<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\CteTemplate;
use App\Models\TraceEvent;
use App\Services\IpfsService;
use App\Services\BlockchainService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Mail\EventPublishedMail;
use Illuminate\Support\Facades\Mail;

class TraceEventController extends Controller
{
    public function __construct(
        private IpfsService       $ipfs,
        private BlockchainService $blockchain,
    ) {}

    private function tenantId(Request $request): int
    {
        return (int) $request->user()->enterprise_id;
    }

    // ── index ──────────────────────────────────────────────

    public function index(Request $request)
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.trace_events.view', 'enterprise.trace_events.create', 'enterprise.trace_events.manage']), 403);

        $tenantId = $this->tenantId($request);
        $batchId  = $request->query('batch_id');

        $batches = Batch::with('product:id,name,category_id')
            ->where('enterprise_id', $tenantId)
            ->whereNotIn('status', ['consumed', 'recalled'])
            ->orderByDesc('id')
            ->get(['id', 'code', 'product_id', 'product_name', 'status',
                'batch_type', 'certifications', 'current_quantity', 'unit']);

        $events = TraceEvent::with([
            'inputBatches:id,code,product_id,product_name',
            'inputBatches.product:id,name,gtin',
            'publisher:id,name',
        ])
            ->whereHas('inputBatches', fn($q) => $q->where('enterprise_id', $tenantId))
            ->when($batchId, fn($q) => $q->whereHas('inputBatches', fn($q2) => $q2->where('batches.id', $batchId)))
            ->orderByDesc('event_time')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Events/Index', [
            'batches' => $batches->map(fn($b) => [
                'id'               => $b->id,
                'code'             => $b->code,
                'product_id'       => $b->product_id,
                'product_name'     => $b->product?->name ?? $b->product_name,
                'status'           => $b->status,
                'batch_type'       => $b->batch_type,
                'certifications'   => $b->certifications ?? [],
                'current_quantity' => $b->current_quantity,
                'unit'             => $b->unit,
                'category_id'      => $b->product?->category_id ?? null,
            ]),
            'events'  => $events,
            'filters' => ['batch_id' => $batchId],
        ]);
    }

    // ── getTemplates (API) ────────────────────────────────

    public function getTemplates(Request $request)
    {
        $categoryId = $request->query('category_id');
        $batchId    = $request->query('batch_id');

        $templates = collect();

        if ($categoryId) {
            $templates = CteTemplate::where('category_id', $categoryId)
                ->orderBy('step_order')
                ->get();
        }

        // Mark is_done cho từng template nếu có batch_id
        $doneCodes = [];
        if ($batchId) {
            $doneCodes = TraceEvent::whereHas('inputBatches', fn($q) => $q->where('batches.id', $batchId))
                ->where('status', 'published')
                ->pluck('cte_code')
                ->unique()
                ->all();
        }

        return response()->json([
            'templates' => $templates->map(fn($t) => [
                'id'          => $t->id,
                'code'        => $t->code,
                'name_vi'     => $t->name_vi,
                'step_order'  => $t->step_order,
                'is_required' => $t->is_required,
                'kde_schema'  => $t->kde_schema ?? [],
                'is_done'     => in_array($t->code, $doneCodes),
            ]),
        ]);
    }

    // ── store ──────────────────────────────────────────────

    public function store(Request $request)
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.trace_events.create']), 403);

        $tenantId = $this->tenantId($request);

        $data = $request->validate([
            'batch_id'     => 'required|integer|exists:batches,id',
            'cte_code'     => 'required|string|max:60',
            'event_time'   => 'required|date',
            'kde_data'     => 'required|array',
            'who_name'     => 'nullable|string|max:255',
            'where_address'=> 'nullable|string|max:255',
            'where_lat'    => 'nullable|numeric|between:-90,90',
            'where_lng'    => 'nullable|numeric|between:-180,180',
            'why_reason'   => 'nullable|string|max:255',
            'note'         => 'nullable|string|max:2000',
        ]);

        $batch = Batch::where('id', $data['batch_id'])
            ->where('enterprise_id', $tenantId)
            ->firstOrFail();

        $event = TraceEvent::create([
            'enterprise_id' => $tenantId,
            'cte_code'      => $data['cte_code'],
            'event_time'    => $data['event_time'],
            'kde_data'      => $data['kde_data'],
            'who_name'      => $data['who_name'] ?? null,
            'where_address' => $data['where_address'] ?? null,
            'where_lat'     => $data['where_lat'] ?? null,
            'where_lng'     => $data['where_lng'] ?? null,
            'why_reason'    => $data['why_reason'] ?? null,
            'note'          => $data['note'] ?? null,
            'status'        => 'draft',
        ]);

        $event->inputBatches()->attach($batch->id, [
            'quantity' => $batch->current_quantity,
            'unit'     => $batch->unit,
        ]);

        return back()->with('success', 'Đã tạo sự kiện.');
    }

    // ── update ─────────────────────────────────────────────

    public function update(Request $request, TraceEvent $traceEvent)
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.trace_events.manage']), 403);
        $this->assertTenant($request, $traceEvent);

        if ($traceEvent->isPublished()) {
            abort(403, 'Sự kiện đã publish lên IPFS, không thể sửa.');
        }

        $data = $request->validate([
            'cte_code'     => 'required|string|max:60',
            'event_time'   => 'required|date',
            'kde_data'     => 'required|array',
            'who_name'     => 'nullable|string|max:255',
            'where_address'=> 'nullable|string|max:255',
            'where_lat'    => 'nullable|numeric|between:-90,90',
            'where_lng'    => 'nullable|numeric|between:-180,180',
            'why_reason'   => 'nullable|string|max:255',
            'note'         => 'nullable|string|max:2000',
        ]);

        $traceEvent->update($data);

        return back()->with('success', 'Đã cập nhật sự kiện.');
    }

    // ── destroy ────────────────────────────────────────────

    public function destroy(Request $request, TraceEvent $traceEvent)
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.trace_events.manage']), 403);
        $this->assertTenant($request, $traceEvent);

        if ($traceEvent->isPublished()) {
            abort(403, 'Sự kiện đã publish lên IPFS, không thể xóa.');
        }

        $traceEvent->delete();

        return back()->with('success', 'Đã xóa sự kiện.');
    }

    // ── publish → IPFS + Hyperledger Fabric ───────────────

    public function publish(Request $request, TraceEvent $traceEvent)
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.trace_events.manage']), 403);
        $traceEvent->loadMissing('inputBatches.product.category', 'inputBatches.enterprise');
        $this->assertTenant($request, $traceEvent);

        if ($traceEvent->isPublished()) {
            return back()->with('success', 'Sự kiện này đã được publish rồi.');
        }

        // ── 1. Payload + content hash ──────────────────────
        $payload     = $traceEvent->toIpfsPayload();
        $json        = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $contentHash = hash('sha256', $json);

        // ── 2. Upload lên IPFS ─────────────────────────────
        $ipfsResult = $this->ipfs->uploadJson(
            $payload,
            "event-{$traceEvent->id}-{$traceEvent->cte_code}"
        );

        if (!$ipfsResult) {
            return back()->withErrors(['ipfs' => 'Không thể upload lên IPFS. Vui lòng thử lại.']);
        }

        // ── 3. Ghi lên Hyperledger Fabric (non-blocking) ───
        $txHash      = null;
        $fabricOk    = false;
        $fabricError = null;

        try {
            $fabricResult = $this->blockchain->recordEvent(
                eventID:      (string) $traceEvent->id,
                batchCode:    (string) ($traceEvent->inputBatches->first()?->code ?? ''),
                enterpriseID: (string) ($traceEvent->inputBatches->first()?->enterprise?->code ?? ''),
                cteCode:      (string) $traceEvent->cte_code,
                contentHash:  $contentHash,
                ipfsCid:      $ipfsResult['cid'],
                recordedBy:   $request->user()->name,
            );

            if ($fabricResult['success']) {
                $fabricOk = true;
                $txHash   = $fabricResult['data']['txId']
                    ?? $fabricResult['data']['tx_hash']
                    ?? $fabricResult['data']['transactionId']
                    ?? null;

                Log::info('[Publish] Fabric OK', [
                    'event_id' => $traceEvent->id,
                    'tx_hash'  => $txHash,
                    'cid'      => $ipfsResult['cid'],
                ]);
            } else {
                $fabricError = $fabricResult['error'] ?? 'unknown';
                Log::warning('[Publish] Fabric failed — non-blocking', [
                    'event_id' => $traceEvent->id,
                    'error'    => $fabricError,
                ]);
            }
        } catch (\Throwable $e) {
            $fabricError = $e->getMessage();
            Log::error('[Publish] Fabric exception — non-blocking', [
                'event_id'  => $traceEvent->id,
                'exception' => $e->getMessage(),
            ]);
        }

        // ── 4. Cập nhật DB ─────────────────────────────────
        $traceEvent->update([
            'status'       => 'published',
            'content_hash' => $contentHash,
            'ipfs_cid'     => $ipfsResult['cid'],
            'ipfs_url'     => $ipfsResult['url'],
            'tx_hash'      => $txHash,
            'published_at' => now(),
            'published_by' => $request->user()->id,
        ]);

        // ── 5. Email ───────────────────────────────────────
        Mail::to($request->user()->email)->queue(
            new EventPublishedMail($traceEvent->fresh(['inputBatches.product']))
        );

        // ── 6. Flash ───────────────────────────────────────
        $ipfsMock = $ipfsResult['mock'] ? ' [MOCK]' : '';

        if ($fabricOk && $txHash) {
            $shortTx = substr($txHash, 0, 16) . '...';
            $msg     = "✅ Publish thành công{$ipfsMock}! IPFS: "
                . substr($ipfsResult['cid'], 0, 12) . "... · Fabric TX: {$shortTx}";
        } elseif ($fabricOk) {
            $msg = "✅ Publish thành công{$ipfsMock}! IPFS + Fabric OK.";
        } else {
            $msg = "✅ Publish IPFS thành công{$ipfsMock}! CID: "
                . substr($ipfsResult['cid'], 0, 16)
                . "... Fabric chưa ghi được (sẽ retry sau).";
        }

        return back()->with('success', $msg);
    }

    // ── uploadAttachment ──────────────────────────────────

    public function uploadAttachment(Request $request, TraceEvent $traceEvent)
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.trace_events.create', 'enterprise.trace_events.manage']), 403);
        $this->assertTenant($request, $traceEvent);

        if ($traceEvent->isPublished()) {
            return response()->json(['error' => 'Sự kiện đã publish, không thể thêm đính kèm.'], 403);
        }

        $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,webp',
        ]);

        $file     = $request->file('file');
        $content  = file_get_contents($file->getRealPath());
        $filename = $file->getClientOriginalName();
        $mime     = $file->getMimeType();

        $result = $this->ipfs->uploadFile($content, $filename, $mime);

        if (!$result) {
            return response()->json(['error' => 'Upload IPFS thất bại.'], 500);
        }

        $attachments   = $traceEvent->attachments ?? [];
        $attachments[] = [
            'cid'       => $result['cid'],
            'url'       => $result['url'],
            'name'      => $filename,
            'mime_type' => $mime,
            'mock'      => $result['mock'],
        ];

        $traceEvent->update(['attachments' => $attachments]);

        return response()->json(['attachment' => end($attachments)]);
    }

    // ── verifyIpfs (backward compat — JSON endpoint) ──────
    // Kept for backward compat nếu có link cũ đang chạy

    public function verifyIpfs(Request $request, string $cid)
    {
        $expectedHash = $request->query('hash', '');

        if (!$expectedHash) {
            return response()->json(['error' => 'Thiếu hash để xác minh.'], 400);
        }

        $result = $this->ipfs->verify($cid, $expectedHash);

        return response()->json([
            'cid'           => $cid,
            'valid'         => $result['valid'],
            'fetched'       => $result['fetched'],
            'expected_hash' => $result['expected_hash'],
            'actual_hash'   => $result['actual_hash'],
            'mock'          => $result['mock'],
            'notice'        => 'Đây là endpoint cũ. Dùng /verify/integrity/{event_id} để xác minh đầy đủ 3 lớp.',
        ]);
    }

    // ════════════════════════════════════════════════════════════════
    // verifyIntegrity — PUBLIC endpoint xác minh 3 lớp
    //
    // Tam giác xác minh:
    //   Layer 1 — Fabric    : đọc content_hash từ Fabric ledger (bất biến)
    //   Layer 2 — IPFS      : fetch IPFS → re-hash → so với Fabric hash
    //   Layer 3 — DB/Display: DB.content_hash → so với Fabric hash
    //
    // Chỉ khi cả 3 khớp → dữ liệu toàn vẹn.
    // ════════════════════════════════════════════════════════════════

    
     public function verifyIntegrity(Request $request, int $id)
    {
        $event = TraceEvent::withoutGlobalScopes()
            ->with(['inputBatches:id,code,product_name,enterprise_id', 'inputBatches.enterprise:id,name,code'])
            ->find($id);
 
        if (!$event || $event->status !== 'published') {
            return response()->json([
                'error'   => 'Sự kiện không tồn tại hoặc chưa được publish.',
                'verdict' => 'error',
            ], 404);
        }
 
        // ── Layer 1: Hyperledger Fabric ────────────────────
        $fabricRecord = $this->blockchain->getEventRecord((string) $event->id);
        $fabricMock   = $fabricRecord['mock'];
        $fabricFound  = $fabricRecord['found'];
        $fabricHash   = $fabricRecord['content_hash'];
 
        // Nếu Fabric mock/missing → fallback DB.content_hash
        $groundTruthHash = $fabricFound ? $fabricHash : $event->content_hash;
 
        $fabricStatus = match (true) {
            $fabricMock  => 'mock',
            $fabricFound => 'found',
            default      => 'missing',
        };
 
        // ── Layer 2: IPFS ──────────────────────────────────
        $ipfsValid   = false;
        $ipfsHash    = null;
        $ipfsFetched = false;
        $ipfsMock    = false;
 
        if ($event->ipfs_cid && $groundTruthHash) {
            $ipfsResult  = $this->ipfs->verify($event->ipfs_cid, $groundTruthHash);
            $ipfsFetched = $ipfsResult['fetched'];
            $ipfsHash    = $ipfsResult['actual_hash'];
            $ipfsValid   = $ipfsResult['valid'];
            $ipfsMock    = $ipfsResult['mock'];
        }
 
        // ── Layer 3: Database ──────────────────────────────
        $dbHash  = $event->content_hash;
        $dbValid = $groundTruthHash && $dbHash && hash_equals($groundTruthHash, $dbHash);
 
        // ── Verdict ────────────────────────────────────────
        $verdict = $this->resolveVerdict(
            fabricMock:  $fabricMock,
            fabricFound: $fabricFound,
            ipfsFetched: $ipfsFetched,
            ipfsValid:   $ipfsValid,
            dbValid:     $dbValid,
        );
 
        return response()->json([
            'verdict' => $verdict,
 
            'fabric' => [
                'status'        => $fabricStatus,
                'mock'          => $fabricMock,
                'found'         => $fabricFound,
                'hash'          => $fabricHash,
                'recorded_by'   => $fabricRecord['recorded_by'],
                'timestamp'     => $fabricRecord['timestamp'],
                'fallback_used' => !$fabricFound,
            ],
 
            'ipfs' => [
                'cid'     => $event->ipfs_cid,
                'fetched' => $ipfsFetched,
                'hash'    => $ipfsHash,
                'valid'   => $ipfsValid,
                'mock'    => $ipfsMock,
            ],
 
            'db' => [
                'hash'  => $dbHash,
                'valid' => $dbValid,
            ],
 
            'ground_truth_source' => $fabricFound ? 'fabric' : 'db_fallback',
            'ground_truth_hash'   => $groundTruthHash,
 
            'event' => [
                'id'           => $event->id,
                'event_code'   => $event->event_code,
                'cte_code'     => $event->cte_code,
                'published_at' => optional($event->published_at)->format('d/m/Y H:i'),
                'tx_hash'      => $event->tx_hash,
                'ipfs_cid'     => $event->ipfs_cid,
                'ipfs_url'     => $event->ipfs_url,
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────

    private function resolveVerdict(
        bool $fabricMock,
        bool $fabricFound,
        bool $ipfsFetched,
        bool $ipfsValid,
        bool $dbValid,
    ): string {
        // Không có IPFS → không thể verify
        if (!$ipfsFetched) return 'ipfs_unavailable';

        // IPFS content bị tamper (hash không khớp với ground truth)
        if (!$ipfsValid) return 'tampered_ipfs';

        // DB bị sửa sau publish (hiển thị dữ liệu giả cho người dùng)
        if (!$dbValid) return 'tampered_db';

        // Tất cả hợp lệ nhưng Fabric mock → valid_no_fabric
        if ($fabricMock || !$fabricFound) return 'valid_no_fabric';

        // Tất cả hợp lệ + Fabric xác nhận
        return 'valid';
    }

    // ── Private ────────────────────────────────────────────

    private function assertTenant(Request $request, TraceEvent $event): void
    {
        abort_unless(
            (int) $event->enterprise_id === $this->tenantId($request),
            403
        );
    }
}