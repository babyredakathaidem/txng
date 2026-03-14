<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\BatchRecall;
use App\Models\TraceEvent;
use App\Services\IpfsService;
use Illuminate\Http\Request;
use App\Mail\BatchRecalledMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class BatchRecallController extends Controller
{
    public function __construct(private IpfsService $ipfs) {}

    /**
     * POST /batches/{batch}/recall
     * Admin DN phát lệnh thu hồi lô
     */
    public function store(Request $request, Batch $batch)
    {
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

        // 1. Tạo recall record
        $recall = BatchRecall::create([
            'batch_id'       => $batch->id,
            'recalled_by'    => $request->user()->id,
            'reason'         => $data['reason'],
            'notice_content' => $data['notice_content'] ?? null,
            'recalled_at'    => now(),
            'status'         => 'active',
        ]);

        // 2. Cập nhật trạng thái lô
        $batch->update(['status' => 'recalled']);
            $adminUsers = User::where('enterprise_id', $tenantId)
            ->where('role', 'enterprise_admin')
            ->get();
        foreach ($adminUsers as $admin) {
            Mail::to($admin->email)->queue(new BatchRecalledMail($batch, $recall));
        }

        // 3. Ghi sự kiện đặc biệt type=recall lên IPFS (minh bạch lý do thu hồi)
        $batch->load('enterprise', 'product');

        $recallPayload = [
            'system'          => 'AGU Traceability',
            'version'         => '1.0',
            'tcvn_ref'        => 'TCVN 12850:2019',
            'event_type'      => 'RECALL',
            'batch_id'        => $batch->id,
            'batch_code'      => $batch->code,
            'product_name'    => $batch->getDisplayName(),
            'enterprise'      => $batch->enterprise?->name,
            'recall_id'       => $recall->id,
            'recalled_by'     => $request->user()->name,
            'recalled_at'     => now()->toISOString(),
            'reason'          => $data['reason'],
            'notice_content'  => $data['notice_content'] ?? null,
        ];

        $json        = json_encode($recallPayload, JSON_UNESCAPED_UNICODE);
        $contentHash = hash('sha256', $json);
        $ipfsResult  = $this->ipfs->uploadJson($recallPayload, "recall-batch-{$batch->id}");

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

        return back()->with('success', 'Đã phát lệnh thu hồi lô. Sự kiện thu hồi đã được ghi lên IPFS.');
    }

    /**
     * PATCH /batches/{batch}/recall/resolve
     * Đánh dấu đã xử lý xong thu hồi
     */
    public function resolve(Request $request, Batch $batch)
    {
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

        // Lô trở về trạng thái active sau khi xử lý xong
        $batch->update(['status' => 'active']);

        return back()->with('success', 'Đã đánh dấu xử lý xong lệnh thu hồi.');
    }
}