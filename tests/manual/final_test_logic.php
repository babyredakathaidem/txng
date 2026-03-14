<?php
use App\Models\Enterprise;
use App\Models\Product;
use App\Models\Batch;
use App\Models\TraceEvent;
use Illuminate\Support\Facades\DB;

echo "--- BẮT ĐẦU TEST TOÀN DIỆN ---\n";

// 1. Dọn dẹp dữ liệu cũ để tránh lỗi Duplicate
TraceEvent::where('event_code', 'EVT-FINAL-TEST-001')->delete();
Batch::where('code', 'BATCH-FINAL-001')->delete();
Product::where('name', 'Gạo ST25 Xịn')->delete();
Enterprise::where('code', 'ENT-TEST-001')->delete();

// 2. Tạo Doanh nghiệp
$ent = Enterprise::create([
    'name' => 'Công ty Xuất khẩu Gạo Miền Tây',
    'code' => 'ENT-TEST-001',
    'status' => 'approved',
    'gln'  => '8930000000001'
]);
echo "1. Đã tạo Doanh nghiệp ID: " . $ent->id . "\n";

// 3. Tạo Sản phẩm
$prod = Product::create([
    'enterprise_id' => $ent->id,
    'name' => 'Gạo ST25 Xịn',
    'gtin' => '0893000000001',
    'unit' => 'kg'
]);
echo "2. Đã tạo Sản phẩm ID: " . $prod->id . "\n";

// 4. Tạo Lô hàng
$batch = Batch::create([
    'enterprise_id' => $ent->id,
    'product_id' => $prod->id,
    'code' => 'BATCH-FINAL-001',
    'product_name' => 'Gạo ST25 Xịn',
    'batch_type' => 'raw_material',
    'quantity' => 500,
    'unit' => 'kg'
]);
echo "3. Đã tạo Lô hàng ID: " . $batch->id . " (Mã: " . $batch->code . ")\n";

// 5. Tạo Sự kiện (Event-centric)
$e = TraceEvent::create([
    'enterprise_id'  => $ent->id,
    'event_category' => TraceEvent::CAT_OBSERVATION,
    'event_code'     => 'EVT-FINAL-TEST-001',
    'cte_code'       => 'harvesting',
    'status'         => 'draft',
    'event_time'     => now(),
]);
echo "4. Đã tạo Sự kiện ID: " . $e->id . " (Mã: " . $e->event_code . ")\n";

// 6. Gắn Lô hàng vào Sự kiện (Pivot table test)
$e->inputBatches()->attach($batch->id, ['quantity' => 500, 'unit' => 'kg']);
echo "5. Đã gắn Lô hàng vào Sự kiện thành công!\n";

// 7. KIỂM TRA LẠI - TRUY VẤN MỚI TOÀN BỘ TỪ DB
$checkEvent = TraceEvent::with(['inputBatches' => function($q) {
    $q->withoutGlobalScopes(); // BỎ QUA TENANT ĐỂ TEST
}])->find($e->id);

echo "--- KẾT QUẢ CUỐI CÙNG ---\n";
echo "ID Sự kiện: " . $checkEvent->id . "\n";
echo "Loại sự kiện: " . $checkEvent->event_category . "\n";
echo "Số lượng lô hàng gắn kèm: " . $checkEvent->inputBatches->count() . "\n";

if ($checkEvent->inputBatches->count() > 0) {
    $b = $checkEvent->inputBatches->first();
    echo "Mã lô hàng đầu tiên: " . $b->code . "\n";
    echo "Khối lượng trong sự kiện: " . $b->pivot->quantity . " " . $b->pivot->unit . "\n";
} else {
    echo "CẢNH BÁO: Vẫn chưa thấy lô hàng nào gắn kèm trong DB!\n";
    // Kiểm tra trực tiếp trong bảng pivot coi có dữ liệu thiệt hông
    $pivotCount = DB::table('event_input_batches')->where('trace_event_id', $e->id)->count();
    echo "Số dòng thực tế trong bảng event_input_batches: " . $pivotCount . "\n";
}
echo "--- TEST HOÀN TẤT! ---\n";
