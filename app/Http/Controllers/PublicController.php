<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PublicController extends Controller
{
    public function home()
    {
        $categories = DB::table('product_categories')
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name_vi', 'icon', 'tcvn_ref']);

        // Lay so luong san pham co the truy xuat duoc theo tung category
        $countByCategory = Product::whereHas('batches', fn($q) =>
                $q->whereHas('inputEvents', fn($eq) => $eq->where('trace_events.status', 'published'))
            )
            ->select('category_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('category_id')
            ->pluck('cnt', 'category_id');

        $categories = $categories->map(fn($c) => array_merge(
            (array) $c,
            ['product_count' => (int) ($countByCategory->get($c->id) ?? 0)]
        ));

        return Inertia::render('Public/Home', [
            'categories' => $categories,
        ]);
    }

    public function categories()
    {
        return redirect()->route('public.products');
    }

    // Trang danh muc san pham - co the browse + filter
    public function products(Request $request)
    {
        $search     = trim($request->query('q', ''));
        $categoryId = $request->query('category_id');
        $province   = $request->query('province');

        // Chi hien san pham co it nhat 1 lo da published event
        $products = Product::with([
                'category:id,name_vi,icon,code',
                'enterprise:id,name,province',
            ])
            ->where('status', 'active')
            ->whereHas('batches', fn($q) =>
                $q->whereHas('inputEvents', fn($eq) => $eq->where('trace_events.status', 'published'))
            )
            ->when($search, fn($q) =>
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('gtin', 'like', "%{$search}%")
                        ->orWhereHas('enterprise', fn($eq) =>
                            $eq->where('name', 'like', "%{$search}%")
                        );
                })
            )
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->when($province, fn($q) =>
                $q->whereHas('enterprise', fn($eq) => $eq->where('province', $province))
            )
            ->paginate(12)
            ->withQueryString();

        $products->through(fn($p) => [
            'id'          => $p->id,
            'name'        => $p->name,
            'gtin'        => $p->gtin,
            'description' => $p->description,
            'image_path'  => $p->image_path,
            'unit'        => $p->unit,
            'category'    => $p->category ? [
                'id'      => $p->category->id,
                'name_vi' => $p->category->name_vi,
                'icon'    => $p->category->icon,
                'code'    => $p->category->code,
            ] : null,
            'enterprise'  => $p->enterprise ? [
                'name'     => $p->enterprise->name,
                'province' => $p->enterprise->province,
            ] : null,
        ]);

        // Filter options
        $categories = DB::table('product_categories')->orderBy('sort_order')->get(['id', 'name_vi', 'icon']);

        $provinces = Product::with('enterprise:id,province')
            ->whereHas('batches', fn($q) =>
                $q->whereHas('inputEvents', fn($eq) => $eq->where('trace_events.status', 'published'))
            )
            ->join('enterprises', 'products.enterprise_id', '=', 'enterprises.id')
            ->whereNotNull('enterprises.province')
            ->distinct()
            ->pluck('enterprises.province')
            ->sort()
            ->values();

        return Inertia::render('Public/Products', [
            'products'   => $products,
            'categories' => $categories,
            'provinces'  => $provinces,
            'filters'    => [
                'q'           => $search,
                'category_id' => $categoryId,
                'province'    => $province,
            ],
        ]);
    }

    public function verify(Request $request)
    {
        $query   = trim($request->query('query', ''));
        $results = [];
        $error   = null;

        if ($query !== '') {
            $batches = Batch::with([
                'product:id,name,gtin,description,image_path,category_id,unit',
                'product.category:id,name_vi,icon,code',
                'enterprise:id,name,code,province,district',
            ])
                ->where(function ($q) use ($query) {
                    $q->where('code', 'like', "%{$query}%")
                      ->orWhereHas('product', fn($pq) => $pq->where('gtin', $query));
                })
                ->whereHas('inputEvents', fn($eq) => $eq->where('trace_events.status', 'published'))
                ->limit(10)
                ->get();

            if ($batches->isEmpty()) {
                $error = 'Khong tim thay ket qua cho "' . $query . '". Vui long kiem tra lai ma lo hoac GTIN.';
            }

            $batchIds    = $batches->pluck('id');
            $eventCounts = \App\Models\TraceEvent::whereHas(
                    'inputBatches', fn($q) => $q->whereIn('batches.id', $batchIds->toArray())
                )
                ->where('status', 'published')
                ->join('event_input_batches', 'trace_events.id', '=', 'event_input_batches.trace_event_id')
                ->select('event_input_batches.batch_id', DB::raw('COUNT(*) as cnt'))
                ->groupBy('event_input_batches.batch_id')
                ->pluck('cnt', 'batch_id');

            $results = $batches->map(fn($b) => [
                'id'              => $b->id,
                'code'            => $b->code,
                'status'          => $b->status,
                'production_date' => optional($b->production_date)->format('d/m/Y'),
                'expiry_date'     => optional($b->expiry_date)->format('d/m/Y'),
                'quantity'        => $b->quantity,
                'unit'            => $b->unit ?? $b->product?->unit,
                'product'         => $b->product ? [
                    'name'        => $b->product->name,
                    'gtin'        => $b->product->gtin,
                    'description' => $b->product->description,
                    'image_path'  => $b->product->image_path,
                    'category'    => $b->product->category ? [
                        'name_vi' => $b->product->category->name_vi,
                        'icon'    => $b->product->category->icon,
                    ] : null,
                ] : null,
                'enterprise'      => $b->enterprise ? [
                    'name'     => $b->enterprise->name,
                    'province' => $b->enterprise->province,
                    'district' => $b->enterprise->district,
                ] : null,
                'event_count'     => (int) ($eventCounts->get($b->id) ?? 0),
            ]);
        }

        return Inertia::render('Public/Verify', [
            'query'   => $query,
            'results' => $results,
            'error'   => $error,
        ]);
    }
}