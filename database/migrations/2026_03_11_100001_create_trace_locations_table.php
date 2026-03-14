<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng trace_locations — Quản lý địa điểm truy vết theo TCVN 13274:2020
 *
 * Mỗi địa điểm có:
 *  - ai_type: loại AI theo TCVN 13274:2020
 *      410 = ship_to       (địa điểm nhận hàng)
 *      411 = bill_to       (địa điểm gửi hàng / bill-to)
 *      412 = purchased_from(địa điểm mua hàng / nhà cung cấp)
 *      414 = physical      (địa điểm vật lý chung)
 *      416 = production    (địa điểm sản xuất / vùng trồng)
 *      417 = party         (địa điểm giao dịch)
 *  - gln: Global Location Number (13 chữ số, tính check digit)
 *  - Thông tin địa lý đầy đủ + toạ độ GPS
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('trace_locations', function (Blueprint $table) {
            $table->id();

            // Tenant scope
            $table->foreignId('enterprise_id')
                ->constrained('enterprises')
                ->cascadeOnDelete();

            // ── Phân loại AI theo TCVN 13274:2020 ──────────────────
            $table->enum('ai_type', [
                '410', // ship_to        — Địa điểm nhận hàng
                '411', // bill_to        — Địa điểm gửi hàng
                '412', // purchased_from — Địa điểm mua hàng / nhà cung cấp
                '414', // physical       — Địa điểm vật lý / kho
                '416', // production     — Địa điểm sản xuất / vùng trồng
                '417', // party          — Địa điểm giao dịch
            ])->default('416')->comment('AI theo TCVN 13274:2020');

            // ── Định danh GS1 ───────────────────────────────────────
            $table->string('gln', 13)->nullable()
                ->comment('Global Location Number — 13 chữ số, check digit GS1');
            $table->string('code', 50)->nullable()
                ->comment('Mã nội bộ doanh nghiệp tự đặt (mã vùng trồng, mã kho...)');

            // ── Thông tin địa điểm ──────────────────────────────────
            $table->string('name', 255)->comment('Tên địa điểm');
            $table->string('province', 100)->nullable()->comment('Tỉnh / Thành phố');
            $table->string('district', 100)->nullable()->comment('Quận / Huyện');
            $table->string('address_detail', 500)->nullable()->comment('Địa chỉ chi tiết');

            // ── Tọa độ GPS ──────────────────────────────────────────
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            // ── Thông tin riêng theo loại địa điểm ─────────────────
            // production (416) / vùng trồng
            $table->decimal('area_ha', 10, 4)->nullable()
                ->comment('Diện tích (ha) — dùng cho vùng trồng AI 416');
            $table->string('farm_code', 100)->nullable()
                ->comment('Mã vùng trồng cấp bởi cơ quan có thẩm quyền');
            $table->string('product_types', 500)->nullable()
                ->comment('Loại cây trồng / sản phẩm tại địa điểm này');

            // ── Trạng thái & Ghi chú ───────────────────────────────
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('note')->nullable();

            $table->timestamps();

            // ── Indexes ────────────────────────────────────────────
            $table->index(['enterprise_id', 'ai_type', 'status']);
            $table->index(['enterprise_id', 'gln']);
            $table->index(['enterprise_id', 'code']);
        });

        // ── Bảng pivot: gắn location vào trace_event ───────────────
        // Một sự kiện có thể có nhiều địa điểm liên quan
        // (ví dụ: event "Thu hoạch" có AI416=vùng trồng + AI414=kho tạm)
        Schema::create('trace_event_locations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('trace_event_id')
                ->constrained('trace_events')
                ->cascadeOnDelete();

            $table->foreignId('trace_location_id')
                ->constrained('trace_locations')
                ->cascadeOnDelete();

            // AI type snapshot tại thời điểm gắn
            // (có thể khác ai_type mặc định của location nếu dùng đa ngữ cảnh)
            $table->string('ai_type', 3)->nullable()
                ->comment('AI type override: 410-417');

            $table->timestamps();

            $table->unique(['trace_event_id', 'trace_location_id']);
            $table->index('trace_location_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trace_event_locations');
        Schema::dropIfExists('trace_locations');
    }
};
