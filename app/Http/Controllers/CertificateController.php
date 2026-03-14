<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Traits\HasTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CertificateController extends Controller
{
    use HasTenant;

    // Tên chứng chỉ chuẩn phổ biến — hiển thị gợi ý trên form
    const STANDARD_NAMES = [
        'VietGAP', 'GlobalGAP', 'ISO 22000', 'HACCP',
        'ISO 9001', 'Organic', 'OCOP', 'FDA', 'BRC', 'SQF',
        'USDA Organic', 'JAS', 'Halal', 'Kosher',
    ];

    // ── Danh sách chứng chỉ ──────────────────────────────
    public function index(Request $request)
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.certificates.view', 'enterprise.certificates.manage']), 403);
        $tenantId = $this->tenantId($request);

        $certs = Certificate::where('enterprise_id', $tenantId)
            ->withCount('batches')
            ->orderBy('status')
            ->orderBy('name')
            ->get()
            ->map(fn($c) => [
                'id'             => $c->id,
                'name'           => $c->name,
                'organization'   => $c->organization,
                'certificate_no' => $c->certificate_no,
                'scope'          => $c->scope,
                'issue_date'     => $c->issue_date?->format('d/m/Y'),
                'issue_date_raw' => $c->issue_date?->format('Y-m-d'),
                'expiry_date'    => $c->expiry_date?->format('d/m/Y'),
                'expiry_date_raw'=> $c->expiry_date?->format('Y-m-d'),
                'image_url'      => $c->image_url,
                'status'         => $c->status,
                'is_expired'     => $c->isExpired(),
                'note'           => $c->note,
                'batches_count'  => $c->batches_count,
            ]);

        return Inertia::render('Enterprise/Certificates/Index', [
            'certificates'  => $certs,
            'standardNames' => self::STANDARD_NAMES,
        ]);
    }

    // ── Tạo mới chứng chỉ ────────────────────────────────
    public function store(Request $request)
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.certificates.manage']), 403);
        $tenantId = $this->tenantId($request);

        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'organization'   => 'nullable|string|max:255',
            'certificate_no' => 'nullable|string|max:100',
            'scope'          => 'nullable|string|max:500',
            'issue_date'     => 'nullable|date',
            'expiry_date'    => 'nullable|date|after_or_equal:issue_date',
            'image'          => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'status'         => 'required|in:active,expired,revoked',
            'note'           => 'nullable|string|max:1000',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')
                ->store("certificates/{$tenantId}", 'public');
        }

        Certificate::create([
            'enterprise_id'  => $tenantId,
            'name'           => $data['name'],
            'organization'   => $data['organization'] ?? null,
            'certificate_no' => $data['certificate_no'] ?? null,
            'scope'          => $data['scope'] ?? null,
            'issue_date'     => $data['issue_date'] ?? null,
            'expiry_date'    => $data['expiry_date'] ?? null,
            'image_path'     => $imagePath,
            'status'         => $data['status'],
            'note'           => $data['note'] ?? null,
        ]);

        return back()->with('success', "Đã thêm chứng chỉ {$data['name']}.");
    }

    // ── Cập nhật chứng chỉ ───────────────────────────────
    public function update(Request $request, Certificate $certificate)
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.certificates.manage']), 403);
        abort_unless($certificate->enterprise_id === $this->tenantId($request), 403);

        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'organization'   => 'nullable|string|max:255',
            'certificate_no' => 'nullable|string|max:100',
            'scope'          => 'nullable|string|max:500',
            'issue_date'     => 'nullable|date',
            'expiry_date'    => 'nullable|date|after_or_equal:issue_date',
            'image'          => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'status'         => 'required|in:active,expired,revoked',
            'note'           => 'nullable|string|max:1000',
        ]);

        $imagePath = $certificate->image_path;
        if ($request->hasFile('image')) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('image')
                ->store("certificates/{$certificate->enterprise_id}", 'public');
        }

        $certificate->update([
            'name'           => $data['name'],
            'organization'   => $data['organization'] ?? null,
            'certificate_no' => $data['certificate_no'] ?? null,
            'scope'          => $data['scope'] ?? null,
            'issue_date'     => $data['issue_date'] ?? null,
            'expiry_date'    => $data['expiry_date'] ?? null,
            'image_path'     => $imagePath,
            'status'         => $data['status'],
            'note'           => $data['note'] ?? null,
        ]);

        return back()->with('success', "Đã cập nhật chứng chỉ {$data['name']}.");
    }

    // ── Xóa chứng chỉ ────────────────────────────────────
    public function destroy(Request $request, Certificate $certificate)
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.certificates.manage']), 403);
        abort_unless($certificate->enterprise_id === $this->tenantId($request), 403);

        if ($certificate->image_path) {
            Storage::disk('public')->delete($certificate->image_path);
        }

        $certificate->delete();

        return back()->with('success', 'Đã xóa chứng chỉ.');
    }

    // ── API: lấy danh sách chứng chỉ cho dropdown (BatchController dùng) ──
    public function listForBatch(Request $request)
    {
        $tenantId = $this->tenantId($request);

        $certs = Certificate::where('enterprise_id', $tenantId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'organization', 'certificate_no', 'expiry_date', 'image_path']);

        return response()->json($certs->map(fn($c) => [
            'id'             => $c->id,
            'name'           => $c->name,
            'organization'   => $c->organization,
            'certificate_no' => $c->certificate_no,
            'expiry_date'    => $c->expiry_date?->format('d/m/Y'),
            'image_url'      => $c->image_url,
        ]));
    }
}