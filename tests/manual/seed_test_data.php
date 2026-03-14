<?php
use App\Models\Enterprise;
use App\Models\Product;
use App\Models\Batch;
use App\Models\TraceEvent;

// 1. Tạo HTX XYZ
$ent = Enterprise::firstOrCreate(
    ['tax_id' => '8930000001'],
    [
        'name' => 'HTX Nông nghiệp XYZ (An Giang)',
        'code' => 'HTX-XYZ',
        'status' => 'approved',
        'address_detail' => 'Châu Thành, An Giang',
        'gln' => '8931234567890'
    ]
);

// 2. Tạo Sản phẩm
$prod = Product::firstOrCreate(
    ['enterprise_id' => $ent->id, 'name' => 'Lúa ST25'],
    ['gtin' => '0893521345678', 'unit' => 'tấn']
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
