<?php

namespace App\Http\Controllers;

use App\Mail\TransferAcceptedMail;
use App\Mail\TransferSentMail;
use App\Models\Batch;
use App\Models\BatchLineage;
use App\Models\BatchTransfer;
use App\Models\Enterprise;
use App\Models\TraceEvent;
use App\Services\IpfsService;
use App\Traits\HasTenant;
use App\Traits\NotifiesEnterpriseAdmins;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BatchTransferController extends Controller
{
    use HasTenant, NotifiesEnterpriseAdmins;

    protected IpfsService $ipfs;

    public function __construct(IpfsService $ipfs)
    {
        $this->ipfs = $ipfs;
    }

    // ── Danh sách inbox ───────────────────────────────────
    public function inbox(Request $request)
    {
        $tenantId = $this->tenantId($request);

        $transfers = BatchTransfer::with([
            'batch:id,code,product_name,unit',
            'fromEnterprise:id,name,code',
            'toEnterprise:id,name,code',
        ])
            ->where('to_enterprise_id', $tenantId)
            ->where('status', 'pending')
            ->latest('transferred_at')
            ->get();

        return Inertia::render('Batches/TransferInbox', [
            'transfers' => $transfers,
        ]);
    }

    // ── Show form chuyển giao ─────────────────────────────
    public function show(Request $request, Batch $batch)
    {
        $tenantId = $this->tenantId($request);
        abort_unless($batch->enterprise_id === $tenantId, 403);

        return Inertia::render('Batches/Transfer', [
            'batch' => $batch->only([
                'id', 'code', 'product_name', 'quantity',
                'current_quantity', 'unit', 'status',
            ]),
        ]);
    }

    // ── DN A tạo yêu cầu chuyển giao ─────────────────────
    public function store(Request $request, Batch $batch)
    {
        $tenantId = $this->tenantId($request);
        abort_unless($batch->enterprise_id === $tenantId, 403);
        abort_unless(in_array($batch->status, ['active', 'received']), 422);

        $data = $request->validate([
            'to_enterprise_code' => 'required|string|max:30',
            'quantity'           => 'required|numeric|min:0.01',
            'invoice_no'         => 'nullable|string|max:100',
            'note'               => 'nullable|string|max:500',
        ]);

        $toEnterprise = Enterprise::where('code', $data['to_enterprise_code'])
            ->where('status', 'approved')
            ->first();

        if (! $toEnterprise) {
            return back()->withErrors(['to_enterprise_code' => 'Không tìm thấy doanh nghiệp với mã này.']);
        }

        if ($toEnterprise->id === $tenantId) {
            return back()->withErrors(['to_enterprise_code' => 'Không thể chuyển giao cho chính mình.']);
        }

        $available = $batch->current_quantity ?? $batch->quantity;
        if ($data['quantity'] > $available) {
            return back()->withErrors(['quantity' => "Số lượng vượt quá hiện có ({$available})."]);
        }

        $transfer = BatchTransfer::create([
            'batch_id'           => $batch->id,
            'from_enterprise_id' => $tenantId,
            'to_enterprise_id'   => $toEnterprise->id,
            'quantity'           => $data['quantity'],
            'unit'               => $batch->unit,
            'invoice_no'         => $data['invoice_no'] ?? null,
            'note'               => $data['note'] ?? null,
            'status'             => 'pending',
            'transferred_at'     => now(),
        ]);

        // Gửi mail thông báo cho admin DN B
        $this->notifyEnterpriseAdmins(
            $toEnterprise->id,
            new TransferSentMail($transfer->load(['batch', 'fromEnterprise', 'toEnterprise']))
        );

        return back()->with('success', "Đã gửi yêu cầu chuyển giao lô {$batch->code} cho {$toEnterprise->name}. Email thông báo đã gửi.");
    }

    // ── DN B xác nhận nhận hàng ───────────────────────────
    public function accept(Request $request, BatchTransfer $transfer)
    {
        $tenantId = $this->tenantId($request);
        abort_unless($transfer->to_enterprise_id === $tenantId, 403);
        abort_unless($transfer->isPending(), 422);

        $acceptedBy = $request->user()->name;

        DB::transaction(function () use ($transfer, $tenantId, $acceptedBy) {
            // withoutGlobalScopes — batch đang thuộc DN A, bỏ qua TenantScope
            $batch = Batch::withoutGlobalScopes()->findOrFail($transfer->batch_id);
            $batch->load('enterprise');

            $fromEnterpriseName = $transfer->fromEnterprise->name;
            $toEnterpriseName   = $transfer->toEnterprise->name;

            // ── Event 1: DN A — Ghi nhận chuyển giao lô ─────
            $eventSent = TraceEvent::create([
                'batch_id'      => $batch->id,
                'enterprise_id' => $transfer->from_enterprise_id,
                'event_type'    => 'transfer_sent',
                'cte_code'      => 'transfer_sent',
                'kde_data'      => [
                    'action'        => 'transfer_sent',
                    'to_enterprise' => $toEnterpriseName,
                    'quantity'      => $transfer->quantity,
                    'unit'          => $transfer->unit,
                    'invoice_no'    => $transfer->invoice_no,
                ],
                'who_name'   => $fromEnterpriseName,
                'note'       => "Chuyển giao lô cho {$toEnterpriseName}",
                'status'     => 'draft',
                'event_time' => $transfer->transferred_at ?? now(),
            ]);

            // ── Event 2: DN B — Nhận lô ──────────────────────
            $eventReceived = TraceEvent::create([
                'batch_id'      => $batch->id,
                'enterprise_id' => $tenantId,
                'event_type'    => 'transfer_received',
                'cte_code'      => 'transfer_received',
                'kde_data'      => [
                    'action'          => 'transfer_accepted',
                    'from_enterprise' => $fromEnterpriseName,
                    'to_enterprise'   => $toEnterpriseName,
                    'quantity'        => $transfer->quantity,
                    'unit'            => $transfer->unit,
                    'invoice_no'      => $transfer->invoice_no,
                ],
                'who_name'   => $acceptedBy,
                'note'       => "Nhận hàng từ {$fromEnterpriseName}",
                'status'     => 'draft',
                'event_time' => now(),
            ]);

            // ── Auto-publish cả 2 events lên IPFS + Fabric ───
            $this->autoPublishEvent($eventSent, $batch);
            $this->autoPublishEvent($eventReceived, $batch);

            // ── Cập nhật transfer ─────────────────────────────
            $transfer->update([
                'status'            => 'accepted',
                'accepted_at'       => now(),
                'transfer_event_id' => $eventReceived->id,
            ]);

            // ── Chuyển lô sang DN B ───────────────────────────
            $batch->update([
                'enterprise_id' => $tenantId,
                'batch_type'    => 'received',
                'status'        => 'active', // Đổi từ received thành active
            ]);

            // ── Ghi BatchLineage cho transfer ─────────────────
            //
            // Tại sao cần lineage cho transfer?
            //   - Batch ID KHÔNG đổi khi transfer, chỉ enterprise_id đổi.
            //   - events của cả 2 DN đều có cùng batch_id → loadEvents() tự gom.
            //   - Tuy nhiên ghi lineage giúp:
            //     (1) Thể hiện rõ điểm chuyển giao trong chuỗi cung ứng
            //     (2) QrScanController@collectAncestorBatchIds có thể theo dõi
            //     (3) Export EPCIS 2.0 cần lineage rõ ràng
            //     (4) Audit trail đầy đủ theo TCVN 12850:2019
            //
            // input_batch_id = output_batch_id = batch->id (cùng 1 batch record)
            // → Khác với split/merge (tạo batch record mới)
            // → Ở đây lineage thể hiện "điểm cắt" chuyển quyền sở hữu
            BatchLineage::create([
                'transformation_type' => 'transfer',
                'input_batch_id'      => $batch->id,   // ID không đổi
                'output_batch_id'     => $batch->id,   // ID không đổi — chủ sở hữu thay đổi
                'quantity'            => $transfer->quantity,
                'unit'                => $transfer->unit,
                'event_id'            => $eventReceived->id,
            ]);
        });

        // Gửi mail thông báo cho DN A
        $transfer->refresh()->load(['batch', 'fromEnterprise', 'toEnterprise']);
        $this->notifyEnterpriseAdmins(
            $transfer->from_enterprise_id,
            new TransferAcceptedMail($transfer)
        );

        return redirect()->route('batches.index')
            ->with('success', 'Đã xác nhận nhận hàng thành công. Sự kiện đã được publish tự động.');
    }

    // ── DN B từ chối ──────────────────────────────────────
    public function reject(Request $request, BatchTransfer $transfer)
    {
        $tenantId = $this->tenantId($request);
        abort_unless($transfer->to_enterprise_id === $tenantId, 403);
        abort_unless($transfer->isPending(), 422);

        $data = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $transfer->update([
            'status'           => 'rejected',
            'rejected_at'      => now(),
            'rejection_reason' => $data['rejection_reason'],
        ]);

        return back()->with('success', 'Đã từ chối yêu cầu chuyển giao.');
    }

    // ── Helper: publish event lên IPFS + Fabric ───────────
    private function autoPublishEvent(TraceEvent $event, Batch $batch): void
    {
        try {
            $payload     = $event->toIpfsPayload();
            $contentHash = hash('sha256', json_encode($payload));
            $ipfsResult  = $this->ipfs->uploadJson($payload);

            $updateData = [
                'status'       => 'published',
                'content_hash' => $contentHash,
                'published_at' => now(),
                'published_by' => null,
            ];

            if ($ipfsResult) {
                $updateData['ipfs_cid'] = $ipfsResult['cid'];
                $updateData['ipfs_url'] = $ipfsResult['url'];
            }

            $event->update($updateData);
        } catch (\Throwable $e) {
            // Không fail transaction khi IPFS lỗi — chỉ log
            \Log::error('auto-publish transfer event failed', [
                'event_id' => $event->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}