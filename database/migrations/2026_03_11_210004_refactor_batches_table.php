<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            if (!Schema::hasColumn('batches', 'origin_event_id')) {
                $table->foreignId('origin_event_id')->nullable()->after('origin_enterprise_id')
                    ->constrained('trace_events')->nullOnDelete();
            }
            
            // 1. Chuyển sang string trước để không bị lỗi ENUM cũ
            $table->string('batch_type')->change();
            
            if (!Schema::hasColumn('batches', 'gtin_cached')) {
                $table->string('gtin_cached', 14)->nullable()->after('unit');
            }
            if (!Schema::hasColumn('batches', 'gs1_128_label')) {
                $table->string('gs1_128_label', 500)->nullable()->after('gtin_cached');
            }
        });

        // 2. Data Migration: Ánh xạ dữ liệu cũ sang chuẩn mới (TCVN)
        // original/received -> raw_material
        DB::table('batches')->whereIn('batch_type', ['original', 'received'])->update(['batch_type' => 'raw_material']);
        // merged/split -> wip (Work In Progress)
        DB::table('batches')->whereIn('batch_type', ['merged', 'split'])->update(['batch_type' => 'wip']);
        // Các giá trị khác nếu có mặc định về wip
        DB::table('batches')->whereNotIn('batch_type', ['raw_material', 'wip', 'finished'])->update(['batch_type' => 'wip']);

        // 3. Cuối cùng mới định nghĩa lại ENUM chuẩn
        DB::statement("ALTER TABLE batches MODIFY COLUMN batch_type ENUM('raw_material','wip','finished')");
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('origin_event_id');
            $table->dropColumn(['gtin_cached', 'gs1_128_label']);
            
            // Revert enum - giả định enum cũ (cần kiểm tra thực tế, ở đây revert về string hoặc enum cũ nếu biết)
            $table->string('batch_type')->change();
        });
    }
};
