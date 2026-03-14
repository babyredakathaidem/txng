<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\BatchRecall;
use App\Models\TraceEvent;
use App\Services\IpfsService;
use Illuminate\Http\Request;
use App\Mail\BatchRecalledMail;
use App\Mail\BatchCascadeRecalledMail;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BatchRecallController extends Controller
{
    public function __construct(private IpfsService $ipfs) {}

    // ══════════════════════════════════════════════════════════════════
    // store() — Phát lệnh thu hồi + Cascade Recall (TCVN 12850:2019)
    // ══════════════════════════════════════════════════════════════════

    /**
     * POST /batches/{batch}/recall
     *
     * Luồng Cascade Recall:
     *  1. Validate & kiểm tra quyền
     *  2. Tạo BatchRecall record cho lô gốc
     *  3. Ghi IPFS cho lô gốc
     *  4. Duyệt đệ quy getAllDescendants() → đổi status, tạo recall record cho từng hậu duệ
     *  5. Gửi email: Admin DN lô gốc + Admin các DN đang sở hữu lô hậu duệ
     *
     * Toàn bộ thao tác DB trong Transaction — đảm bảo tính toàn vẹn.
     */
    public function store(Request $request, Batch $batch)
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.batches.manage']), 403);
        $tenantId = (int) $request->user()->enterprise_id;
        abort_unless($batch->enterprise_id === $tenantId, 403);
        abort_unless($request->user()->isEnterpriseAdmin(), 403, 'Chỉ Admin DN mới được thu hồi lô.');

        if ($batch->isRecalled()) {
            return back()->withErrors(['error' => 'Lô này đã đang trong trạng thái thu hồi.']);
        }

        $data = $request->validate([
            'reason'         => 'required|string|max:2000',
            'notice_content' => 'nullable|string|max:3000',
        ]);

        // Eager load trước transaction
        $batch->load(['enterprise:id,name,code', 'product:id,name,gtin']);

        // ── Bước 4: Tìm hậu duệ trước khi mở transaction ─────────────
        // (getAllDescendants chỉ READ, an toàn ngoài transaction)
        $visited     = [];
        $descendants = $batch->getAllDescendants(10, $visited);

        // ── Wrap toàn bộ DB writes trong Transaction ───────────────────
        $recall = null;
        $ipfsResult = null;
        $recallEvent = null;

        DB::transaction(function () use (
            $batch, $data, $tenantId, $descendants, $request,
            &$recall, &$ipfsResult, &$recallEvent
        ) {
            // ── 1. Tạo recall record cho lô gốc ───────────────────────
            $recall = BatchRecall::create([
                'batch_id'       => $batch->id,
                'recalled_by'    => $request->user()->id,
                'reason'         => $data['reason'],
                'notice_content' => $data['notice_content'] ?? null,
                'recalled_at'    => now(),
                'status'         => 'active',
            ]);

            // ── 2. Cập nhật status lô gốc ─────────────────────────────
            $batch->update(['status' => 'recalled']);

            // ── 3. Ghi IPFS cho lô gốc ────────────────────────────────
            $recallPayload = $this->buildRecallPayload(
                batch:           $batch,
                recall:          $recall,
                recalledByName:  $request->user()->name,
                reason:          $data['reason'],
                noticeContent:   $data['notice_content'] ?? null,
                isCascade:       false,
                parentBatchCode: null,
            );

            $json         = json_encode($recallPayload, JSON_UNESCAPED_UNICODE);
            $contentHash  = hash('sha256', $json);
            $ipfsResult   = $this->ipfs->uploadJson($recallPayload, "recall-batch-{$batch->id}");

            $recallEvent = TraceEvent::create([
                'enterprise_id' => $tenantId,
                'batch_id'      => $batch->id,
                'cte_code'      => 'recall',
                'event_type'    => 'recall',
                'event_time'    => now(),
                'who_name'      => $request->user()->name,
                'why_reason'    => $data['reason'],
                'note'          => $data['notice_content'] ?? null,
                'kde_data'      => $recallPayload,
                'status'        => 'published',
                'content_hash'  => $contentHash,
                'ipfs_cid'      => $ipfsResult['cid'] ?? null,
                'ipfs_url'      => $ipfsResult['url'] ?? null,
                'published_at'  => now(),
                'published_by'  => $request->user()->id,
            ]);

            // ── 4. Cập nhật recall record với IPFS CID ────────────────
            $recall->update(['ipfs_cid' => $ipfsResult['cid'] ?? null]);

            // ── 5. CASCADE RECALL — Thu hồi dây chuyền ────────────────
            foreach ($descendants as $descendant) {
                // Bỏ qua lô đã bị recalled rồi
                if ($descendant->isRecalled()) {
                    continue;
                }

                // Tạo recall record đánh dấu "recalled_by_parent"
                $cascadeRecall = BatchRecall::create([
                    'batch_id'         => $descendant->id,
                    'recalled_by'      => $request->user()->id,
                    'reason'           => "[Cascade từ lô {$batch->code}] " . $data['reason'],
                    'notice_content'   => $data['notice_content'] ?? null,
                    'recalled_at'      => now(),
                    'status'           => 'active',
                    'parent_recall_id' => $recall->id,  // FK đến recall gốc
                ]);

                // Đổi status lô hậu duệ
                $descendant->update(['status' => 'recalled']);

                // Ghi IPFS cho lô hậu duệ (non-blocking nếu lỗi)
                try {
                    $cascadePayload = $this->buildRecallPayload(
                        batch:           $descendant,
                        recall:          $cascadeRecall,
                        recalledByName:  $request->user()->name,
                        reason:          $data['reason'],
                        noticeContent:   $data['notice_content'] ?? null,
                        isCascade:       true,
                        parentBatchCode: $batch->code,
                    );

                    $cascadeJson        = json_encode($cascadePayload, JSON_UNESCAPED_UNICODE);
                    $cascadeHash        = hash('sha256', $cascadeJson);
                    $cascadeIpfs        = $this->ipfs->uploadJson($cascadePayload, "recall-cascade-{$descendant->id}");

                    TraceEvent::create([
                        'enterprise_id' => $descendant->enterprise_id,
                        'batch_id'      => $descendant->id,
                        'cte_code'      => 'recall',
                        'event_type'    => 'recall',
                        'event_time'    => now(),
                        'who_name'      => $request->user()->name,
                        'why_reason'    => "[Cascade] " . $data['reason'],
                        'note'          => "Thu hồi do lô cha [{$batch->code}] bị phát hiện không an toàn.",
                        'kde_data'      => $cascadePayload,
                        'status'        => 'published',
                        'content_hash'  => $cascadeHash,
                        'ipfs_cid'      => $cascadeIpfs['cid'] ?? null,
                        'ipfs_url'      => $cascadeIpfs['url'] ?? null,
                        'published_at'  => now(),
                        'published_by'  => $request->user()->id,
                    ]);

                    $cascadeRecall->update(['ipfs_cid' => $cascadeIpfs['cid'] ?? null]);
                } catch (\Throwable $e) {
                    Log::error('[CascadeRecall] IPFS upload failed (non-blocking)', [
                        'descendant_id' => $descendant->id,
                        'error'         => $e->getMessage(),
                    ]);
                }
            }
        }); // end DB::transaction

        // ── 6. Gửi email (ngoài transaction — không block rollback) ───

        // Email cho Admin DN sở hữu lô gốc
        $this->sendRecallEmailToEnterprise($tenantId, $batch, $recall);

        // Email cho Admin của các DN đang sở hữu lô hậu duệ (có thể khác DN)
        if ($descendants->isNotEmpty()) {
            // Gom các enterprise_id khác nhau từ hậu duệ
            $affectedEnterpriseIds = $descendants
                ->pluck('enterprise_id')
                ->unique()
                ->filter(fn($id) => (int) $id !== $tenantId); // không gửi lại cho chính mình

            foreach ($affectedEnterpriseIds as $affectedEnterpriseId) {
                // Lấy các lô hậu duệ thuộc DN này
                $affectedBatches = $descendants->where('enterprise_id', $affectedEnterpriseId)->values();

                $this->sendCascadeRecallEmailToEnterprise(
                    enterpriseId:    (int) $affectedEnterpriseId,
                    rootBatch:       $batch,
                    rootRecall:      $recall,
                    affectedBatches: $affectedBatches,
                );
            }
        }

        // ── 7. Flash message ───────────────────────────────────────────
        $descendantCount = $descendants->count();
        $ipfsMock = ($ipfsResult['mock'] ?? false) ? ' [MOCK]' : '';
        $cidShort = isset($ipfsResult['cid']) ? substr($ipfsResult['cid'], 0, 12) . '...' : 'N/A';

        $msg = "✅ Phát lệnh thu hồi thành công{$ipfsMock}! CID: {$cidShort}";
        if ($descendantCount > 0) {
            $msg .= " · Đã cascade thu hồi {$descendantCount} lô hậu duệ liên quan.";
        }

        return back()->with('success', $msg);
    }

    // ══════════════════════════════════════════════════════════════════
    // resolve() — Giải quyết xong lệnh thu hồi
    // ══════════════════════════════════════════════════════════════════

    /**
     * PATCH /batches/{batch}/recall/resolve
     */
    public function resolve(Request $request, Batch $batch)
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.batches.manage']), 403);
        $tenantId = (int) $request->user()->enterprise_id;
        abort_unless($batch->enterprise_id === $tenantId, 403);
        abort_unless($request->user()->isEnterpriseAdmin(), 403);

        $recall = $batch->activeRecall;
        if (!$recall) {
            return back()->withErrors(['error' => 'Không tìm thấy lệnh thu hồi đang hoạt động.']);
        }

        $data = $request->validate([
            'resolved_note' => 'nullable|string|max:2000',
        ]);

        $recall->update([
            'status'        => 'resolved',
            'resolved_by'   => $request->user()->id,
            'resolved_at'   => now(),
            'resolved_note' => $data['resolved_note'] ?? null,
        ]);

        $batch->update(['status' => 'active']);

        return back()->with('success', 'Đã đánh dấu xử lý xong lệnh thu hồi.');
    }

    // ══════════════════════════════════════════════════════════════════
    // Private Helpers
    // ══════════════════════════════════════════════════════════════════

    /**
     * Xây dựng payload JSON chuẩn TCVN 12850:2019 để ghi lên IPFS.
     */
    private function buildRecallPayload(
        Batch $batch,
        BatchRecall $recall,
        string $recalledByName,
        string $reason,
        ?string $noticeContent,
        bool $isCascade,
        ?string $parentBatchCode
    ): array {
        return [
            'system'           => 'AGU Traceability',
            'version'          => '1.0',
            'tcvn_ref'         => 'TCVN 12850:2019',
            'event_type'       => $isCascade ? 'CASCADE_RECALL' : 'RECALL',
            'batch_id'         => $batch->id,
            'batch_code'       => $batch->code,
            'product_name'     => $batch->getDisplayName(),
            'enterprise'       => $batch->enterprise?->name,
            'enterprise_code'  => $batch->enterprise?->code,
            'recall_id'        => $recall->id,
            'recalled_by'      => $recalledByName,
            'recalled_at'      => now()->toISOString(),
            'reason'           => $reason,
            'notice_content'   => $noticeContent,
            'is_cascade'       => $isCascade,
            'parent_batch_code'=> $parentBatchCode,
        ];
    }

    /**
     * Gửi email cảnh báo recall chuẩn cho Admin của một DN.
     */
    private function sendRecallEmailToEnterprise(int $enterpriseId, Batch $batch, BatchRecall $recall): void
    {
        $admins = User::where('enterprise_id', $enterpriseId)
            ->where('role', 'enterprise_admin')
            ->get();

        foreach ($admins as $admin) {
            Mail::to($admin->email)->queue(new BatchRecalledMail($batch, $recall));
        }
    }

    /**
     * Gửi email cảnh báo cascade recall cho Admin DN bị ảnh hưởng.
     * DN này không phải là người phát lệnh — họ chỉ đang sở hữu lô hậu duệ.
     */
    private function sendCascadeRecallEmailToEnterprise(
        int $enterpriseId,
        Batch $rootBatch,
        BatchRecall $rootRecall,
        Collection $affectedBatches
    ): void {
        $admins = User::where('enterprise_id', $enterpriseId)
            ->where('role', 'enterprise_admin')
            ->get();

        foreach ($admins as $admin) {
            Mail::to($admin->email)->queue(
                new BatchCascadeRecalledMail($rootBatch, $rootRecall, $affectedBatches)
            );
        }
    }
}