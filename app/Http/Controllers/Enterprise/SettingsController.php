<?php

namespace App\Http\Controllers\Enterprise;

use App\Http\Controllers\Controller;
use App\Models\Enterprise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function show(Request $request)
    {
        $this->authorizeAdmin($request);

        $enterprise = Enterprise::findOrFail($request->user()->enterprise_id);

        $fileUrl = $enterprise->business_cert_file_path
            ? Storage::disk('public')->url($enterprise->business_cert_file_path)
            : null;

        return Inertia::render('Enterprise/Settings', [
            'enterprise' => [
                'id'                            => $enterprise->id,
                'code'                          => $enterprise->code,
                'name'                          => $enterprise->name,
                'business_code'                 => $enterprise->business_code,
                'business_code_issued_at'       => $enterprise->business_code_issued_at,
                'business_cert_no'              => $enterprise->business_cert_no,
                'business_cert_issued_place'    => $enterprise->business_cert_issued_place,
                'business_license_no'           => $enterprise->business_license_no,
                'business_license_issued_place' => $enterprise->business_license_issued_place,
                'province'                      => $enterprise->province,
                'district'                      => $enterprise->district,
                'address_detail'                => $enterprise->address_detail,
                'phone'                         => $enterprise->phone,
                'email'                         => $enterprise->email,
                'representative_name'           => $enterprise->representative_name,
                'representative_id'             => $enterprise->representative_id,
                'status'                        => $enterprise->status,
                'approved_at'                   => optional($enterprise->approved_at)->format('d/m/Y'),
                'cert_file_url'                 => $fileUrl,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $this->authorizeAdmin($request);

        $enterprise = Enterprise::findOrFail($request->user()->enterprise_id);

        $data = $request->validate([
            'name'                          => ['required', 'string', 'max:255'],
            'phone'                         => ['required', 'string', 'max:30'],
            'email'                         => ['required', 'email', 'max:255'],
            'province'                      => ['required', 'string', 'max:100'],
            'district'                      => ['required', 'string', 'max:100'],
            'address_detail'                => ['nullable', 'string', 'max:255'],
            'representative_name'           => ['nullable', 'string', 'max:255'],
            'representative_id'             => ['nullable', 'string', 'max:50'],
            'business_cert_no'              => ['nullable', 'string', 'max:100'],
            'business_cert_issued_place'    => ['nullable', 'string', 'max:255'],
            'business_license_no'           => ['nullable', 'string', 'max:100'],
            'business_license_issued_place' => ['nullable', 'string', 'max:255'],
        ]);

        $enterprise->update($data);

        return back()->with('success', 'Đã cập nhật thông tin doanh nghiệp.');
    }

    private function authorizeAdmin(Request $request): void
    {
        if (!$request->user()->isEnterpriseAdmin()) {
            abort(403, 'Chỉ Admin DN mới có quyền này.');
        }
    }
}