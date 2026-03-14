<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\BatchTransfer;
use App\Models\Enterprise;
use App\Models\Product;
use App\Models\TraceEvent;
use App\Models\TraceLocation;
use App\Models\User;
use App\Services\BlockchainService;
use App\Services\GS1Service;
use App\Services\IpfsService;
use App\Services\QrCodeService;
use App\Traits\HasTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TransferEventController extends Controller
{
    use HasTenant;

    public function __construct(
        private GS1Service        $gs1Service,
        private QrCodeService     $qrCodeService,
        private IpfsService       $ipfsService,
        private BlockchainService $blockchainService,
    ) {}

    // ════════════════════════════════════════════════════════════════
    // 1. storeTransferOut — DN A gửi lô → DN B (chờ xác nhận)
    // ════════════════════════════════════════════════════════════════

    public function storeTransferOut(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.trace_events.create']), 403);

        $tenantId = $this->tenantId($request);

        $data = $request->validate([
            'input_batch_ids'    => 'required|array|min:1',
            'input_batch_ids.*'  => 'integer|exists:batches,id',
            'to_enterprise_id'   => 'required|integer|exists:enterprises,id',
            'event_time'         => 'required|date',
            'who_name'           => 'required|string|max:255',
            'kde_data'           => 'nullable|array',
            'note'               => 'nullable|string|max:2000',
            'gs1_document_ref'   => 'nullable|string|max:100',  // AI(400) số hợp đồng
            'sscc_code'          => 'nullable|string|max:20',   // AI(00) SSCC
        ]);

        // Không cho phép chuyển giao cho chính mình
        if ((int) $data['to_enterprise_id'] === $tenantId) {
            return back()->withErrors(['to_enterprise_id' => 'Không thể chuyển giao cho chính doanh nghiệp của mình.']);
        }

        // Load và validate tất cả input batches: thuộc tenant + active
        $batches = Batch::whereIn('id', $data['input_batch_ids'])
            ->where('enterprise_id', $tenantId)
            ->where('status', 'active')
            ->get()
            ->keyBy('id');

        if ($batches->count() !== count($data['input_batch_ids'])) {
            return back()->withErrors(['input_batch_ids' => 'Một số lô không hợp lệ, không thuộc doanh nghiệp, hoặc không ở trạng thái active.']);
        }

        $enterprise   = Enterprise::findOrFail($tenantId);
        $toEnterprise = Enterprise::findOrFail($data['to_enterprise_id']);

        DB::transaction(function () use ($data, $tenantId, $enterprise, $toEnterprise, $batches) {

            // 1. Tạo TraceEvent transfer_out (status=draft, transfer_status=pending)
            $event = TraceEvent::create([
                'enterprise_id'   => $tenantId,
                'event_category'  => TraceEvent::CAT_TRANSFER_OUT,
                'event_code'      => TraceEvent::generateEventCode(
                    $enterprise->code, 'SHIP', $this->nextEventSeq($tenantId)
                ),
                'event_token'     => (string) Str::uuid(),
                'cte_code'        => 'transfer_out',
                'event_type'      => 'transfer_out',
                'event_time'      => $data['event_time'],
                'who_name'        => $data['who_name'],
                'kde_data'        => $data['kde_data'] ?? null,
                'note'            => $data['note'] ?? null,
                'to_enterprise_id'=> $data['to_enterprise_id'],
                'gs1_document_ref'=> $data['gs1_document_ref'] ?? null,
                'transfer_status' => 'pending',
                'status'          => 'draft',
            ]);

            // 2. Gắn input batches vào pivot (KHÔNG consumed — chờ đối tác confirm)
            foreach ($batches as $batch) {
                $event->inputBatches()->attach($batch->id, [
                    'quantity' => $batch->current_quantity,
                    'unit'     => $batch->unit,
                ]);
            }

            // 3. Tạo BatchTransfer records (compatibility)
            foreach ($batches as $batch) {
                BatchTransfer::create([
                    'batch_id'          => $batch->id,
                    'from_enterprise_id'=> $tenantId,
                    'to_enterprise_id'  => $data['to_enterprise_id'],
                    'quantity'          => $batch->current_quantity,
                    'unit'              => $batch->unit,
                    'invoice_no'        => $data['gs1_document_ref'] ?? null,
                    'sscc_code'         => $data['sscc_code'] ?? null,
                    'status'            => 'pending',
                    'transfer_event_id' => $event->id,
                    'transferred_at'    => now(),
                ]);
            }

            // 4. Notify users của toEnterprise (database notification, non-blocking)
            $this->notifyRecipients($toEnterprise->id, $event, $enterprise->name, $batches->count());
        });

        return redirect()->back()->with(
            'success', "Đã gửi lệnh chuyển giao {$batches->count()} lô, đang chờ [{$toEnterprise->name}] xác nhận."
        );
    }

    // ════════════════════════════════════════════════════════════════
    // 2. acceptTransfer — Bên nhận xác nhận
    // ════════════════════════════════════════════════════════════════

    public function acceptTransfer(Request $request, TraceEvent $event): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.trace_events.create']), 403);

        $tenantId = $this->tenantId($request);

        // Validate quyền nhận + trạng thái
        abort_unless($event->event_category === TraceEvent::CAT_TRANSFER_OUT, 403, 'Không phải sự kiện chuyển giao đi.');
        abort_unless((int) $event->to_enterprise_id === $tenantId, 403, 'Bạn không phải người nhận của lệnh chuyển giao này.');
        abort_unless($event->transfer_status === 'pending', 422, 'Lệnh chuyển giao này đã được xử lý.');

        $myEnterprise   = Enterprise::findOrFail($tenantId);
        $fromEnterprise = Enterprise::findOrFail($event->enterprise_id);

        $receiveEvent = DB::transaction(function () use ($event, $tenantId, $myEnterprise, $fromEnterprise) {

            // 1. Cập nhật transfer event gốc
            $event->update([
                'transfer_status' => 'accepted',
                'accepted_at'     => now(),
            ]);

            // 2. Lấy input batches + chuyển quyền sở hữu
            $inputBatches = $event->inputBatches()->withoutGlobalScopes()->get();
            foreach ($inputBatches as $batch) {
                $batch->update([
                    'enterprise_id' => $tenantId,
                    'status'        => 'active',
                ]);
            }

            // 3. Update BatchTransfer records
            BatchTransfer::where('transfer_event_id', $event->id)->update([
                'status'      => 'accepted',
                'accepted_at' => now(),
            ]);

            // 4. Tạo TraceEvent transfer_in cho bên nhận
            $receiveEvent = TraceEvent::create([
                'enterprise_id'     => $tenantId,
                'event_category'    => TraceEvent::CAT_TRANSFER_IN,
                'event_code'        => TraceEvent::generateEventCode(
                    $myEnterprise->code, 'RECEIVE', $this->nextEventSeq($tenantId)
                ),
                'event_token'       => (string) Str::uuid(),
                'cte_code'          => 'transfer_in',
                'event_type'        => 'transfer_in',
                'event_time'        => now(),
                'who_name'          => auth()->user()->name,
                'from_enterprise_id'=> $event->enterprise_id,
                'gs1_document_ref'  => $event->gs1_document_ref,
                'transfer_status'   => 'accepted',
                'accepted_at'       => now(),
                'status'            => 'draft',
            ]);

            // 5. Gắn các lô đã nhận vào transfer_in event
            foreach ($inputBatches as $batch) {
                $receiveEvent->inputBatches()->attach($batch->id, [
                    'quantity' => $batch->current_quantity,
                    'unit'     => $batch->unit,
                ]);
            }

            // 6. Update accepted_event_id trong BatchTransfer
            BatchTransfer::where('transfer_event_id', $event->id)->update([
                'accepted_event_id' => $receiveEvent->id,
            ]);

            // 7. Publish cả 2 events lên IPFS + Fabric (non-blocking)
            $this->publishTransferEvents($event, $receiveEvent, $inputBatches, $fromEnterprise, $myEnterprise);

            // 8. Notify bên gửi
            $this->notifyRecipients($fromEnterprise->id, $event, $myEnterprise->name, $inputBatches->count(), 'accepted');

            return $receiveEvent;
        });

        return redirect()->route('transfer.pending')
            ->with('success', "Đã xác nhận nhận hàng. {$receiveEvent->inputBatches()->count()} lô đã được chuyển vào kho của bạn.");
    }

    // ════════════════════════════════════════════════════════════════
    // 3. rejectTransfer — Bên nhận từ chối
    // ════════════════════════════════════════════════════════════════

    public function rejectTransfer(Request $request, TraceEvent $event): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.trace_events.manage']), 403);

        $tenantId = $this->tenantId($request);

        abort_unless($event->event_category === TraceEvent::CAT_TRANSFER_OUT, 403, 'Không phải sự kiện chuyển giao đi.');
        abort_unless((int) $event->to_enterprise_id === $tenantId, 403, 'Bạn không phải người nhận của lệnh này.');
        abort_unless($event->transfer_status === 'pending', 422, 'Lệnh chuyển giao này đã được xử lý.');

        $data = $request->validate([
            'rejection_reason' => 'required|string|min:10|max:500',
        ]);

        $myEnterprise   = Enterprise::findOrFail($tenantId);
        $fromEnterprise = Enterprise::findOrFail($event->enterprise_id);

        $event->update([
            'transfer_status'  => 'rejected',
            'rejected_at'      => now(),
            'rejection_reason' => $data['rejection_reason'],
        ]);

        BatchTransfer::where('transfer_event_id', $event->id)->update([
            'status'           => 'rejected',
            'rejected_at'      => now(),
            'rejection_reason' => $data['rejection_reason'],
        ]);

        // Input batches GIỮ NGUYÊN status + enterprise_id (không thay đổi gì)

        // Notify bên gửi
        $this->notifyRecipients($fromEnterprise->id, $event, $myEnterprise->name, 0, 'rejected', $data['rejection_reason']);

        return redirect()->route('transfer.pending')
            ->with('success', 'Đã từ chối lệnh chuyển giao. Bên gửi sẽ nhận được thông báo.');
    }

    // ════════════════════════════════════════════════════════════════
    // 4. storeTransferIn — Nhập từ bên ngoài hệ thống
    // ════════════════════════════════════════════════════════════════

    public function storeTransferIn(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.trace_events.create']), 403);

        $tenantId = $this->tenantId($request);

        $data = $request->validate([
            'external_party_name'   => 'required|string|max:255',
            'external_ref'          => 'nullable|string|max:255',
            'event_time'            => 'required|date',
            'who_name'              => 'required|string|max:255',
            'trace_location_id'     => 'nullable|exists:trace_locations,id',
            'kde_data'              => 'nullable|array',
            'note'                  => 'nullable|string|max:2000',
            'gs1_document_ref'      => 'nullable|string|max:100',
            'output_product_id'     => 'required|integer|exists:products,id',
            'output_quantity'       => 'required|numeric|min:0.001',
            'output_unit'           => 'required|string|max:50',
            'output_batch_type'     => 'required|in:raw_material,wip,finished',
            'output_production_date'=> 'nullable|date',
            'output_expiry_date'    => 'nullable|date|after_or_equal:output_production_date',
        ]);

        // Validate location thuộc tenant
        if (!empty($data['trace_location_id'])) {
            $loc = TraceLocation::find($data['trace_location_id']);
            abort_unless($loc && (int) $loc->enterprise_id === $tenantId, 403, 'Địa điểm không thuộc doanh nghiệp của bạn.');
        }

        // Validate product thuộc tenant
        $product = Product::where('id', $data['output_product_id'])
            ->where('enterprise_id', $tenantId)
            ->firstOrFail();

        $enterprise = Enterprise::findOrFail($tenantId);

        $batch = DB::transaction(function () use ($data, $tenantId, $enterprise, $product) {

            // 1. Tạo TraceEvent transfer_in (auto-accepted, không cần xác nhận)
            $event = TraceEvent::create([
                'enterprise_id'       => $tenantId,
                'event_category'      => TraceEvent::CAT_TRANSFER_IN,
                'event_code'          => TraceEvent::generateEventCode(
                    $enterprise->code, 'IMPORT', $this->nextEventSeq($tenantId)
                ),
                'event_token'         => (string) Str::uuid(),
                'cte_code'            => 'transfer_in',
                'event_type'          => 'transfer_in',
                'event_time'          => $data['event_time'],
                'who_name'            => $data['who_name'],
                'trace_location_id'   => $data['trace_location_id'] ?? null,
                'kde_data'            => $data['kde_data'] ?? null,
                'note'                => $data['note'] ?? null,
                'external_party_name' => $data['external_party_name'],
                'external_ref'        => $data['external_ref'] ?? null,
                'gs1_document_ref'    => $data['gs1_document_ref'] ?? null,
                'from_enterprise_id'  => null, // bên ngoài hệ thống
                'transfer_status'     => 'accepted',
                'accepted_at'         => now(),
                'status'              => 'draft',
            ]);

            // 2. Sinh lô output mới
            $batchCode = $this->generateBatchCode($data['output_batch_type'], $enterprise->code, $tenantId);

            $batch = Batch::create([
                'enterprise_id'    => $tenantId,
                'origin_event_id'  => $event->id,
                'product_id'       => $product->id,
                'product_name'     => $product->name,
                'batch_type'       => $data['output_batch_type'],
                'code'             => $batchCode,
                'status'           => 'active',
                'quantity'         => $data['output_quantity'],
                'current_quantity' => $data['output_quantity'],
                'unit'             => $data['output_unit'],
                'production_date'  => $data['output_production_date'] ?? null,
                'expiry_date'      => $data['output_expiry_date'] ?? null,
            ]);

            // 3. Cache GS1 labels
            $batch->load('product', 'enterprise');
            $gtin = $this->qrCodeService->resolveGtin($batch);

            $fullLabel = $this->gs1Service->buildFullLabel($batch, $event);

            $batch->update([
                'gtin_cached'   => $gtin,
                'gs1_128_label' => $fullLabel['gs1_128'],
            ]);

            // 4. Gắn vào pivot event_output_batches
            $event->outputBatches()->attach($batch->id, [
                'quantity' => $data['output_quantity'],
                'unit'     => $data['output_unit'],
            ]);

            // 5. Tạo QR codes
            $this->qrCodeService->ensureForBatch($tenantId, $batch->id);

            // 6. Publish ngay lên IPFS + Fabric (transfer_in từ ngoài → publish luôn)
            $this->publishSingleEvent($event, $batch, $enterprise);

            return $batch;
        });

        return redirect()->back()->with('success', "Đã nhập lô [{$batch->code}] thành công.");
    }

    // ════════════════════════════════════════════════════════════════
    // 5. pendingIndex — Inbox: danh sách transfer pending
    // ════════════════════════════════════════════════════════════════

    public function pendingIndex(): Response
    {
        abort_unless(auth()->user()->hasAnyPermission(['enterprise.trace_events.view', 'enterprise.trace_events.create', 'enterprise.trace_events.manage']), 403);

        $tenantId = (int) auth()->user()->enterprise_id;

        // Lệnh mình gửi đi đang chờ
        $outgoing = TraceEvent::where('enterprise_id', $tenantId)
            ->where('event_category', TraceEvent::CAT_TRANSFER_OUT)
            ->where('transfer_status', 'pending')
            ->with([
                'inputBatches:id,code,product_name,quantity,unit',
                'toEnterprise:id,name,code',
            ])
            ->latest()
            ->get()
            ->map(fn($e) => $this->formatEventSummary($e, 'outgoing'));

        // Lệnh người ta gửi cho mình
        $incoming = TraceEvent::withoutGlobalScopes()
            ->where('to_enterprise_id', $tenantId)
            ->where('event_category', TraceEvent::CAT_TRANSFER_OUT)
            ->where('transfer_status', 'pending')
            ->with([
                'inputBatches:id,code,product_name,quantity,unit',
                'enterprise:id,name,code',
            ])
            ->latest()
            ->get()
            ->map(fn($e) => $this->formatEventSummary($e, 'incoming'));

        return Inertia::render('Events/Transfer/Pending', [
            'outgoing' => $outgoing,
            'incoming' => $incoming,
        ]);
    }

    // ════════════════════════════════════════════════════════════════
    // Private helpers
    // ════════════════════════════════════════════════════════════════

    /**
     * Publish cả hai events (transfer_out, transfer_in) lên IPFS + Fabric.
     * Non-blocking: lỗi Fabric chỉ log, không throw.
     */
    private function publishTransferEvents(
        TraceEvent $outEvent,
        TraceEvent $inEvent,
        $batches,
        Enterprise $fromEnt,
        Enterprise $toEnt
    ): void {
        foreach ([$outEvent, $inEvent] as $event) {
            try {
                $payload     = $event->toIpfsPayload();
                $contentHash = hash('sha256', json_encode($payload));
                $ipfsResult  = $this->ipfsService->uploadJson($payload);

                $updateData = [
                    'status'       => 'published',
                    'content_hash' => $contentHash,
                    'published_at' => now(),
                    'published_by' => auth()->id(),
                ];

                if ($ipfsResult) {
                    $updateData['ipfs_cid'] = $ipfsResult['cid'];
                    $updateData['ipfs_url'] = $ipfsResult['url'];
                }

                $event->update($updateData);

                // Ghi lên Fabric (transferBatch)
                if ($ipfsResult) {
                    $batch = $event->inputBatches()->withoutGlobalScopes()->first();
                    $this->blockchainService->transferBatch(
                        transferID:     $event->event_code,
                        batchCode:      $batch?->code ?? '',
                        fromEnterprise: $fromEnt->code,
                        toEnterprise:   $toEnt->code,
                        invoiceNo:      $outEvent->gs1_document_ref ?? '',
                    );
                }
            } catch (\Throwable $e) {
                Log::error('Transfer event publish failed', [
                    'event_id' => $event->id,
                    'error'    => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Publish một event đơn lẻ (transfer_in từ bên ngoài).
     */
    private function publishSingleEvent(TraceEvent $event, Batch $batch, Enterprise $enterprise): void
    {
        try {
            $payload     = $event->toIpfsPayload();
            $contentHash = hash('sha256', json_encode($payload));
            $ipfsResult  = $this->ipfsService->uploadJson($payload);

            $updateData = [
                'status'       => 'published',
                'content_hash' => $contentHash,
                'published_at' => now(),
                'published_by' => auth()->id(),
            ];

            if ($ipfsResult) {
                $updateData['ipfs_cid'] = $ipfsResult['cid'];
                $updateData['ipfs_url'] = $ipfsResult['url'];
            }

            $event->update($updateData);

            if ($ipfsResult) {
                $this->blockchainService->recordEvent(
                    eventID:     $event->event_code,
                    batchCode:   $batch->code,
                    enterpriseID:$enterprise->code,
                    cteCode:     'transfer_in',
                    contentHash: $contentHash,
                    ipfsCid:     $ipfsResult['cid'],
                    recordedBy:  (string) auth()->id(),
                );
            }
        } catch (\Throwable $e) {
            Log::error('storeTransferIn publish failed', [
                'event_id' => $event->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    /**
     * Gửi database notification cho users của enterprise nhận.
     * Sử dụng Alert model (hoặc Laravel notification nếu có).
     */
    private function notifyRecipients(
        int    $enterpriseId,
        TraceEvent $event,
        string $actorName,
        int    $batchCount,
        string $action = 'pending',
        string $reason = ''
    ): void {
        try {
            $message = match ($action) {
                'accepted' => "{$actorName} đã xác nhận nhận {$batchCount} lô từ lệnh [{$event->event_code}].",
                'rejected' => "{$actorName} đã từ chối lệnh [{$event->event_code}]. Lý do: {$reason}",
                default    => "{$actorName} đã gửi lệnh chuyển giao {$batchCount} lô [{$event->event_code}]. Vui lòng xác nhận.",
            };

            // Lưu Alert cho enterprise nhận
            \App\Models\Alert::create([
                'enterprise_id' => $enterpriseId,
                'type'          => "transfer_{$action}",
                'title'         => 'Thông báo chuyển giao lô hàng',
                'message'       => $message,
                'data'          => ['event_id' => $event->id, 'event_code' => $event->event_code],
                'status'        => 'unread',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Transfer notification failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Format event summary cho Inertia response.
     */
    private function formatEventSummary(TraceEvent $event, string $direction): array
    {
        return [
            'id'              => $event->id,
            'event_code'      => $event->event_code,
            'event_time'      => $event->event_time?->format('d/m/Y H:i'),
            'who_name'        => $event->who_name,
            'transfer_status' => $event->transfer_status,
            'gs1_document_ref'=> $event->gs1_document_ref,
            'status'          => $event->status,
            'created_at'      => $event->created_at?->format('d/m/Y H:i'),
            'direction'       => $direction,
            'partner'         => $direction === 'outgoing'
                ? ['id' => $event->toEnterprise?->id,   'name' => $event->toEnterprise?->name,   'code' => $event->toEnterprise?->code]
                : ['id' => $event->enterprise?->id,     'name' => $event->enterprise?->name,     'code' => $event->enterprise?->code],
            'batches'         => $event->inputBatches->map(fn($b) => [
                'id'           => $b->id,
                'code'         => $b->code,
                'product_name' => $b->product_name,
                'quantity'     => $b->pivot->quantity,
                'unit'         => $b->pivot->unit,
            ]),
        ];
    }

    // ── seq helpers ───────────────────────────────────────

    private function nextEventSeq(int $tenantId): int
    {
        return DB::table('trace_events')
            ->where('enterprise_id', $tenantId)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count() + 1;
    }

    private function generateBatchCode(string $batchType, string $enterpriseCode, int $enterpriseId): string
    {
        $prefix = match ($batchType) {
            'raw_material' => 'NL',
            'wip'          => 'WIP',
            'finished'     => 'LOT',
            default        => 'BATCH',
        };

        $date = Carbon::now()->format('Ym');
        $seq  = $this->nextBatchSeq($enterpriseId);

        do {
            $seqStr = str_pad($seq, 3, '0', STR_PAD_LEFT);
            $code   = "{$prefix}-{$enterpriseCode}-{$date}-{$seqStr}";
            $seq++;
        } while (Batch::where('code', $code)->exists());

        return $code;
    }

    private function nextBatchSeq(int $enterpriseId): int
    {
        return DB::table('batches')
            ->where('enterprise_id', $enterpriseId)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count() + 1;
    }
}
