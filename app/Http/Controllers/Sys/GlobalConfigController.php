<?php

namespace App\Http\Controllers\Sys;

use App\Http\Controllers\Controller;
use App\Models\CteTemplate;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GlobalConfigController extends Controller
{
    /**
     * Dashboard cấu hình hệ thống
     */
    public function index()
    {
        return Inertia::render('Sys/Config/Index', [
            'categories' => ProductCategory::withCount('batches')->get(),
            'cte_templates' => CteTemplate::with('category')->get(),
        ]);
    }

    /**
     * Cập nhật KDE Schema cho một CTE Template (Cái này cực gắt)
     */
    public function updateCte(Request $request, CteTemplate $template)
    {
        $data = $request->validate([
            'name_vi'     => 'required|string|max:255',
            'is_required' => 'boolean',
            'kde_schema'  => 'required|array', // JSON chứa WHO/WHAT/WHERE/WHEN/WHY
            'tcvn_note'   => 'nullable|string',
        ]);

        $template->update($data);

        return back()->with('success', 'Đã cập nhật cấu trúc sự kiện chuẩn.');
    }

    /**
     * Thêm loại sản phẩm mới và TCVN áp dụng
     */
    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'code'     => 'required|string|unique:product_categories,code',
            'name_vi'  => 'required|string|max:255',
            'tcvn_ref' => 'nullable|string',
        ]);

        ProductCategory::create($data);

        return back()->with('success', 'Đã thêm danh mục sản phẩm mới.');
    }

    /**
     * Thống kê toàn hệ thống cho Superadmin (Nhiệm vụ của Thông tư 02)
     */
    public function systemStats()
    {
        return Inertia::render('Sys/Stats', [
            'total_enterprises' => \App\Models\Enterprise::count(),
            'total_batches'     => \App\Models\Batch::count(),
            'total_events'      => \App\Models\TraceEvent::count(),
            'recent_recalls'    => \App\Models\BatchRecall::with('batch')->latest()->take(5)->get(),
        ]);
    }
}
