<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\BatchLineage;
use App\Models\Certificate;
use App\Models\Product;
use App\Models\TraceEvent;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BatchController extends Controller
{
    private function tenantId(Request $request): int
    {
        return (int) $request->user()->enterprise_id;
    }

    private const CATEGORY_PREFIX = [
        'lua_gao'      => 'LG',
        'rau_qua'      => 'RQ',
        'thuy_san'     => 'TS',
        'chan_nuoi'     => 'CN',
        'thuc_pham_cb' => 'TP',
        'khac'         => 'KH',
    ];

    private function generateBatchCode(int $tenantId, string $categoryCode): string
    {
        $prefix  = self::CATEGORY_PREFIX[$categoryCode] ?? 'KH';
        $entPart = str_pad($tenantId, 2, '0', STR_PAD_LEFT);
        $pattern = $prefix . $entPart . '%';
        $last = Batch::where('enterprise_id', $tenantId)->where('code', 'like', $pattern)->orderByDesc('code')->value('code');
        $seq = $last ? (intval(substr($last, -3)) + 1) : 1;
        return $prefix . $entPart . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    public function index(Request $request)
    {
        if (!$request->user()->hasAnyPermission(['enterprise.batches.view', 'enterprise.batches.manage'])) {
            return redirect()->route('dashboard')->with('error', 'Bạn không có quyền xem danh sách lô hàng.');
        }

        $tenantId  = $this->tenantId($request);
        $q         = $request->string('q')->toString();
        $productId = $request->query('product_id');

        $batches = Batch::with(['product:id,name,category_id', 'certificates:id,name,organization,status'])
            ->where('enterprise_id', $tenantId)
            ->when($q, fn($query) => $query->where(function ($sub) use ($q) {
                $sub->where('code', 'like', "%{$q}%")->orWhere('product_name', 'like', "%{$q}%");
            }))
            ->when($productId, fn($query) => $query->where('product_id', $productId))
            ->withCount('events')->latest()->paginate(10)->withQueryString();

        $products = Product::with('category:id,code')->where('enterprise_id', $tenantId)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'gtin', 'category_id'])
            ->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'gtin' => $p->gtin, 'category_id' => $p->category_id, 'category_code' => $p->category?->code]);

        $certificates = Certificate::where('enterprise_id', $tenantId)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'organization', 'certificate_no', 'expiry_date'])
            ->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'organization' => $c->organization, 'certificate_no' => $c->certificate_no, 'expiry_date' => $c->expiry_date?->format('d/m/Y')]);

        return Inertia::render('Batches/Index', [
            'batches'      => $batches,
            'products'     => $products,
            'certificates' => $certificates,
            'filters'      => ['q' => $q, 'product_id' => $productId],
        ]);
    }

    public function store(Request $request)
    {
        if (!$request->user()->hasAnyPermission(['enterprise.batches.manage'])) {
            return back()->with('error', 'Tài khoản không có quyền khởi tạo lô hàng.');
        }

        $tenantId = $this->tenantId($request);
        $data = $request->validate([
            'product_id'       => 'required|integer|exists:products,id',
            'description'      => 'nullable|string|max:2000',
            'production_date'  => 'nullable|date',
            'expiry_date'      => 'nullable|date|after_or_equal:production_date',
            'quantity'         => 'nullable|integer|min:1',
            'unit'             => 'nullable|string|max:50',
            'certificate_ids'   => 'nullable|array',
            'certificate_ids.*' => 'integer|exists:certificates,id',
        ]);

        $product = Product::with('category:id,code')->where('id', $data['product_id'])->where('enterprise_id', $tenantId)->first();
        if (!$product) return back()->withErrors(['product_id' => 'Sản phẩm không hợp lệ.']);

        $code = $this->generateBatchCode($tenantId, $product->category?->code ?? 'khac');
        $batch = Batch::create([
            'enterprise_id' => $tenantId, 'product_id' => $product->id, 'code' => $code, 'product_name' => $product->name,
            'description' => $data['description'] ?? null, 'production_date' => $data['production_date'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null, 'quantity' => $data['quantity'] ?? null,
            'current_quantity' => $data['quantity'] ?? null, 'unit' => $data['unit'] ?? $product->unit ?? null,
            'status' => 'active', 'batch_type' => 'original',
        ]);

        if (!empty($data['certificate_ids'])) {
            $validCertIds = Certificate::where('enterprise_id', $tenantId)->whereIn('id', $data['certificate_ids'])->pluck('id');
            $batch->certificates()->sync($validCertIds);
        }

        $product->load('processes');
        $enterpriseCode = \App\Models\Enterprise::find($tenantId)->code ?? 'ENT';
        foreach ($product->processes as $step) {
            $event = TraceEvent::create([
                'enterprise_id' => $tenantId, 'event_category' => TraceEvent::CAT_OBSERVATION,
                'event_code' => TraceEvent::generateEventCode($enterpriseCode, $step->cte_code ?? 'STEP', $this->nextEventSeq($tenantId)),
                'process_step_id' => $step->id, 'event_token' => (string) Str::uuid(), 'cte_code' => $step->cte_code ?? 'custom',
                'event_type' => $step->name_vi, 'status' => 'draft', 'kde_data' => ['step_name' => $step->name_vi, 'is_required' => $step->is_required],
            ]);
            $event->inputBatches()->attach($batch->id);
        }

        app(QrCodeService::class)->ensureForBatch($tenantId, $batch->id);
        return redirect()->route('batches.index')->with('success', "Đã tạo lô {$code}.");
    }

    public function update(Request $request, Batch $batch)
    {
        if (!$request->user()->hasAnyPermission(['enterprise.batches.manage'])) {
            return back()->with('error', 'Bạn không có quyền cập nhật thông tin lô hàng.');
        }
        $tenantId = $this->tenantId($request);
        abort_unless($batch->enterprise_id === $tenantId, 403);

        $data = $request->validate([
            'description' => 'nullable|string|max:2000', 'production_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:production_date', 'quantity' => 'nullable|integer|min:1',
            'unit' => 'nullable|string|max:50', 'certificate_ids' => 'nullable|array',
            'certificate_ids.*' => 'integer|exists:certificates,id',
        ]);

        $batch->update(['description' => $data['description'] ?? null, 'production_date' => $data['production_date'] ?? null, 'expiry_date' => $data['expiry_date'] ?? null, 'quantity' => $data['quantity'] ?? $batch->quantity, 'unit' => $data['unit'] ?? $batch->unit]);

        $certIds = !empty($data['certificate_ids']) ? Certificate::where('enterprise_id', $tenantId)->whereIn('id', $data['certificate_ids'])->pluck('id')->toArray() : [];
        $batch->certificates()->sync($certIds);

        return back()->with('success', 'Cập nhật lô thành công.');
    }

    public function destroy(Request $request, Batch $batch)
    {
        if (!$request->user()->hasAnyPermission(['enterprise.batches.manage'])) {
            return back()->with('error', 'Bạn không có quyền xóa lô hàng này.');
        }
        $tenantId = $this->tenantId($request);
        abort_unless($batch->enterprise_id === $tenantId, 403);

        if ($batch->events()->where('trace_events.status', 'published')->exists()) {
            return back()->withErrors(['error' => 'Không thể xóa lô đã có sự kiện published.']);
        }

        $batch->delete();
        return back()->with('success', 'Xóa lô thành công.');
    }

    public function lineage(Request $request, Batch $batch)
    {
        if (!$request->user()->hasAnyPermission(['enterprise.batches.view', 'enterprise.batches.manage'])) {
            return redirect()->route('batches.index')->with('error', 'Bạn không có quyền xem phả hệ lô hàng này.');
        }
        $tenantId = $this->tenantId($request);
        abort_unless($batch->enterprise_id === $tenantId, 403);

        $batch->load(['product:id,name,gtin,category_id', 'product.category:id,name_vi,icon', 'enterprise:id,name,code', 'originEnterprise:id,name,code', 'publishedEvents', 'childBatches.enterprise:id,name,code', 'childBatches.publishedEvents']);
        $ancestors = $batch->buildAncestors();
        $nodes = []; $edges = [];
        $this->flattenLineage($batch, $ancestors, $nodes, $edges, $tenantId);

        $children = BatchLineage::where('input_batch_id', $batch->id)->where('transformation_type', 'split')->with(['outputBatch.enterprise:id,name,code', 'outputBatch.publishedEvents'])->get();
        foreach ($children as $child) {
            $cb = $child->outputBatch; if (!$cb) continue;
            $cbId = 'batch-' . $cb->id; $currentId = 'batch-' . $batch->id;
            if (!isset($nodes[$cbId])) $nodes[$cbId] = $this->formatNode($cb, 'split_child');
            $edges[] = ['from' => $currentId, 'to' => $cbId, 'type' => 'split', 'label' => 'Tách → ' . ($child->quantity ?? '') . ' ' . ($child->unit ?? '')];
        }

        $mergedOutputs = BatchLineage::where('input_batch_id', $batch->id)->where('transformation_type', 'merge')->with(['outputBatch.enterprise:id,name,code', 'outputBatch.publishedEvents'])->get();
        foreach ($mergedOutputs as $mo) {
            $ob = $mo->outputBatch; if (!$ob) continue;
            $obId = 'batch-' . $ob->id; $currentId = 'batch-' . $batch->id;
            if (!isset($nodes[$obId])) $nodes[$obId] = $this->formatNode($ob, 'merge_output');
            $edges[] = ['from' => $currentId, 'to' => $obId, 'type' => 'merge', 'label' => 'Gộp → ' . ($mo->quantity ?? '') . ' ' . ($mo->unit ?? '')];
        }

        return Inertia::render('Batches/Lineage', ['batch' => $this->formatLineageBatch($batch), 'nodes' => array_values($nodes), 'edges' => $edges]);
    }

    private function nextEventSeq(int $tenantId): int
    {
        return \Illuminate\Support\Facades\DB::table('trace_events')->where('enterprise_id', $tenantId)->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count() + 1;
    }

    private function flattenLineage(Batch $currentBatch, array $ancestors, array &$nodes, array &$edges, int $tenantId): void
    {
        $currentId = 'batch-' . $currentBatch->id;
        if (!isset($nodes[$currentId])) {
            $isOwn = $currentBatch->enterprise_id === $tenantId;
            $nodes[$currentId] = $this->formatNode($currentBatch, $isOwn ? 'current' : 'external');
        }
        foreach ($ancestors as $ancestor) {
            $rel = $ancestor['relation'];
            if ($rel === 'split_from' && isset($ancestor['batch'])) {
                $parentBatch = $ancestor['batch']; $parentId = 'batch-' . $parentBatch->id;
                if (!isset($nodes[$parentId])) $nodes[$parentId] = $this->formatNode($parentBatch, 'parent');
                $edges[] = ['from' => $parentId, 'to' => $currentId, 'type' => 'split', 'label' => 'Tách lô'];
                if (!empty($ancestor['ancestors'])) $this->flattenLineage($parentBatch, $ancestor['ancestors'], $nodes, $edges, $tenantId);
            } elseif ($rel === 'merged_from' && isset($ancestor['batch'])) {
                $inputBatch = $ancestor['batch']; $inputId = 'batch-' . $inputBatch->id;
                if (!isset($nodes[$inputId])) $nodes[$inputId] = $this->formatNode($inputBatch, 'merge_input');
                $qty = isset($ancestor['quantity']) ? $ancestor['quantity'] . ' ' . ($ancestor['unit'] ?? '') : '';
                $edges[] = ['from' => $inputId, 'to' => $currentId, 'type' => 'merge', 'label' => 'Gộp' . ($qty ? " ({$qty})" : '')];
                if (!empty($ancestor['ancestors'])) $this->flattenLineage($inputBatch, $ancestor['ancestors'], $nodes, $edges, $tenantId);
            } elseif ($rel === 'received_from' && isset($ancestor['transfer'])) {
                $transfer = $ancestor['transfer']; $fromEnt = $transfer->fromEnterprise; $fromId = 'enterprise-' . ($fromEnt?->id ?? 'unknown');
                if (!isset($nodes[$fromId])) $nodes[$nodes[$fromId] = ['id' => $fromId, 'type' => 'enterprise', 'label' => $fromEnt?->name ?? 'DN không xác định', 'code' => $fromEnt?->code, 'batch' => null]];
                $edges[] = ['from' => $fromId, 'to' => $currentId, 'type' => 'transfer', 'label' => 'Chuyển giao · ' . ($transfer->quantity ?? '') . ' ' . ($transfer->unit ?? '')];
            }
        }
    }

    private function formatNode(Batch $batch, string $type): array
    {
        return ['id' => 'batch-' . $batch->id, 'type' => $type, 'batch_id' => $batch->id, 'code' => $batch->code, 'product_name' => $batch->product?->name ?? $batch->product_name ?? '—', 'batch_type' => $batch->batch_type, 'status' => $batch->status, 'enterprise' => $batch->enterprise?->name, 'enterprise_code' => $batch->enterprise?->code, 'event_count' => $batch->publishedEvents?->count() ?? 0, 'quantity' => $batch->current_quantity ?? $batch->quantity, 'unit' => $batch->unit];
    }

    private function formatLineageBatch(Batch $batch): array
    {
        return ['id' => $batch->id, 'code' => $batch->code, 'product_name' => $batch->product?->name ?? $batch->product_name ?? '—', 'batch_type' => $batch->batch_type, 'status' => $batch->status, 'enterprise' => $batch->enterprise?->name, 'enterprise_code' => $batch->enterprise?->code, 'product' => $batch->product ? ['name' => $batch->product->name, 'gtin' => $batch->product->gtin] : null];
    }
}
