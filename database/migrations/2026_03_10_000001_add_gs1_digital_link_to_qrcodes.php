<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm cột gs1_digital_link vào bảng qrcodes.
 *
 * GS1 Digital Link URI format (chuẩn GS1 DL 1.2):
 *   https://domain.com/01/{gtin}/10/{batch_code}
 *
 * Cột này lưu URL đầy đủ được in vào QR code thay cho token ẩn.
 * Token vẫn giữ lại để xác thực nội bộ khi quét (geo-lock, private 48h).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qrcodes', function (Blueprint $table) {
            // URL GS1 Digital Link hoàn chỉnh — đây là nội dung QR in ra
            $table->string('gs1_digital_link', 512)->nullable()->after('token');

            // Lưu GTIN được dùng để tạo GS1 DL (có thể khác GTIN product nếu nội bộ)
            $table->string('gtin_used', 14)->nullable()->after('gs1_digital_link');
        });
    }

    public function down(): void
    {
        Schema::table('qrcodes', function (Blueprint $table) {
            $table->dropColumn(['gs1_digital_link', 'gtin_used']);
        });
    }
};