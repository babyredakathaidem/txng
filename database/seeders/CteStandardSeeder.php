<?php

namespace Database\Seeders;

use App\Models\CteTemplate;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CteStandardSeeder extends Seeder
{
    public function run(): void
    {
        // Xóa sạch CTE cũ để xây lại cho chuẩn
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        CteTemplate::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Lấy hoặc tạo Category mặc định (Nông sản/Thực phẩm là nhóm gắt nhất)
        $category = ProductCategory::firstOrCreate(
            ['code' => 'agri_food'],
            [
                'name_vi' => 'Sản phẩm nông sản/thực phẩm',
                'tcvn_ref' => 'TCVN 12850:2019',
            ]
        );

        $templates = [
            [
                'code' => 'INIT_BATCH',
                'name_vi' => 'Khởi tạo lô hàng (Đăng ký)',
                'step_order' => 1,
                'is_required' => true,
                'tcvn_note' => 'TCVN 12850:2019 - Khoản 5.1: Khai báo đối tượng truy xuất',
                'kde_schema' => [
                    ['w' => 'WHO', 'label' => 'Người khai báo', 'key' => 'recorder_name', 'type' => 'text', 'required' => true],
                    ['w' => 'WHAT', 'label' => 'Mã GTIN', 'key' => 'gtin', 'type' => 'text', 'required' => true],
                    ['w' => 'WHERE', 'label' => 'Cơ sở sản xuất (GLN)', 'key' => 'production_gln', 'type' => 'text', 'required' => true],
                    ['w' => 'WHY', 'label' => 'Tiêu chuẩn áp dụng', 'key' => 'technical_standard', 'type' => 'text', 'placeholder' => 'TCVN 11892-1:2017 (VietGAP)'],
                ]
            ],
            [
                'code' => 'PRODUCTION_LOG',
                'name_vi' => 'Ghi nhận sản xuất/nuôi trồng',
                'step_order' => 2,
                'is_required' => false,
                'tcvn_note' => 'Ghi nhận các yếu tố đầu vào (vật tư, phân bón, thuốc)',
                'kde_schema' => [
                    ['w' => 'WHO', 'label' => 'Công nhân/Kỹ thuật viên', 'key' => 'worker_id', 'type' => 'text'],
                    ['w' => 'WHAT', 'label' => 'Vật tư đầu vào', 'key' => 'input_materials', 'type' => 'textarea'],
                    ['w' => 'WHERE', 'label' => 'Vị trí (Lô/Thửa/Chuồng)', 'key' => 'internal_location', 'type' => 'text'],
                    ['w' => 'WHY', 'label' => 'Nhật ký kỹ thuật', 'key' => 'technical_log', 'type' => 'textarea'],
                ]
            ],
            [
                'code' => 'TRANSFORMATION',
                'name_vi' => 'Biến đổi/Chế biến (Split/Merge)',
                'step_order' => 3,
                'is_required' => false,
                'tcvn_note' => 'TCVN 12850:2019 - Khoản 5.3: Sự kiện biến đổi vật phẩm',
                'kde_schema' => [
                    ['w' => 'WHO', 'label' => 'Quản lý xưởng', 'key' => 'manager_name', 'type' => 'text'],
                    ['w' => 'WHAT', 'label' => 'Tỷ lệ hao hụt (%)', 'key' => 'loss_rate', 'type' => 'number'],
                    ['w' => 'WHERE', 'label' => 'Dây chuyền sản xuất', 'key' => 'production_line', 'type' => 'text'],
                    ['w' => 'WHY', 'label' => 'Lý do biến đổi', 'key' => 'transformation_reason', 'type' => 'text'],
                ]
            ],
            [
                'code' => 'PACKAGING',
                'name_vi' => 'Đóng gói & Cấp mã SSCC',
                'step_order' => 4,
                'is_required' => true,
                'tcvn_note' => 'Đóng gói thành phẩm và định danh đơn vị vận chuyển (AI 00)',
                'kde_schema' => [
                    ['w' => 'WHO', 'label' => 'Tổ đóng gói', 'key' => 'packing_team', 'type' => 'text'],
                    ['w' => 'WHAT', 'label' => 'Mã SSCC (18 số)', 'key' => 'sscc_code', 'type' => 'text', 'required' => true],
                    ['w' => 'WHAT', 'label' => 'Quy cách đóng gói', 'key' => 'packaging_spec', 'type' => 'text'],
                    ['w' => 'WHERE', 'label' => 'Địa điểm đóng gói (GLN)', 'key' => 'packaging_gln', 'type' => 'text'],
                ]
            ],
            [
                'code' => 'SHIPPING',
                'name_vi' => 'Vận chuyển (Xuất kho)',
                'step_order' => 5,
                'is_required' => true,
                'tcvn_note' => 'TCVN 12850:2019 - Khoản 5.4: Sự kiện di chuyển',
                'kde_schema' => [
                    ['w' => 'WHO', 'label' => 'Đơn vị vận chuyển (MST)', 'key' => 'transporter_tax_id', 'type' => 'text'],
                    ['w' => 'WHAT', 'label' => 'Số hiệu phương tiện', 'key' => 'vehicle_id', 'type' => 'text'],
                    ['w' => 'WHERE', 'label' => 'GLN Đích (Điểm đến)', 'key' => 'destination_gln', 'type' => 'text', 'required' => true],
                    ['w' => 'WHY', 'label' => 'Số hóa đơn/Phiếu xuất', 'key' => 'invoice_no', 'type' => 'text'],
                ]
            ],
        ];

        foreach ($templates as $tpl) {
            CteTemplate::create(array_merge($tpl, ['category_id' => $category->id]));
        }
    }
}
