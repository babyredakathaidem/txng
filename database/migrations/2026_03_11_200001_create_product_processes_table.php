<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bảng quy trình sản xuất gắn theo sản phẩm
        Schema::create('product_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('step_order'); // Thứ tự bước: 1, 2, 3...
            $table->string('name_vi', 100);            // Tên bước: "Trồng mía", "Thu hoạch"...
            $table->string('cte_code', 50)->nullable(); // Mapping CTE chuẩn (optional)
            $table->text('description')->nullable();    // Mô tả chi tiết bước
            $table->boolean('is_required')->default(true);
            $table->timestamps();
        });

        // Mỗi TraceEvent bước cần một QR token riêng để in
        // Thêm cột event_token vào trace_events (nếu chưa có)
        if (!Schema::hasColumn('trace_events', 'event_token')) {
            Schema::table('trace_events', function (Blueprint $table) {
                $table->string('event_token', 40)->nullable()->unique()->after('id');
                $table->unsignedBigInteger('process_step_id')->nullable()->after('batch_id');
                $table->foreign('process_step_id')
                      ->references('id')
                      ->on('product_processes')
                      ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('trace_events', function (Blueprint $table) {
            $table->dropForeign(['process_step_id']);
            $table->dropColumn(['event_token', 'process_step_id']);
        });
        Schema::dropIfExists('product_processes');
    }
};
