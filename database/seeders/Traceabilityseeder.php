<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TraceabilitySeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCategories();
        $this->seedCteTemplates();
    }

    // ── KDE field helpers ──────────────────────────────────────

    private function f(
        string $key,
        string $label,
        string $w,
        string $type = 'text',
        bool   $required = false,
        string $placeholder = '',
        array  $options = []
    ): array {
        $f = compact('key', 'label', 'w', 'type', 'required');
        if ($placeholder) $f['placeholder'] = $placeholder;
        if ($options)     $f['options']     = $options;
        return $f;
    }

    // ── Common KDE sets (tái dụng) ─────────────────────────────

    private function whoPerformer(bool $req = true): array
    {
        return $this->f('who_performer', 'Người/Đơn vị thực hiện', 'WHO', 'text', $req, 'VD: HTX Nông nghiệp Mỹ Phú');
    }

    private function whoSupervisor(): array
    {
        return $this->f('who_supervisor', 'Người giám sát', 'WHO', 'text', false);
    }

    private function whereAddress(bool $req = true): array
    {
        return $this->f('where_address', 'Địa điểm', 'WHERE', 'text', $req, 'Địa chỉ cụ thể');
    }

    private function whereGps(): array
    {
        return $this->f('where_gps', 'Tọa độ GPS', 'WHERE', 'gps', false);
    }

    private function whyStandard(array $options = []): array
    {
        $opts = $options ?: ['VietGAP', 'GlobalGAP', 'Organic', 'TCVN', 'Quy trình nội bộ', 'Khác'];
        return $this->f('why_standard', 'Tiêu chuẩn/Quy trình áp dụng', 'WHY', 'select', false, '', $opts);
    }

    private function whyNote(): array
    {
        return $this->f('why_note', 'Ghi chú thêm', 'WHY', 'textarea', false);
    }

    private function whatQuantity(bool $req = true): array
    {
        return $this->f('what_quantity', 'Số lượng', 'WHAT', 'number', $req, 'VD: 500');
    }

    private function whatUnit(): array
    {
        return $this->f('what_unit', 'Đơn vị tính', 'WHAT', 'select', false, '', ['kg', 'tấn', 'thùng', 'hộp', 'túi', 'con', 'lít', 'cái']);
    }

    // ── Category definitions ───────────────────────────────────

    private function seedCategories(): void
    {
        $categories = [
            ['code' => 'lua_gao',     'name_vi' => 'Lúa gạo',               'tcvn_ref' => 'TCVN 12850:2019', 'sort_order' => 1],
            ['code' => 'rau_qua',     'name_vi' => 'Rau quả tươi',          'tcvn_ref' => 'TCVN 12827:2023', 'sort_order' => 2],
            ['code' => 'thuy_san',    'name_vi' => 'Thủy sản',              'tcvn_ref' => 'TCVN 12850:2019', 'sort_order' => 3],
            ['code' => 'chan_nuoi',   'name_vi' => 'Chăn nuôi / Thịt',      'tcvn_ref' => 'TCVN 12850:2019', 'sort_order' => 4],
            ['code' => 'thuc_pham_cb','name_vi' => 'Thực phẩm chế biến',    'tcvn_ref' => 'TCVN 12850:2019', 'sort_order' => 5],
            ['code' => 'khac',        'name_vi' => 'Khác',                  'tcvn_ref' => 'TCVN 12850:2019', 'sort_order' => 6],
        ];

        foreach ($categories as $cat) {
            DB::table('product_categories')->updateOrInsert(
                ['code' => $cat['code']],
                array_merge($cat, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    // ── CTE Template definitions ───────────────────────────────

    private function seedCteTemplates(): void
    {
        $catId = fn(string $code) => DB::table('product_categories')->where('code', $code)->value('id');

        $templates = [
            // ================================================================
            // LÚA GẠO
            // ================================================================
            ['category' => 'lua_gao', 'step' => 1, 'code' => 'land_prep',   'name_vi' => 'Chuẩn bị đất & Làm đất',   'required' => true,  'kde' => [
                $this->whoPerformer(),
                $this->f('what_area', 'Diện tích (ha)', 'WHAT', 'number', true, 'VD: 2.5'),
                $this->f('what_method', 'Phương pháp làm đất', 'WHAT', 'select', false, '', ['Cày bừa cơ giới', 'Thủ công', 'Kết hợp']),
                $this->whereAddress(),
                $this->whereGps(),
                $this->whyStandard(['VietGAP', 'SRP', 'Organic', 'Quy trình nội bộ']),
                $this->whyNote(),
            ]],
            ['category' => 'lua_gao', 'step' => 2, 'code' => 'seeding',     'name_vi' => 'Gieo sạ',                   'required' => true,  'kde' => [
                $this->whoPerformer(),
                $this->f('what_variety', 'Giống lúa', 'WHAT', 'text', true, 'VD: ST25, Jasmine 85'),
                $this->f('what_seed_amount', 'Lượng giống (kg/ha)', 'WHAT', 'number', true),
                $this->f('what_method', 'Phương pháp', 'WHAT', 'select', false, '', ['Sạ thẳng', 'Cấy', 'Sạ hàng']),
                $this->whereAddress(true),
                $this->whereGps(),
                $this->whyStandard(['VietGAP', 'SRP', 'Organic']),
                $this->whyNote(),
            ]],
            ['category' => 'lua_gao', 'step' => 3, 'code' => 'crop_care',   'name_vi' => 'Chăm sóc & Bón phân',       'required' => false, 'kde' => [
                $this->whoPerformer(false),
                $this->f('what_fertilizer', 'Loại phân bón', 'WHAT', 'text', false, 'VD: Phân Ure, NPK 16-16-8'),
                $this->f('what_amount', 'Lượng phân bón (kg/ha)', 'WHAT', 'number', false),
                $this->whereAddress(false),
                $this->whyNote(),
            ]],
            ['category' => 'lua_gao', 'step' => 4, 'code' => 'pesticide',   'name_vi' => 'Phun thuốc BVTV',           'required' => false, 'kde' => [
                $this->whoPerformer(),
                $this->f('what_pesticide', 'Tên thuốc BVTV', 'WHAT', 'text', true, 'Tên thương mại + hoạt chất'),
                $this->f('what_dosage', 'Liều lượng', 'WHAT', 'text', true),
                $this->f('what_purpose', 'Mục đích phun', 'WHAT', 'text', false, 'VD: Trừ sâu đục thân'),
                $this->f('what_phi', 'PHI - Thời gian cách ly (ngày)', 'WHAT', 'number', true),
                $this->whereAddress(),
                $this->f('why_target', 'Đối tượng dịch hại', 'WHY', 'text', false),
                $this->whyNote(),
            ]],
            ['category' => 'lua_gao', 'step' => 5, 'code' => 'harvest',     'name_vi' => 'Thu hoạch',                 'required' => true,  'kde' => [
                $this->whoPerformer(),
                $this->whoSupervisor(),
                $this->whatQuantity(),
                $this->whatUnit(),
                $this->f('what_moisture', 'Độ ẩm hạt (%)', 'WHAT', 'number', false),
                $this->f('what_method', 'Phương tiện thu hoạch', 'WHAT', 'select', false, '', ['Máy gặt đập liên hợp', 'Thủ công', 'Kết hợp']),
                $this->whereAddress(),
                $this->whereGps(),
                $this->whyStandard(['VietGAP', 'SRP', 'Organic']),
                $this->whyNote(),
            ]],
            ['category' => 'lua_gao', 'step' => 6, 'code' => 'drying',      'name_vi' => 'Sấy / Phơi khô',            'required' => true,  'kde' => [
                $this->whoPerformer(),
                $this->f('what_method', 'Phương pháp', 'WHAT', 'select', true, '', ['Sấy máy', 'Phơi nắng', 'Kết hợp']),
                $this->f('what_temp', 'Nhiệt độ sấy (°C)', 'WHAT', 'number', false),
                $this->f('what_duration', 'Thời gian (giờ)', 'WHAT', 'number', false),
                $this->f('what_moisture_after', 'Độ ẩm sau sấy (%)', 'WHAT', 'number', true),
                $this->whereAddress(),
                $this->whyNote(),
            ]],
            ['category' => 'lua_gao', 'step' => 7, 'code' => 'milling',     'name_vi' => 'Xay xát',                   'required' => true,  'kde' => [
                $this->whoPerformer(),
                $this->f('what_mill_name', 'Cơ sở xay xát', 'WHO', 'text', true),
                $this->whatQuantity(),
                $this->f('what_yield_rate', 'Tỷ lệ thu hồi gạo (%)', 'WHAT', 'number', false),
                $this->f('what_rice_type', 'Loại gạo sau xay', 'WHAT', 'text', false, 'VD: Gạo trắng 5% tấm'),
                $this->whereAddress(),
                $this->whyNote(),
            ]],
            ['category' => 'lua_gao', 'step' => 8, 'code' => 'quality_check','name_vi' => 'Kiểm tra chất lượng',      'required' => true,  'kde' => [
                $this->f('who_lab', 'Đơn vị kiểm tra/phân tích', 'WHO', 'text', true),
                $this->f('who_inspector', 'Cán bộ kiểm tra', 'WHO', 'text', false),
                $this->f('what_result', 'Kết quả kiểm tra', 'WHAT', 'select', true, '', ['Đạt', 'Không đạt', 'Cần xem xét thêm']),
                $this->f('what_cert_no', 'Số phiếu / Mã chứng nhận', 'WHAT', 'text', false),
                $this->f('what_standards_tested', 'Tiêu chuẩn kiểm tra', 'WHAT', 'text', false, 'VD: TCVN 5644:2008, MRL thuốc trừ sâu'),
                $this->f('what_residue', 'Kết quả dư lượng thuốc BVTV', 'WHAT', 'text', false),
                $this->whereAddress(false),
                $this->f('why_conclusion', 'Kết luận / Khuyến nghị', 'WHY', 'textarea', false),
            ]],
            ['category' => 'lua_gao', 'step' => 9, 'code' => 'packaging',   'name_vi' => 'Đóng gói',                  'required' => true,  'kde' => [
                $this->whoPerformer(),
                $this->f('what_pack_type', 'Loại bao bì', 'WHAT', 'text', true, 'VD: Túi PP 5kg, Thùng carton 25kg'),
                $this->f('what_qty_packs', 'Số lượng đơn vị đóng gói', 'WHAT', 'number', true),
                $this->f('what_net_weight', 'Trọng lượng tịnh/đơn vị (kg)', 'WHAT', 'number', true),
                $this->whereAddress(),
                $this->whyStandard(['TCVN', 'Quy chuẩn bao bì thực phẩm', 'Yêu cầu khách hàng']),
                $this->whyNote(),
            ]],
            ['category' => 'lua_gao', 'step' => 10,'code' => 'warehousing', 'name_vi' => 'Nhập kho',                  'required' => false, 'kde' => [
                $this->whoPerformer(false),
                $this->whatQuantity(),
                $this->whatUnit(),
                $this->f('what_warehouse', 'Tên kho / Mã kho', 'WHERE', 'text', true),
                $this->whereAddress(),
                $this->f('what_temp', 'Nhiệt độ kho (°C)', 'WHAT', 'number', false),
                $this->f('what_humidity', 'Độ ẩm kho (%)', 'WHAT', 'number', false),
                $this->whyNote(),
            ]],
            ['category' => 'lua_gao', 'step' => 11,'code' => 'distribution','name_vi' => 'Xuất kho / Phân phối',      'required' => true,  'kde' => [
                $this->f('who_shipper', 'Đơn vị giao hàng', 'WHO', 'text', true),
                $this->f('who_receiver', 'Đơn vị nhận hàng / Khách hàng', 'WHO', 'text', true),
                $this->whatQuantity(),
                $this->whatUnit(),
                $this->f('what_vehicle', 'Phương tiện vận chuyển / Biển số', 'WHAT', 'text', false),
                $this->f('where_from', 'Địa điểm xuất phát', 'WHERE', 'text', true),
                $this->f('where_to', 'Điểm đến / Địa chỉ giao', 'WHERE', 'text', true),
                $this->f('what_invoice', 'Số hóa đơn / Chứng từ xuất kho', 'WHAT', 'text', false),
                $this->whyNote(),
            ]],

            // ================================================================
            // RAU QUẢ TƯƠI (TCVN 12827:2023)
            // ================================================================
            ['category' => 'rau_qua', 'step' => 1, 'code' => 'planting',    'name_vi' => 'Gieo trồng',                'required' => true,  'kde' => [
                $this->whoPerformer(),
                $this->f('what_variety', 'Giống cây trồng', 'WHAT', 'text', true, 'VD: Cải xanh F1, Dưa hấu Sugar Baby'),
                $this->f('what_area', 'Diện tích gieo trồng (m² hoặc ha)', 'WHAT', 'number', true),
                $this->f('what_seed_source', 'Nguồn giống', 'WHAT', 'text', false, 'Tên cơ sở cung cấp giống'),
                $this->whereAddress(true),
                $this->whereGps(),
                $this->whyStandard(['VietGAP', 'GlobalGAP', 'Organic', 'Hữu cơ']),
                $this->whyNote(),
            ]],
            ['category' => 'rau_qua', 'step' => 2, 'code' => 'crop_care',   'name_vi' => 'Chăm sóc & Tưới tiêu',     'required' => false, 'kde' => [
                $this->whoPerformer(false),
                $this->f('what_activity', 'Hoạt động chăm sóc', 'WHAT', 'text', false, 'VD: Tưới nước, Làm cỏ, Tỉa cành'),
                $this->f('what_fertilizer', 'Phân bón sử dụng', 'WHAT', 'text', false),
                $this->whereAddress(false),
                $this->whyNote(),
            ]],
            ['category' => 'rau_qua', 'step' => 3, 'code' => 'pesticide',   'name_vi' => 'Phun thuốc BVTV',           'required' => false, 'kde' => [
                $this->whoPerformer(),
                $this->f('what_pesticide', 'Tên thuốc BVTV (tên TM + hoạt chất)', 'WHAT', 'text', true),
                $this->f('what_dosage', 'Liều lượng / Nồng độ', 'WHAT', 'text', true),
                $this->f('what_phi', 'Thời gian cách ly PHI (ngày)', 'WHAT', 'number', true),
                $this->f('what_purpose', 'Đối tượng phòng trừ', 'WHAT', 'text', true),
                $this->whereAddress(),
                $this->whyNote(),
            ]],
            ['category' => 'rau_qua', 'step' => 4, 'code' => 'harvest',     'name_vi' => 'Thu hoạch',                 'required' => true,  'kde' => [
                $this->whoPerformer(),
                $this->whatQuantity(),
                $this->whatUnit(),
                $this->f('what_maturity', 'Tiêu chí thu hoạch / Độ chín', 'WHAT', 'text', false),
                $this->f('what_condition', 'Tình trạng sản phẩm sau thu hoạch', 'WHAT', 'text', false),
                $this->whereAddress(),
                $this->whereGps(),
                $this->whyStandard(['VietGAP', 'GlobalGAP', 'Organic', 'Tiêu chuẩn thu mua']),
                $this->whyNote(),
            ]],
            ['category' => 'rau_qua', 'step' => 5, 'code' => 'sorting',     'name_vi' => 'Phân loại & Sơ chế',       'required' => true,  'kde' => [
                $this->whoPerformer(),
                $this->f('what_grade', 'Phân loại / Cấp chất lượng', 'WHAT', 'select', true, '', ['Loại 1 (Extra)', 'Loại 2 (Class I)', 'Loại 3 (Class II)', 'Phế phẩm']),
                $this->f('what_qty_accepted', 'Sản lượng đạt loại (kg)', 'WHAT', 'number', false),
                $this->f('what_qty_rejected', 'Sản lượng loại thải (kg)', 'WHAT', 'number', false),
                $this->f('what_process', 'Sơ chế thực hiện', 'WHAT', 'text', false, 'VD: Rửa, cắt gốc, bóc bẹ'),
                $this->whereAddress(),
                $this->whyNote(),
            ]],
            ['category' => 'rau_qua', 'step' => 6, 'code' => 'quality_check','name_vi' => 'Kiểm tra chất lượng',     'required' => true,  'kde' => [
                $this->f('who_lab', 'Đơn vị kiểm tra', 'WHO', 'text', true),
                $this->f('what_result', 'Kết quả', 'WHAT', 'select', true, '', ['Đạt', 'Không đạt', 'Cần kiểm tra thêm']),
                $this->f('what_cert_no', 'Số phiếu kiểm nghiệm', 'WHAT', 'text', false),
                $this->f('what_residue_result', 'Kết quả dư lượng thuốc BVTV', 'WHAT', 'text', false),
                $this->f('what_micro_result', 'Kết quả vi sinh', 'WHAT', 'text', false),
                $this->whereAddress(false),
                $this->f('why_tcvn', 'Tiêu chuẩn kiểm tra áp dụng', 'WHY', 'text', false, 'VD: TCVN 12827:2023, QCVN 8-3'),
                $this->f('why_note', 'Kết luận', 'WHY', 'textarea', false),
            ]],
            ['category' => 'rau_qua', 'step' => 7, 'code' => 'packaging',   'name_vi' => 'Đóng gói',                  'required' => true,  'kde' => [
                $this->whoPerformer(),
                $this->f('what_pack_type', 'Loại bao bì', 'WHAT', 'text', true, 'VD: Túi lưới 500g, Hộp carton 5kg'),
                $this->f('what_qty_packs', 'Số lượng đơn vị đóng gói', 'WHAT', 'number', true),
                $this->f('what_net_weight', 'Trọng lượng tịnh/đơn vị', 'WHAT', 'text', true),
                $this->f('what_label_info', 'Thông tin nhãn (hạn SD, lô)', 'WHAT', 'text', false),
                $this->whereAddress(),
                $this->whyStandard(['TCVN', 'GlobalGAP packaging', 'Yêu cầu siêu thị']),
                $this->whyNote(),
            ]],
            ['category' => 'rau_qua', 'step' => 8, 'code' => 'cold_storage','name_vi' => 'Bảo quản lạnh',             'required' => false, 'kde' => [
                $this->whoPerformer(false),
                $this->f('what_warehouse', 'Tên kho lạnh', 'WHERE', 'text', true),
                $this->f('what_temp', 'Nhiệt độ bảo quản (°C)', 'WHAT', 'number', true),
                $this->f('what_humidity', 'Độ ẩm (%)', 'WHAT', 'number', false),
                $this->whatQuantity(),
                $this->whatUnit(),
                $this->whereAddress(),
                $this->whyNote(),
            ]],
            ['category' => 'rau_qua', 'step' => 9, 'code' => 'transport',   'name_vi' => 'Vận chuyển',                'required' => true,  'kde' => [
                $this->f('who_carrier', 'Đơn vị/Người vận chuyển', 'WHO', 'text', true),
                $this->f('who_driver', 'Tài xế / Người giao hàng', 'WHO', 'text', false),
                $this->f('what_vehicle', 'Phương tiện / Biển số xe', 'WHAT', 'text', false),
                $this->f('what_condition', 'Điều kiện bảo quản khi vận chuyển', 'WHAT', 'text', false, 'VD: Xe lạnh 4°C, Thùng xốp đá'),
                $this->whatQuantity(),
                $this->whatUnit(),
                $this->f('where_from', 'Xuất phát từ', 'WHERE', 'text', true),
                $this->f('where_to', 'Đến', 'WHERE', 'text', true),
                $this->f('what_duration', 'Thời gian vận chuyển (giờ)', 'WHAT', 'number', false),
                $this->whyNote(),
            ]],
            ['category' => 'rau_qua', 'step' => 10,'code' => 'distribution','name_vi' => 'Phân phối / Bán hàng',      'required' => true,  'kde' => [
                $this->f('who_shipper', 'Bên giao / Bên bán', 'WHO', 'text', true),
                $this->f('who_receiver', 'Bên nhận / Khách hàng', 'WHO', 'text', true),
                $this->whatQuantity(),
                $this->whatUnit(),
                $this->f('where_market', 'Kênh phân phối', 'WHERE', 'select', true, '', ['Siêu thị', 'Chợ đầu mối', 'Bán lẻ trực tiếp', 'Xuất khẩu', 'Khác']),
                $this->f('where_to', 'Địa chỉ điểm bán/giao', 'WHERE', 'text', false),
                $this->f('what_invoice', 'Số hóa đơn / Phiếu giao hàng', 'WHAT', 'text', false),
                $this->whyNote(),
            ]],

            // ================================================================
            // THỦY SẢN
            // ================================================================
            ['category' => 'thuy_san', 'step' => 1, 'code' => 'farming',    'name_vi' => 'Nuôi trồng',                'required' => true,  'kde' => [
                $this->whoPerformer(),
                $this->f('what_species', 'Loài thủy sản', 'WHAT', 'text', true, 'VD: Tôm thẻ chân trắng, Cá tra'),
                $this->f('what_area', 'Diện tích ao nuôi (ha)', 'WHAT', 'number', true),
                $this->f('what_density', 'Mật độ thả nuôi (con/m²)', 'WHAT', 'number', false),
                $this->f('what_seed_source', 'Nguồn giống / Cơ sở cung cấp', 'WHAT', 'text', false),
                $this->whereAddress(true),
                $this->whereGps(),
                $this->f('what_cert', 'Chứng nhận vùng nuôi', 'WHAT', 'text', false, 'VD: ASC, BAP, VietGAP'),
                $this->whyStandard(['VietGAP Thủy sản', 'ASC', 'BAP', 'GlobalGAP', 'Organic Aquaculture']),
                $this->whyNote(),
            ]],
            ['category' => 'thuy_san', 'step' => 2, 'code' => 'feeding',    'name_vi' => 'Cho ăn & Quản lý ao',      'required' => false, 'kde' => [
                $this->whoPerformer(false),
                $this->f('what_feed', 'Loại thức ăn', 'WHAT', 'text', false),
                $this->f('what_feed_amount', 'Lượng thức ăn (kg/ngày)', 'WHAT', 'number', false),
                $this->f('what_water_quality', 'Chỉ số nước (pH, DO, nhiệt độ)', 'WHAT', 'text', false),
                $this->f('what_treatment', 'Xử lý ao/Thuốc thú y sử dụng', 'WHAT', 'text', false),
                $this->whereAddress(false),
                $this->whyNote(),
            ]],
            ['category' => 'thuy_san', 'step' => 3, 'code' => 'harvest',    'name_vi' => 'Thu hoạch',                 'required' => true,  'kde' => [
                $this->whoPerformer(),
                $this->whatQuantity(),
                $this->whatUnit(),
                $this->f('what_avg_size', 'Kích cỡ trung bình', 'WHAT', 'text', false, 'VD: 30 con/kg'),
                $this->f('what_survival_rate', 'Tỷ lệ sống (%)', 'WHAT', 'number', false),
                $this->whereAddress(),
                $this->whereGps(),
                $this->f('why_harvest_reason', 'Lý do thu hoạch', 'WHY', 'select', false, '', ['Đạt kích cỡ thương phẩm', 'Thu hoạch dứt điểm', 'Bệnh/Sự cố']),
                $this->whyNote(),
            ]],
            ['category' => 'thuy_san', 'step' => 4, 'code' => 'processing', 'name_vi' => 'Sơ chế',                   'required' => true,  'kde' => [
                $this->whoPerformer(),
                $this->f('what_process_type', 'Hình thức sơ chế', 'WHAT', 'select', true, '', ['Nguyên con', 'Bỏ đầu', 'Fillet', 'Tôm bóc vỏ', 'Cấp đông IQF', 'Khác']),
                $this->whatQuantity(),
                $this->whatUnit(),
                $this->f('what_yield_rate', 'Tỷ lệ thu hồi (%)', 'WHAT', 'number', false),
                $this->whereAddress(),
                $this->whyStandard(['HACCP', 'ISO 22000', 'BRC', 'Quy trình nội bộ']),
                $this->whyNote(),
            ]],
            ['category' => 'thuy_san', 'step' => 5, 'code' => 'quality_check','name_vi' => 'Kiểm dịch & Kiểm tra CL','required' => true, 'kde' => [
                $this->f('who_lab', 'Cơ quan/Đơn vị kiểm dịch', 'WHO', 'text', true),
                $this->f('what_result', 'Kết quả kiểm tra', 'WHAT', 'select', true, '', ['Đạt', 'Không đạt']),
                $this->f('what_cert_no', 'Số Giấy chứng nhận ATTP / Kiểm dịch', 'WHAT', 'text', false),
                $this->f('what_antibiotic', 'Kết quả kiểm kháng sinh', 'WHAT', 'text', false),
                $this->f('what_heavy_metal', 'Kết quả kim loại nặng', 'WHAT', 'text', false),
                $this->whereAddress(false),
                $this->f('why_standard', 'Tiêu chuẩn áp dụng', 'WHY', 'text', false, 'VD: QCVN 8-3:2012/BYT'),
                $this->whyNote(),
            ]],
            ['category' => 'thuy_san', 'step' => 6, 'code' => 'freezing',   'name_vi' => 'Cấp đông / Bảo quản',      'required' => true,  'kde' => [
                $this->whoPerformer(),
                $this->f('what_method', 'Phương pháp cấp đông', 'WHAT', 'select', true, '', ['IQF (cấp đông rời)', 'Block frozen (cấp đông khối)', 'Tươi ướp đá']),
                $this->f('what_temp', 'Nhiệt độ bảo quản (°C)', 'WHAT', 'number', true),
                $this->whereAddress(),
                $this->whyNote(),
            ]],
            ['category' => 'thuy_san', 'step' => 7, 'code' => 'packaging',  'name_vi' => 'Đóng gói',                  'required' => true,  'kde' => [
                $this->whoPerformer(),
                $this->f('what_pack_type', 'Quy cách đóng gói', 'WHAT', 'text', true, 'VD: Túi PE 1kg, Master carton 10kg'),
                $this->f('what_qty_packs', 'Số lượng đơn vị đóng gói', 'WHAT', 'number', true),
                $this->whereAddress(),
                $this->whyNote(),
            ]],
            ['category' => 'thuy_san', 'step' => 8, 'code' => 'transport',  'name_vi' => 'Vận chuyển',                'required' => true,  'kde' => [
                $this->f('who_carrier', 'Đơn vị vận chuyển', 'WHO', 'text', true),
                $this->f('what_vehicle', 'Phương tiện / Biển số', 'WHAT', 'text', false),
                $this->f('what_temp', 'Nhiệt độ vận chuyển (°C)', 'WHAT', 'number', true),
                $this->whatQuantity(),
                $this->whatUnit(),
                $this->f('where_from', 'Xuất phát', 'WHERE', 'text', true),
                $this->f('where_to', 'Đến', 'WHERE', 'text', true),
                $this->whyNote(),
            ]],
            ['category' => 'thuy_san', 'step' => 9, 'code' => 'distribution','name_vi' => 'Phân phối',                'required' => true,  'kde' => [
                $this->f('who_receiver', 'Khách hàng / Đơn vị nhận', 'WHO', 'text', true),
                $this->whatQuantity(),
                $this->whatUnit(),
                $this->f('where_market', 'Thị trường', 'WHERE', 'select', true, '', ['Nội địa - Siêu thị', 'Nội địa - Nhà hàng/HoReCa', 'Nội địa - Chợ', 'Xuất khẩu']),
                $this->f('what_invoice', 'Số hóa đơn/Chứng từ', 'WHAT', 'text', false),
                $this->whyNote(),
            ]],

            // ================================================================
            // CHĂN NUÔI / THỊT
            // ================================================================
            ['category' => 'chan_nuoi', 'step' => 1, 'code' => 'breeding',  'name_vi' => 'Nuôi / Chăn thả',           'required' => true,  'kde' => [
                $this->whoPerformer(),
                $this->f('what_species', 'Loài vật nuôi', 'WHAT', 'text', true, 'VD: Heo, Bò, Gà, Vịt'),
                $this->f('what_breed', 'Giống', 'WHAT', 'text', false),
                $this->f('what_quantity', 'Số lượng con', 'WHAT', 'number', true),
                $this->f('what_housing', 'Hệ thống chăn nuôi', 'WHAT', 'select', false, '', ['Chuồng kín (công nghiệp)', 'Bán chăn thả', 'Chăn thả tự nhiên']),
                $this->whereAddress(true),
                $this->whereGps(),
                $this->whyStandard(['VietGAHP', 'GlobalGAP Livestock', 'Organic', 'An toàn dịch bệnh']),
                $this->whyNote(),
            ]],
            ['category' => 'chan_nuoi', 'step' => 2, 'code' => 'vaccination','name_vi' => 'Tiêm phòng & Thú y',       'required' => true,  'kde' => [
                $this->f('who_vet', 'Cán bộ thú y / Đơn vị thực hiện', 'WHO', 'text', true),
                $this->f('what_vaccine', 'Tên vaccine / Thuốc thú y', 'WHAT', 'text', true),
                $this->f('what_disease', 'Phòng/trị bệnh', 'WHAT', 'text', true, 'VD: Lở mồm long móng, Cúm gia cầm'),
                $this->f('what_dosage', 'Liều dùng', 'WHAT', 'text', false),
                $this->f('what_withdrawal', 'Thời gian ngưng thuốc (ngày)', 'WHAT', 'number', false),
                $this->whereAddress(false),
                $this->whyNote(),
            ]],
            ['category' => 'chan_nuoi', 'step' => 3, 'code' => 'slaughter', 'name_vi' => 'Giết mổ',                   'required' => true,  'kde' => [
                $this->f('who_slaughterhouse', 'Cơ sở giết mổ', 'WHO', 'text', true),
                $this->whatQuantity(),
                $this->f('what_weight', 'Trọng lượng hơi (kg)', 'WHAT', 'number', true),
                $this->f('what_carcass_weight', 'Trọng lượng thịt xẻ (kg)', 'WHAT', 'number', false),
                $this->whereAddress(true),
                $this->f('what_license', 'Số GCNĐKKS cơ sở giết mổ', 'WHAT', 'text', false),
                $this->whyNote(),
            ]],
            ['category' => 'chan_nuoi', 'step' => 4, 'code' => 'veterinary_check','name_vi' => 'Kiểm dịch thú y',     'required' => true,  'kde' => [
                $this->f('who_vet_authority', 'Cơ quan kiểm dịch', 'WHO', 'text', true, 'VD: Trạm Thú y huyện/tỉnh'),
                $this->f('what_result', 'Kết quả kiểm dịch', 'WHAT', 'select', true, '', ['Đạt - Cho phép lưu thông', 'Không đạt - Tiêu hủy', 'Cần xử lý thêm']),
                $this->f('what_stamp_no', 'Số tem kiểm dịch / Dấu KSVS', 'WHAT', 'text', false),
                $this->f('what_cert_no', 'Số Giấy chứng nhận kiểm dịch', 'WHAT', 'text', false),
                $this->whereAddress(false),
                $this->whyNote(),
            ]],
            ['category' => 'chan_nuoi', 'step' => 5, 'code' => 'processing','name_vi' => 'Pha lọc / Chế biến',        'required' => true,  'kde' => [
                $this->whoPerformer(),
                $this->f('what_cuts', 'Sản phẩm sau pha lọc', 'WHAT', 'text', false, 'VD: Thăn, Sườn, Ba chỉ, Nội tạng'),
                $this->whatQuantity(),
                $this->whatUnit(),
                $this->whereAddress(),
                $this->whyStandard(['HACCP', 'ISO 22000', 'VSATTP', 'Quy trình nội bộ']),
                $this->whyNote(),
            ]],
            ['category' => 'chan_nuoi', 'step' => 6, 'code' => 'cold_storage','name_vi' => 'Bảo quản lạnh',           'required' => true,  'kde' => [
                $this->whoPerformer(false),
                $this->f('what_temp', 'Nhiệt độ bảo quản (°C)', 'WHAT', 'number', true, 'VD: 2-4°C (tươi), -18°C (đông)'),
                $this->f('what_warehouse', 'Tên / Mã kho lạnh', 'WHERE', 'text', true),
                $this->whatQuantity(),
                $this->whatUnit(),
                $this->whereAddress(),
                $this->whyNote(),
            ]],
            ['category' => 'chan_nuoi', 'step' => 7, 'code' => 'packaging',  'name_vi' => 'Đóng gói',                 'required' => true,  'kde' => [
                $this->whoPerformer(),
                $this->f('what_pack_type', 'Loại bao bì', 'WHAT', 'text', true),
                $this->f('what_qty_packs', 'Số đơn vị đóng gói', 'WHAT', 'number', true),
                $this->f('what_net_weight', 'Trọng lượng tịnh', 'WHAT', 'text', true),
                $this->whereAddress(),
                $this->whyNote(),
            ]],
            ['category' => 'chan_nuoi', 'step' => 8, 'code' => 'transport',  'name_vi' => 'Vận chuyển',               'required' => true,  'kde' => [
                $this->f('who_carrier', 'Đơn vị vận chuyển', 'WHO', 'text', true),
                $this->f('what_vehicle', 'Phương tiện / Biển số', 'WHAT', 'text', false),
                $this->f('what_temp', 'Nhiệt độ xe lạnh (°C)', 'WHAT', 'number', false),
                $this->f('where_from', 'Từ', 'WHERE', 'text', true),
                $this->f('where_to', 'Đến', 'WHERE', 'text', true),
                $this->whyNote(),
            ]],
            ['category' => 'chan_nuoi', 'step' => 9, 'code' => 'distribution','name_vi' => 'Phân phối',               'required' => true,  'kde' => [
                $this->f('who_receiver', 'Đơn vị nhận / Khách hàng', 'WHO', 'text', true),
                $this->whatQuantity(),
                $this->whatUnit(),
                $this->f('where_to', 'Điểm bán / Giao tới', 'WHERE', 'text', true),
                $this->f('what_invoice', 'Số hóa đơn', 'WHAT', 'text', false),
                $this->whyNote(),
            ]],

            // ================================================================
            // THỰC PHẨM CHẾ BIẾN
            // ================================================================
            ['category' => 'thuc_pham_cb', 'step' => 1, 'code' => 'raw_material_import','name_vi' => 'Nhập nguyên liệu','required' => true, 'kde' => [
                $this->f('who_supplier', 'Nhà cung cấp nguyên liệu', 'WHO', 'text', true),
                $this->f('what_material', 'Tên nguyên liệu', 'WHAT', 'text', true),
                $this->whatQuantity(),
                $this->whatUnit(),
                $this->f('what_origin', 'Xuất xứ nguyên liệu', 'WHAT', 'text', false),
                $this->f('what_invoice', 'Số hóa đơn / Chứng từ nhập', 'WHAT', 'text', false),
                $this->whereAddress(false),
                $this->whyNote(),
            ]],
            ['category' => 'thuc_pham_cb', 'step' => 2, 'code' => 'raw_material_check','name_vi' => 'Kiểm tra nguyên liệu','required' => true, 'kde' => [
                $this->f('who_inspector', 'Người/Bộ phận kiểm tra', 'WHO', 'text', true),
                $this->f('what_result', 'Kết quả', 'WHAT', 'select', true, '', ['Đạt - Nhập kho', 'Không đạt - Trả lại', 'Đạt có điều kiện']),
                $this->f('what_criteria', 'Tiêu chí kiểm tra', 'WHAT', 'text', false, 'VD: Cảm quan, độ ẩm, vi sinh'),
                $this->whereAddress(false),
                $this->whyNote(),
            ]],
            ['category' => 'thuc_pham_cb', 'step' => 3, 'code' => 'production','name_vi' => 'Sản xuất / Chế biến',   'required' => true,  'kde' => [
                $this->whoPerformer(),
                $this->f('what_recipe', 'Công thức / Quy trình sản xuất', 'WHAT', 'text', false, 'Mã quy trình nội bộ'),
                $this->whatQuantity(),
                $this->whatUnit(),
                $this->f('what_production_order', 'Lệnh sản xuất / Mã ca', 'WHAT', 'text', false),
                $this->whereAddress(),
                $this->whyStandard(['GMP', 'HACCP', 'ISO 22000', 'FSSC 22000']),
                $this->whyNote(),
            ]],
            ['category' => 'thuc_pham_cb', 'step' => 4, 'code' => 'quality_check','name_vi' => 'Kiểm tra QC / Bán thành phẩm','required' => true, 'kde' => [
                $this->f('who_qc', 'Bộ phận / Người QC', 'WHO', 'text', true),
                $this->f('what_result', 'Kết quả QC', 'WHAT', 'select', true, '', ['Pass', 'Fail', 'Hold - Cần xem xét']),
                $this->f('what_tests', 'Chỉ tiêu kiểm tra', 'WHAT', 'text', false, 'VD: Cảm quan, lý hóa, vi sinh'),
                $this->f('what_cert_no', 'Số phiếu kiểm nghiệm', 'WHAT', 'text', false),
                $this->whereAddress(false),
                $this->whyNote(),
            ]],
            ['category' => 'thuc_pham_cb', 'step' => 5, 'code' => 'packaging','name_vi' => 'Đóng gói thành phẩm',    'required' => true,  'kde' => [
                $this->whoPerformer(),
                $this->f('what_pack_type', 'Quy cách đóng gói', 'WHAT', 'text', true),
                $this->f('what_qty', 'Số lượng thành phẩm đóng gói', 'WHAT', 'number', true),
                $this->f('what_net_weight', 'Khối lượng tịnh/đơn vị', 'WHAT', 'text', true),
                $this->f('what_exp', 'Hạn sử dụng (date)', 'WHAT', 'date', true),
                $this->whereAddress(),
                $this->whyNote(),
            ]],
            ['category' => 'thuc_pham_cb', 'step' => 6, 'code' => 'warehousing','name_vi' => 'Nhập kho thành phẩm',  'required' => false, 'kde' => [
                $this->whoPerformer(false),
                $this->f('what_warehouse', 'Tên / Mã kho', 'WHERE', 'text', true),
                $this->whatQuantity(),
                $this->whatUnit(),
                $this->f('what_temp', 'Nhiệt độ kho (°C)', 'WHAT', 'number', false),
                $this->whereAddress(),
                $this->whyNote(),
            ]],
            ['category' => 'thuc_pham_cb', 'step' => 7, 'code' => 'distribution','name_vi' => 'Xuất kho / Phân phối','required' => true,  'kde' => [
                $this->f('who_shipper', 'Bên giao / Đơn vị xuất kho', 'WHO', 'text', true),
                $this->f('who_receiver', 'Khách hàng / Đơn vị nhận', 'WHO', 'text', true),
                $this->whatQuantity(),
                $this->whatUnit(),
                $this->f('where_to', 'Địa chỉ giao hàng', 'WHERE', 'text', true),
                $this->f('what_invoice', 'Số hóa đơn / Phiếu xuất kho', 'WHAT', 'text', false),
                $this->f('what_vehicle', 'Phương tiện vận chuyển', 'WHAT', 'text', false),
                $this->whyNote(),
            ]],

            // ================================================================
            // KHÁC — template tối giản (5W đầy đủ)
            // ================================================================
            ['category' => 'khac', 'step' => 1, 'code' => 'production',     'name_vi' => 'Sản xuất / Tạo ra',         'required' => true,  'kde' => [
                $this->whoPerformer(),
                $this->whatQuantity(),
                $this->whatUnit(),
                $this->whereAddress(),
                $this->whereGps(),
                $this->whyStandard(),
                $this->whyNote(),
            ]],
            ['category' => 'khac', 'step' => 2, 'code' => 'quality_check',  'name_vi' => 'Kiểm tra chất lượng',       'required' => true,  'kde' => [
                $this->f('who_inspector', 'Người/Đơn vị kiểm tra', 'WHO', 'text', true),
                $this->f('what_result', 'Kết quả', 'WHAT', 'select', true, '', ['Đạt', 'Không đạt']),
                $this->f('what_cert', 'Số chứng nhận/phiếu KT', 'WHAT', 'text', false),
                $this->whereAddress(false),
                $this->whyNote(),
            ]],
            ['category' => 'khac', 'step' => 3, 'code' => 'packaging',      'name_vi' => 'Đóng gói',                  'required' => true,  'kde' => [
                $this->whoPerformer(),
                $this->f('what_pack_type', 'Loại bao bì', 'WHAT', 'text', true),
                $this->f('what_qty', 'Số lượng đóng gói', 'WHAT', 'number', true),
                $this->whereAddress(),
                $this->whyNote(),
            ]],
            ['category' => 'khac', 'step' => 4, 'code' => 'distribution',   'name_vi' => 'Phân phối / Xuất kho',      'required' => true,  'kde' => [
                $this->f('who_receiver', 'Người/Đơn vị nhận', 'WHO', 'text', true),
                $this->whatQuantity(),
                $this->whatUnit(),
                $this->f('where_to', 'Địa điểm giao', 'WHERE', 'text', true),
                $this->f('what_invoice', 'Số chứng từ', 'WHAT', 'text', false),
                $this->whyNote(),
            ]],
        ];

        foreach ($templates as $tpl) {
            $categoryId = $catId($tpl['category']);
            if (!$categoryId) continue;

            DB::table('cte_templates')->updateOrInsert(
                [
                    'category_id' => $categoryId,
                    'code'        => $tpl['code'],
                ],
                [
                    'step_order'  => $tpl['step'],
                    'name_vi'     => $tpl['name_vi'],
                    'is_required' => $tpl['required'],
                    'kde_schema'  => json_encode($tpl['kde'], JSON_UNESCAPED_UNICODE),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]
            );
        }
    }
}