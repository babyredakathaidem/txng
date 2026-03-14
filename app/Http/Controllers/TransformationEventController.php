<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\BatchLineage;
use App\Models\Product;
use App\Models\TraceEvent;
use App\Models\TraceLocation;
use App\Services\GS1Service;
use App\Services\QrCodeService;
use App\Traits\HasTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TransformationEventController extends Controller
{
    use HasTenant;

    public function __construct(
        private QrCodeService $qrService,
        private GS1Service    $gs1Service,
    ) {}

    // ── create ────────────────────────────────────────────
    public function create(\Illuminate\Http\Request $request): Response
    {
        if (!$request->user()->hasAnyPermission(['enterprise.trace_events.create'])) {
            return redirect()->route('dashboard')->with('error', 'Bạn không có quyền khởi tạo sự kiện chế biến.');
        }

        $tenantId = $this->tenantId($request);

        return Inertia::render('Events/CreateTransformation', [
            'batches' => Batch::where('enterprise_id', $tenantId)
                ->where('status', 'active')
                ->with('enterprise:id,name')
                ->get(['id', 'code', 'enterprise_id', 'product_name', 'current_quantity', 'unit', 'status']),

            'locations' => \App\Models\TraceLocation::where('enterprise_id', $tenantId)
                ->get(['id', 'name', 'gln', 'ai_type']),

            'products' => Product::where('enterprise_id', $tenantId)
                ->get(['id', 'name', 'gtin']),

            'cte_options' => [],  // frontend dùng DEFAULT_CTE
        ]);
    }

    // ── store ─────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        if (!$request->user()->hasAnyPermission(['enterprise.trace_events.create'])) {
            return back()->with('error', 'Tài khoản không có quyền lưu trữ sự kiện chế biến.');
        }

        $tenantId = $this->tenantId($request);

        $data = $request->validate([
            'cte_code'              => 'required|string|max:60',
            'event_time'            => 'required|date',
            'who_name'              => 'required|string|max:255',
            'trace_location_id'     => 'nullable|exists:trace_locations,id',
            'kde_data'              => 'nullable|array',
            'note'                  => 'nullable|string|max:2000',
            // Input batches: mảng object {id, quantity?, unit?}
            'input_batches'         => 'required|array|min:1',
            'input_batches.*.id'    => 'required|integer|exists:batches,id',
            'input_batches.*.quantity' => 'nullable|numeric|min:0.001',
            'input_batches.*.unit'  => 'nullable|string|max:50',
            // Output batch
            'output_product_id'     => 'required|integer|exists:products,id',
            'output_quantity'       => 'required|numeric|min:0.001',
            'output_unit'           => 'required|string|max:50',
            'output_batch_type'     => 'required|in:raw_material,wip,finished',
            'output_production_date'=> 'nullable|date',
            'output_expiry_date'    => 'nullable|date|after_or_equal:output_production_date',
        ]);

        // Validate trace_location thuộc tenant
        if (!empty($data['trace_location_id'])) {
            $loc = TraceLocation::find($data['trace_location_id']);
            abort_unless($loc && (int) $loc->enterprise_id === $tenantId, 403, 'Địa điểm không thuộc doanh nghiệp của bạn.');
        }

        // Validate output product thuộc tenant
        $outputProduct = Product::where('id', $data['output_product_id'])
            ->where('enterprise_id', $tenantId)
            ->with('category:id,code')
            ->firstOrFail();

        // Lấy tất cả input batch IDs
        $inputBatchIds = collect($data['input_batches'])->pluck('id')->all();

        // Load và validate input batches
        $inputBatches = Batch::whereIn('id', $inputBatchIds)
            ->where('enterprise_id', $tenantId)
            ->where('status', 'active')
            ->get()
            ->keyBy('id');

        if ($inputBatches->count() !== count($inputBatchIds)) {
            return back()->withErrors([
                'input_batches' => 'Một số lô không hợp lệ, không thuộc doanh nghiệp, hoặc không ở trạng thái active.',
            ]);
        }

        $outputBatch = DB::transaction(function () use ($data, $tenantId, $inputBatches, $outputProduct) {

            // a. Tạo event_code
            $eventCode = $this->generateEventCode($tenantId, $data['cte_code']);

            // b. Tạo TraceEvent
            $event = TraceEvent::create([
                'enterprise_id'    => $tenantId,
                'event_category'   => TraceEvent::CAT_TRANSFORMATION,
                'event_code'       => $eventCode,
                'event_token'      => (string) Str::uuid(),
                'cte_code'         => $data['cte_code'],
                'event_type'       => $data['cte_code'],
                'event_time'       => $data['event_time'],
                'trace_location_id'=> $data['trace_location_id'] ?? null,
                'who_name'         => $data['who_name'],
                'kde_data'         => $data['kde_data'] ?? null,
                'note'             => $data['note'] ?? null,
                'status'           => 'draft',
            ]);

            // c. Gắn input batches vào pivot event_input_batches
            foreach ($data['input_batches'] as $item) {
                $batch = $inputBatches->get($item['id']);
                $event->inputBatches()->attach($batch->id, [
                    'quantity' => $item['quantity'] ?? $batch->current_quantity ?? $batch->quantity,
                    'unit'     => $item['unit']     ?? $batch->unit,
                ]);
            }

            // d. Consume input batches (irreversible sau khi published)
            foreach ($inputBatches as $batch) {
                $batch->update([
                    'status'           => 'consumed',
                    'current_quantity' => 0,
                ]);
            }

            // e. Sinh lô output mới
            $outputCode = $this->generateBatchCode(
                $tenantId,
                $outputProduct->category?->code ?? 'khac'
            );

            $outputBatch = Batch::create([
                'enterprise_id'    => $tenantId,
                'product_id'       => $outputProduct->id,
                'product_name'     => $outputProduct->name,
                'code'             => $outputCode,
                'batch_type'       => $data['output_batch_type'],
                'quantity'         => $data['output_quantity'],
                'current_quantity' => $data['output_quantity'],
                'unit'             => $data['output_unit'],
                'production_date'  => $data['output_production_date'] ?? null,
                'expiry_date'      => $data['output_expiry_date'] ?? null,
                'origin_event_id'  => $event->id,
                'status'           => 'active',
            ]);

            // Load relation cần thiết để cache GS1
            $outputBatch->load('product', 'enterprise');
            $gtin = $this->qrService->resolveGtin($outputBatch);
            
            // Lấy event để buildFullLabel (lấy AI 251 và location GLN)
            // Vì event chưa được lưu relation với batch mới tạo, ta pass explicit event vào GS1Service
            $fullLabel = $this->gs1Service->buildFullLabel($outputBatch, $event);

            $outputBatch->update([
                'gtin_cached'   => $gtin,
                'gs1_128_label' => $fullLabel['gs1_128'],
            ]);

            // f. Gắn output batch vào pivot event_output_batches
            $event->outputBatches()->attach($outputBatch->id, [
                'quantity' => $data['output_quantity'],
                'unit'     => $data['output_unit'],
            ]);

            // g. Tạo BatchLineage records
            $transformationType = $this->resolveTransformationType($data['cte_code']);
            foreach ($inputBatches as $inputBatch) {
                BatchLineage::create([
                    'transformation_type' => $transformationType,
                    'input_batch_id'      => $inputBatch->id,
                    'output_batch_id'     => $outputBatch->id,
                    'quantity'            => $inputBatch->quantity,
                    'unit'                => $inputBatch->unit,
                    'event_id'            => $event->id,
                ]);
            }

            // h. Tạo QR codes cho lô output
            $this->qrService->ensureForBatch($tenantId, $outputBatch->id);

            return $outputBatch;
        });

        return redirect()->route('batches.index')->with(
            'success',
            "Đã tạo sự kiện + lô [{$outputBatch->code}]. Vui lòng điền đủ dữ liệu và publish."
        );
    }

    // ── show ──────────────────────────────────────────────

    public function show(Request $request, TraceEvent $event): Response
    {
        if (!$request->user()->hasAnyPermission(['enterprise.trace_events.view', 'enterprise.trace_events.manage'])) {
            return redirect()->route('events.index')->with('error', 'Bạn không có quyền xem chi tiết sự kiện này.');
        }
        $this->assertTenant($request, $event);

        $event->load([
            'inputBatches:id,code,product_id,product_name,quantity,unit,status,batch_type',
            'inputBatches.product:id,name,gtin',
            'inputBatches.inputEvents:id,cte_code,event_time,status',
            'outputBatches:id,code,product_id,product_name,quantity,unit,status,batch_type',
            'outputBatches.product:id,name,gtin',
            'traceLocation:id,name,address_detail,province,lat,lng,gln,ai_type',
            'publisher:id,name',
        ]);

        $lineage = BatchLineage::where('event_id', $event->id)
            ->with([
                'inputBatch:id,code,product_name,quantity,unit',
                'outputBatch:id,code,product_name,quantity,unit',
            ])
            ->get();

        return Inertia::render('TransformationEvents/Show', [
            'event'    => $this->formatEvent($event),
            'lineage'  => $lineage,
        ]);
    }

    // ── destroy ───────────────────────────────────────────

    public function destroy(Request $request, TraceEvent $event): RedirectResponse
    {
        if (!$request->user()->hasAnyPermission(['enterprise.trace_events.manage'])) {
            return back()->with('error', 'Chỉ quản trị viên mới có quyền hủy sự kiện chế biến.');
        }
        $this->assertTenant($request, $event);

        if ($event->status !== 'draft') {
            return back()->withErrors(['error' => 'Không thể hủy sự kiện đã publish.']);
        }

        DB::transaction(function () use ($event) {
            // 1. Khôi phục trạng thái input batches về 'active'
            $inputBatches = $event->inputBatches()->withoutGlobalScopes()->get();
            foreach ($inputBatches as $batch) {
                // Lấy lại quantity gốc từ pivot
                $origQty = $batch->pivot->quantity ?? $batch->quantity;
                $batch->update([
                    'status'           => 'active',
                    'current_quantity' => $origQty,
                ]);
            }

            // 2. Lấy output batches trước khi detach
            $outputBatches = $event->outputBatches()->withoutGlobalScopes()->get();

            // 3. Detach pivot tables
            $event->inputBatches()->detach();
            $event->outputBatches()->detach();

            // 4. Xóa BatchLineage records
            BatchLineage::where('event_id', $event->id)->delete();

            // 5. Xóa output batches (sinh ra bởi event này)
            foreach ($outputBatches as $ob) {
                // Xóa QR codes liên quan
                $ob->qrcodes()->delete();
                $ob->delete();
            }

            // 6. Xóa event
            $event->delete();
        });

        return redirect()->route('batches.index')
            ->with('success', 'Đã hủy sự kiện chế biến. Các lô đầu vào đã được khôi phục về trạng thái active.');
    }

    // ── Private helpers ───────────────────────────────────

    /**
     * Sinh event_code: EVT-{ENT_CODE}-{CTE_7}-{YYYYMM}-{SEQ3}
     */
    private function generateEventCode(int $tenantId, string $cteCode): string
    {
        $yearMonth = now()->format('Ym');
        $cteUpper  = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $cteCode), 0, 7));

        $enterpriseCode = DB::table('enterprises')->where('id', $tenantId)->value('code') ?? 'UNK';
        $prefix    = "EVT-{$enterpriseCode}-{$cteUpper}-{$yearMonth}-";

        $last = DB::table('trace_events')
            ->where('enterprise_id', $tenantId)
            ->where('event_code', 'like', $prefix . '%')
            ->orderByDesc('event_code')
            ->value('event_code');

        $seq = $last ? ((int) substr($last, strrpos($last, '-') + 1) + 1) : 1;

        return $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Sinh mã lô output: {PREFIX}{enterpriseId 2 chữ số}{sequence 3 chữ số}
     * Dùng cùng logic với BatchController.
     */
    private function generateBatchCode(int $tenantId, string $categoryCode): string
    {
        $prefixMap = [
            'lua_gao'      => 'LG',
            'rau_qua'      => 'RQ',
            'thuy_san'     => 'TS',
            'chan_nuoi'     => 'CN',
            'thuc_pham_cb' => 'TP',
            'khac'         => 'KH',
        ];

        $prefix  = $prefixMap[$categoryCode] ?? 'KH';
        $entPart = str_pad($tenantId, 2, '0', STR_PAD_LEFT);
        $pattern = $prefix . $entPart . '%';

        $last = Batch::where('enterprise_id', $tenantId)
            ->where('code', 'like', $pattern)
            ->orderByDesc('code')
            ->value('code');

        $seq = $last ? (intval(substr($last, -3)) + 1) : 1;

        return $prefix . $entPart . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Ánh xạ cte_code → transformation_type cho BatchLineage.
     * Giữ tương thích với batch_lineage.transformation_type enum.
     */
    private function resolveTransformationType(string $cteCode): string
    {
        return match ($cteCode) {
            'split'          => 'split',
            'merge', 'blend' => 'merge',
            default          => 'transformation',
        };
    }

    /**
     * Format event + relations cho Inertia response.
     */
    private function formatEvent(TraceEvent $event): array
    {
        return [
            'id'            => $event->id,
            'event_code'    => $event->event_code,
            'event_token'   => $event->event_token,
            'cte_code'      => $event->cte_code,
            'event_category'=> $event->event_category,
            'event_time'    => $event->event_time?->format('d/m/Y H:i'),
            'who_name'      => $event->who_name,
            'kde_data'      => $event->kde_data,
            'note'          => $event->note ?? null,
            'status'        => $event->status,
            'ipfs_cid'      => $event->ipfs_cid,
            'content_hash'  => $event->content_hash,
            'published_at'  => $event->published_at?->format('d/m/Y H:i'),
            'publisher'     => $event->publisher?->name,
            'location'      => $event->traceLocation ? [
                'id'       => $event->traceLocation->id,
                'name'     => $event->traceLocation->name,
                'address'  => $event->traceLocation->address_detail,
                'province' => $event->traceLocation->province,
                'gln'      => $event->traceLocation->gln,
                'lat'      => $event->traceLocation->lat,
                'lng'      => $event->traceLocation->lng,
            ] : null,
            'input_batches' => $event->inputBatches->map(fn($b) => [
                'id'           => $b->id,
                'code'         => $b->code,
                'product_name' => $b->product?->name ?? $b->product_name,
                'gtin'         => $b->product?->gtin,
                'quantity'     => $b->pivot->quantity,
                'unit'         => $b->pivot->unit,
                'status'       => $b->status,
                'batch_type'   => $b->batch_type,
                'events'       => $b->inputEvents->map(fn($e) => [
                    'id'         => $e->id,
                    'cte_code'   => $e->cte_code,
                    'event_time' => optional($e->event_time)->format('d/m/Y H:i'),
                    'status'     => $e->status,
                ]),
            ]),
            'output_batches' => $event->outputBatches->map(fn($b) => [
                'id'           => $b->id,
                'code'         => $b->code,
                'product_name' => $b->product?->name ?? $b->product_name,
                'gtin'         => $b->product?->gtin,
                'quantity'     => $b->pivot->quantity,
                'unit'         => $b->pivot->unit,
                'status'       => $b->status,
                'batch_type'   => $b->batch_type,
            ]),
        ];
    }
}
