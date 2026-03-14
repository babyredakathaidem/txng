<?php
use App\Models\Enterprise;
use App\Models\Product;
use App\Models\Batch;
use App\Models\TraceEvent;

// 1. Tạo HTX XYZ - Dùng code thay cho tax_id vì tax_id hổng có trong DB
$ent = Enterprise::firstOrCreate(
    ['code' => 'HTX-XYZ'],
    [
        'name' => 'HTX Nông nghiệp XYZ (An Giang)',
        'status' => 'approved',
        'address_detail' => 'Châu Thành, An Giang',
        'gln' => '8931234567890'
    ]
);

// 2. Tạo Sản phẩm
$prod = Product::firstOrCreate(
    ['name' => 'Lúa ST25'],
    ['enterprise_id' => $ent->id, 'gtin' => '0893521345678', 'unit' => 'tấn']
);

// 3. Tạo Lô hàng nguyên liệu
$batch = Batch::firstOrCreate(
    ['code' => 'LOT-LUA-XYZ-001'],
    [
        'enterprise_id' => $ent->id,
        'product_id' => $prod->id,
        'product_name' => 'Lúa ST25',
        'batch_type' => 'raw_material',
        'quantity' => 20,
        'unit' => 'tấn'
    ]
);

echo "Đã tạo dữ liệu mẫu thành công!\n";
echo "Enterprise ID: " . $ent->id . "\n";
echo "Batch ID: " . $batch->id . "\n";

// 4. TEST SỰ KIỆN LUÔN CHO ÔNG COI
$e = TraceEvent::create([
    'enterprise_id'  => $ent->id,
    'event_category' => 'observation',
    'event_code'     => 'EVT-XYZ-PLANT-' . time(),
    'cte_code'       => 'planting',
    'status'         => 'draft',
    'event_time'     => now(),
]);

echo "Created Event Code: " . $e->event_code . "\n";
echo "isObservation: " . ($e->isObservation() ? "YES" : "NO") . "\n";

$e->inputBatches()->attach($batch->id, ['quantity' => 10, 'unit' => 'tấn']);
echo "Gắn Batch vào Sự kiện: ĐÃ XONG!\n";
echo "Số lượng Input Batches: " . $e->inputBatches()->count() . "\n";
echo "Test thành công 100% rồi nha cha nội!\n";
