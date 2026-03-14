<?php

/**
 * Test script cho TraceLocation + GS1Service (AI 01 và AI 410-417)
 * Chạy bằng: php artisan tinker --execute="require 'tests/manual/test_trace_location.php';"
 *
 * Hoặc copy từng block vào tinker:
 *   php artisan tinker
 */

use App\Services\GS1Service;
use App\Services\GS1Validator;
use App\Models\TraceLocation;

$gs1 = new GS1Service();

echo "\n" . str_repeat('=', 60) . "\n";
echo "  TEST 1: Mã truy vết vật phẩm — AI (01) TCVN 13274:2020\n";
echo str_repeat('=', 60) . "\n";

$testGtins = [
    '8934588000015',  // GTIN-13 Việt Nam (893...)
    '893458800001',   // GTIN-12
    '12345670',       // GTIN-8
];

foreach ($testGtins as $gtin) {
    $ai01 = $gs1->buildProductTraceCode($gtin, packagingLevel: 0);
    $gtin14extracted = $gs1->extractGtin14FromAi01($ai01);
    $valid = GS1Validator::isValid($gtin14extracted ?? '');

    echo "\nInput GTIN  : {$gtin}\n";
    echo "AI(01) str  : {$ai01}\n";
    echo "GTIN-14     : {$gtin14extracted}\n";
    echo "Check digit : " . ($valid ? "✅ HỢP LỆ" : "❌ SAI") . "\n";
}

// Test packaging level khác nhau (N1 = 0-9)
echo "\n--- Kiểm tra packaging level ---\n";
$gtin = '8934588000015';
foreach ([0, 1, 3, 9] as $level) {
    $ai01 = $gs1->buildProductTraceCode($gtin, $level);
    echo "Level {$level}: {$ai01}\n";
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "  TEST 2: Mã truy vết địa điểm — AI (410-417) TCVN 13274:2020\n";
echo str_repeat('=', 60) . "\n";

// Sinh GLN hợp lệ trước
$gln = $gs1->generateGLN(1); // enterprise_id = 1
echo "\nGLN sinh cho enterprise 1: {$gln}\n";
echo "Format hiển thị: " . $gs1->formatGLN($gln) . "\n";
echo "GLN hợp lệ: " . (GS1Validator::isValid($gln) ? "✅" : "❌") . "\n";

// Test từng AI type
$aiTypes = ['410', '411', '412', '414', '416', '417'];
echo "\n--- Tạo mã địa điểm cho từng AI type ---\n";
foreach ($aiTypes as $aiType) {
    try {
        $code = $gs1->buildLocationCode($aiType, $gln);
        echo "AI({$aiType}): {$code}  ✅\n";
    } catch (\Exception $e) {
        echo "AI({$aiType}): ❌ {$e->getMessage()}\n";
    }
}

// Test GLN sai
echo "\n--- Test GLN sai (check digit lỗi) ---\n";
try {
    $gs1->buildLocationCode('416', '8930000000019'); // check digit sai
    echo "❌ Phải throw exception nhưng không throw\n";
} catch (\InvalidArgumentException $e) {
    echo "✅ Catch đúng exception: " . $e->getMessage() . "\n";
}

// Test AI type không hợp lệ
echo "\n--- Test AI type không hợp lệ ---\n";
try {
    $gs1->buildLocationCode('999', $gln);
    echo "❌ Phải throw exception nhưng không throw\n";
} catch (\InvalidArgumentException $e) {
    echo "✅ Catch đúng exception: " . $e->getMessage() . "\n";
}

// buildLocationCodeLoose — không validate, dùng cho demo
echo "\n--- buildLocationCodeLoose (không validate) ---\n";
$loose = $gs1->buildLocationCodeLoose('416', '12345');
echo "Loose: {$loose}\n"; // (416)0000000012345 (pad trái)

echo "\n" . str_repeat('=', 60) . "\n";
echo "  TEST 3: GS1-128 đầy đủ kèm địa điểm sản xuất\n";
echo str_repeat('=', 60) . "\n";

$result = $gs1->buildGS1_128WithLocation(
    gtin: '8934588000015',
    lotNumber: 'LG07001',
    expiryDate: '2027-03-07',
    locationGln: $gln,
    locationAi: '416'
);
echo "\nGS1-128 full: {$result}\n";
// Expected: (01)08934588000012(10)LG07001(17)270307(416)8930000000018

echo "\n" . str_repeat('=', 60) . "\n";
echo "  TEST 4: buildIdentifiers() — field ai01_product mới\n";
echo str_repeat('=', 60) . "\n";

$ids = $gs1->buildIdentifiers(
    gtin: '8934588000015',
    lotNumber: 'LG07001',
    gln: $gln,
    expiryDate: '2027-12-31'
);
echo "\n";
print_r($ids);

echo "\n" . str_repeat('=', 60) . "\n";
echo "  TEST 5: TraceLocation model (cần DB)\n";
echo str_repeat('=', 60) . "\n";

try {
    // Kiểm tra bảng tồn tại
    $count = TraceLocation::count();
    echo "\nBảng trace_locations: ✅ TỒN TẠI (hiện có {$count} bản ghi)\n";

    // Thử tạo 1 location test
    $loc = TraceLocation::create([
        'enterprise_id'  => 1, // đảm bảo enterprise_id=1 tồn tại
        'ai_type'        => '416',
        'gln'            => $gln,
        'code'           => 'VT-TEST-01',
        'name'           => 'Vùng trồng test An Giang',
        'province'       => 'An Giang',
        'district'       => 'Long Xuyên',
        'address_detail' => 'Ấp Mỹ Hòa, Xã Mỹ Hòa Hưng',
        'lat'            => 10.3753,
        'lng'            => 105.4394,
        'area_ha'        => 2.5,
        'farm_code'      => 'AG-LX-2024-001',
        'product_types'  => 'Lúa, Rau màu',
        'status'         => 'active',
    ]);

    echo "Tạo location: ✅ ID={$loc->id}\n";
    echo "AI label    : {$loc->ai_label}\n";
    echo "Full address: {$loc->full_address}\n";
    echo "GS1 AI str  : " . ($loc->toGs1AiString() ?? 'null (chưa có GLN)') . "\n";
    echo "Has GPS     : " . ($loc->hasGps() ? '✅' : '❌') . "\n";
    echo "Is production: " . ($loc->isProductionSite() ? '✅' : '❌') . "\n";
    echo "\nIPFS fragment:\n";
    print_r($loc->toIpfsFragment());

    // Dọn dẹp sau test
    $loc->delete();
    echo "\nDọn bản ghi test: ✅\n";

} catch (\Exception $e) {
    echo "\n⚠️  Lỗi DB (migration chưa chạy?): " . $e->getMessage() . "\n";
    echo "→ Chạy: php artisan migrate --path=database/migrations/2026_03_11_100001_create_trace_locations_table.php\n";
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "  DONE\n";
echo str_repeat('=', 60) . "\n\n";
