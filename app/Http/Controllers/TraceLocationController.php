<?php

namespace App\Http\Controllers;

use App\Models\TraceLocation;
use App\Services\GS1Service;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TraceLocationController extends Controller
{
    private function tenantId(Request $request): int
    {
        return (int) $request->user()->enterprise_id;
    }

    /**
     * Danh sách địa điểm của doanh nghiệp.
     * Có thể lọc theo ai_type (410-417).
     */
    public function index(Request $request)
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.locations.view', 'enterprise.locations.manage']), 403);

        $tenantId = $this->tenantId($request);
        $aiType   = $request->string('ai_type')->toString();
        $q        = $request->string('q')->toString();

        $locations = TraceLocation::where('enterprise_id', $tenantId)
            ->when($aiType, fn($qry) => $qry->where('ai_type', $aiType))
            ->when($q, fn($qry) => $qry->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('address_detail', 'like', "%{$q}%")
                    ->orWhere('farm_code', 'like', "%{$q}%");
            }))
            ->orderBy('ai_type')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('TraceLocation/Index', [
            'locations' => $locations,
            'ai_labels' => TraceLocation::AI_LABELS,
            'filters'   => ['ai_type' => $aiType, 'q' => $q],
        ]);
    }

    /**
     * Tạo địa điểm mới.
     * GLN tự động sinh nếu không nhập (dùng GS1Service::generateGLN theo enterprise_id + location_id).
     */
    public function store(Request $request)
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.locations.manage']), 403);
        $tenantId = $this->tenantId($request);

        $data = $request->validate([
            'ai_type'        => 'required|in:410,411,412,414,416,417',
            'name'           => 'required|string|max:255',
            'code'           => 'nullable|string|max:50',
            'gln'            => 'nullable|string|max:13|regex:/^\d{13}$/',
            'province'       => 'nullable|string|max:100',
            'district'       => 'nullable|string|max:100',
            'address_detail' => 'nullable|string|max:500',
            'lat'            => 'nullable|numeric|between:-90,90',
            'lng'            => 'nullable|numeric|between:-180,180',
            // Vùng trồng / sản xuất (AI 416)
            'area_ha'        => 'nullable|numeric|min:0',
            'farm_code'      => 'nullable|string|max:100',
            'product_types'  => 'nullable|string|max:500',
            'note'           => 'nullable|string|max:2000',
            'status'         => 'required|in:active,inactive',
        ]);

        // Validate GLN check digit nếu có nhập
        if (! empty($data['gln'])) {
            if (! \App\Services\GS1Validator::isValid($data['gln'])) {
                return back()->withErrors(['gln' => 'GLN không hợp lệ — check digit sai.']);
            }
        }

        TraceLocation::create(array_merge($data, ['enterprise_id' => $tenantId]));

        return back()->with('success', 'Đã thêm địa điểm truy vết.');
    }

    /**
     * Cập nhật địa điểm.
     */
    public function update(Request $request, TraceLocation $traceLocation)
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.locations.manage']), 403);
        abort_unless($traceLocation->enterprise_id === $this->tenantId($request), 403);

        $data = $request->validate([
            'ai_type'        => 'required|in:410,411,412,414,416,417',
            'name'           => 'required|string|max:255',
            'code'           => 'nullable|string|max:50',
            'gln'            => 'nullable|string|max:13|regex:/^\d{13}$/',
            'province'       => 'nullable|string|max:100',
            'district'       => 'nullable|string|max:100',
            'address_detail' => 'nullable|string|max:500',
            'lat'            => 'nullable|numeric|between:-90,90',
            'lng'            => 'nullable|numeric|between:-180,180',
            'area_ha'        => 'nullable|numeric|min:0',
            'farm_code'      => 'nullable|string|max:100',
            'product_types'  => 'nullable|string|max:500',
            'note'           => 'nullable|string|max:2000',
            'status'         => 'required|in:active,inactive',
        ]);

        if (! empty($data['gln']) && ! \App\Services\GS1Validator::isValid($data['gln'])) {
            return back()->withErrors(['gln' => 'GLN không hợp lệ — check digit sai.']);
        }

        $traceLocation->update($data);

        return back()->with('success', 'Đã cập nhật địa điểm.');
    }

    /**
     * Xóa địa điểm — chỉ xóa được nếu chưa gắn với event nào.
     */
    public function destroy(Request $request, TraceLocation $traceLocation)
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.locations.manage']), 403);
        abort_unless($traceLocation->enterprise_id === $this->tenantId($request), 403);

        if ($traceLocation->traceEvents()->exists()) {
            return back()->withErrors([
                'location' => 'Không thể xóa địa điểm đã được gắn vào sự kiện truy vết.',
            ]);
        }

        $traceLocation->delete();
        return back()->with('success', 'Đã xóa địa điểm.');
    }

    /**
     * API endpoint — trả JSON danh sách địa điểm active của tenant.
     * Dùng trong dropdown khi tạo TraceEvent.
     *
     * Query params:
     *   ?ai_type=416    — lọc theo loại AI
     *   ?q=tên          — tìm kiếm
     */
    public function listForEvent(Request $request)
    {
        $tenantId = $this->tenantId($request);
        $aiType   = $request->string('ai_type')->toString();
        $q        = $request->string('q')->toString();

        $locations = TraceLocation::where('enterprise_id', $tenantId)
            ->active()
            ->when($aiType, fn($qry) => $qry->where('ai_type', $aiType))
            ->when($q, fn($qry) => $qry->where('name', 'like', "%{$q}%"))
            ->orderBy('ai_type')
            ->orderBy('name')
            ->get([
                'id', 'ai_type', 'gln', 'code', 'name',
                'province', 'district', 'address_detail',
                'lat', 'lng', 'area_ha', 'farm_code',
            ])
            ->map(fn($loc) => array_merge($loc->toArray(), [
                'ai_label'   => $loc->ai_label,
                'gs1_ai_str' => $loc->toGs1AiString(),
            ]));

        return response()->json($locations);
    }

    /**
     * Sinh GLN tự động cho địa điểm dựa trên enterprise_id.
     * Đây là helper cho demo — thực tế phải đăng ký GLN với GS1 Việt Nam.
     */
    public function generateGln(Request $request)
    {
        $tenantId = $this->tenantId($request);
        $gs1      = app(GS1Service::class);

        // Dùng enterprise_id * 1000 + count locations làm seed
        $locationCount = TraceLocation::where('enterprise_id', $tenantId)->count();
        $seed          = $tenantId * 1000 + $locationCount + 1;
        $gln           = $gs1->generateGLN($seed);

        return response()->json([
            'gln'       => $gln,
            'formatted' => $gs1->formatGLN($gln),
            'note'      => 'GLN tạm thời cho demo — đăng ký chính thức tại gs1.org.vn',
        ]);
    }
}
