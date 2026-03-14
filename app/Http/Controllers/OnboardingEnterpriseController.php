<?php

namespace App\Http\Controllers;

use App\Mail\EnterpriseSubmittedMail;
use App\Models\Enterprise;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class OnboardingEnterpriseController extends Controller
{
    public function create()
    {
        return Inertia::render('Onboarding/EnterpriseCreate');
    }

    private function generateEnterpriseCode(string $province): string
    {
        // Map tỉnh → mã 2-3 ký tự viết tắt
        $provinceMap = [
            'An Giang'          => 'AG',  'Bà Rịa - Vũng Tàu' => 'VT',
            'Bạc Liêu'          => 'BL',  'Bắc Kạn'           => 'BK',
            'Bắc Giang'         => 'BG',  'Bắc Ninh'          => 'BN',
            'Bến Tre'           => 'BT',  'Bình Dương'        => 'BD',
            'Bình Định'         => 'BDi', 'Bình Phước'        => 'BP',
            'Bình Thuận'        => 'BTh', 'Cà Mau'            => 'CM',
            'Cao Bằng'          => 'CB',  'Cần Thơ'           => 'CT',
            'Đà Nẵng'           => 'DN',  'Đắk Lắk'          => 'DL',
            'Đắk Nông'          => 'DNo', 'Điện Biên'         => 'DB',
            'Đồng Nai'          => 'DNa', 'Đồng Tháp'        => 'DT',
            'Gia Lai'           => 'GL',  'Hà Giang'          => 'HG',
            'Hà Nam'            => 'HN',  'Hà Nội'            => 'HNi',
            'Hà Tĩnh'           => 'HT',  'Hải Dương'         => 'HD',
            'Hải Phòng'         => 'HP',  'Hậu Giang'         => 'HGi',
            'Hòa Bình'          => 'HB',  'Hồ Chí Minh'       => 'HCM',
            'Hưng Yên'          => 'HY',  'Khánh Hòa'         => 'KH',
            'Kiên Giang'        => 'KG',  'Kon Tum'           => 'KT',
            'Lai Châu'          => 'LC',  'Lâm Đồng'          => 'LD',
            'Lạng Sơn'          => 'LS',  'Lào Cai'           => 'LCa',
            'Long An'           => 'LA',  'Nam Định'          => 'ND',
            'Nghệ An'           => 'NA',  'Ninh Bình'         => 'NB',
            'Ninh Thuận'        => 'NT',  'Phú Thọ'           => 'PT',
            'Phú Yên'           => 'PY',  'Quảng Bình'        => 'QB',
            'Quảng Nam'         => 'QNam','Quảng Ngãi'        => 'QNg',
            'Quảng Ninh'        => 'QN',  'Quảng Trị'         => 'QT',
            'Sóc Trăng'         => 'ST',  'Sơn La'            => 'SL',
            'Tây Ninh'          => 'TN',  'Thái Bình'         => 'TB',
            'Thái Nguyên'       => 'TNg', 'Thanh Hóa'         => 'TH',
            'Thừa Thiên Huế'    => 'TTH', 'Tiền Giang'        => 'TG',
            'Trà Vinh'          => 'TV',  'Tuyên Quang'       => 'TQ',
            'Vĩnh Long'         => 'VL',  'Vĩnh Phúc'         => 'VP',
            'Yên Bái'           => 'YB',
        ];

        // Tìm prefix tỉnh — so sánh không phân biệt hoa thường
        $prefix = 'XX';
        foreach ($provinceMap as $name => $abbr) {
            if (stripos($province, $name) !== false || stripos($name, $province) !== false) {
                $prefix = strtoupper($abbr);
                break;
            }
        }

        // Đếm số DN cùng prefix để tạo số thứ tự
        $count = Enterprise::where('code', 'like', "AGU-{$prefix}-%")->count();
        $seq   = str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        return "AGU-{$prefix}-{$seq}";
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                          => ['required', 'string', 'max:255'],
            'business_code'                 => ['required', 'string', 'max:50'],
            'business_code_issued_at'       => ['nullable', 'date'],
            'business_cert_no'              => ['nullable', 'string', 'max:100'],
            'business_cert_issued_place'    => ['nullable', 'string', 'max:255'],
            'business_license_no'           => ['nullable', 'string', 'max:100'],
            'business_license_issued_place' => ['nullable', 'string', 'max:255'],
            'province'                      => ['required', 'string', 'max:100'],
            'district'                      => ['required', 'string', 'max:100'],
            'address_detail'                => ['nullable', 'string', 'max:255'],
            'phone'                         => ['required', 'string', 'max:30'],
            'email'                         => ['required', 'email', 'max:255'],
            'representative_name'           => ['nullable', 'string', 'max:255'],
            'representative_id'             => ['nullable', 'string', 'max:50'],
            'business_cert_file'            => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:20480'],
            'accept_terms'                  => ['accepted'],
        ]);

        $user       = $request->user();
        $enterprise = null;

        DB::transaction(function () use ($request, $user, $data, &$enterprise) {
            $path = $request->file('business_cert_file')->store('enterprise_gcn', 'public');

            // Sinh mã DN hệ thống
            $code = $this->generateEnterpriseCode($data['province']);

            $enterprise = Enterprise::create([
                'name'                          => $data['name'],
                'code'                          => $code,
                'business_code'                 => $data['business_code'],
                'business_code_issued_at'       => $data['business_code_issued_at'] ?? null,
                'business_cert_no'              => $data['business_cert_no'] ?? null,
                'business_cert_issued_place'    => $data['business_cert_issued_place'] ?? null,
                'business_license_no'           => $data['business_license_no'] ?? null,
                'business_license_issued_place' => $data['business_license_issued_place'] ?? null,
                'province'                      => $data['province'],
                'district'                      => $data['district'],
                'address_detail'                => $data['address_detail'] ?? null,
                'phone'                         => $data['phone'],
                'email'                         => $data['email'],
                'representative_name'           => $data['representative_name'] ?? null,
                'representative_id'             => $data['representative_id'] ?? null,
                'business_cert_file_path'       => $path,
                'status'                        => 'pending',
                'created_by'                    => $user->id,
                'admin_user_id'                 => $user->id,
                'terms_accepted_at'             => now(),
            ]);

            $user->enterprise_id = $enterprise->id;
            $user->role          = 'enterprise_admin';
            $user->save();
        });

        // Gửi mail thông báo cho super admin
        $superAdmins = User::where('is_super_admin', true)->get();
        foreach ($superAdmins as $admin) {
            Mail::to($admin->email)->queue(new EnterpriseSubmittedMail($enterprise, $user));
        }

        return redirect()->route('onboarding.enterprise.pending');
    }
}