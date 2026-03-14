<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductProcess;
use App\Traits\HasTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductProcessController extends Controller
{
    use HasTenant;

    // ── Lấy danh sách bước của sản phẩm ──────────────────
    public function index(Request $request, Product $product)
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.products.view', 'enterprise.products.manage']), 403);
        $tenantId = $this->tenantId($request);
        abort_unless($product->enterprise_id === $tenantId, 403);

        $steps = $product->processes()->get(['id', 'step_order', 'name_vi', 'cte_code', 'description', 'is_required']);

        return response()->json($steps);
    }

    // ── Lưu toàn bộ danh sách bước (đồng bộ full: upsert + delete) ──
    public function sync(Request $request, Product $product)
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.products.manage']), 403);
        $tenantId = $this->tenantId($request);
        abort_unless($product->enterprise_id === $tenantId, 403);

        $data = $request->validate([
            'steps'                   => 'required|array',
            'steps.*.id'              => 'nullable|integer',
            'steps.*.step_order'      => 'required|integer|min:1',
            'steps.*.name_vi'         => 'required|string|max:100',
            'steps.*.cte_code'        => 'nullable|string|max:50',
            'steps.*.description'     => 'nullable|string|max:500',
            'steps.*.is_required'     => 'boolean',
        ]);

        DB::transaction(function () use ($data, $product, $tenantId) {
            $incomingIds = collect($data['steps'])->pluck('id')->filter()->all();

            // Xóa các bước không còn trong danh sách
            ProductProcess::where('product_id', $product->id)
                ->where('enterprise_id', $tenantId)
                ->when(!empty($incomingIds), fn($q) => $q->whereNotIn('id', $incomingIds))
                ->delete();

            // Upsert từng bước
            foreach ($data['steps'] as $step) {
                ProductProcess::updateOrCreate(
                    ['id' => $step['id'] ?? null],
                    [
                        'product_id'    => $product->id,
                        'enterprise_id' => $tenantId,
                        'step_order'    => $step['step_order'],
                        'name_vi'       => $step['name_vi'],
                        'cte_code'      => $step['cte_code'] ?? null,
                        'description'   => $step['description'] ?? null,
                        'is_required'   => $step['is_required'] ?? true,
                    ]
                );
            }
        });

        return response()->json([
            'message' => 'Đã lưu quy trình sản xuất thành công.',
            'steps'   => $product->fresh()->processes()->get(),
        ]);
    }

    // ── Xóa một bước ─────────────────────────────────────
    public function destroy(Request $request, Product $product, ProductProcess $step)
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.products.manage']), 403);
        $tenantId = $this->tenantId($request);
        abort_unless($product->enterprise_id === $tenantId, 403);
        abort_unless($step->product_id === $product->id, 403);

        $step->delete();

        return response()->json(['message' => 'Đã xóa bước thành công.']);
    }
}
