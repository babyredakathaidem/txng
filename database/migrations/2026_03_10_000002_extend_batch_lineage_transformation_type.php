<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Thêm giá trị 'transfer' vào enum transformation_type của bảng batch_lineage.
 *
 * Lý do: Khi DN A chuyển lô cho DN B, cần ghi lineage để đảm bảo chuỗi
 * cung ứng không bị đứt đoạn khi truy xuất ngược (QrScanController@loadEvents).
 *
 * Giá trị enum sau khi update: ['split', 'merge', 'transfer']
 */
return new class extends Migration
{
    public function up(): void
    {
        // MySQL: không thể dùng ->change() trực tiếp với enum → dùng raw
        DB::statement("
            ALTER TABLE batch_lineage
            MODIFY COLUMN transformation_type
            ENUM('split', 'merge', 'transfer') NOT NULL
        ");
    }

    public function down(): void
    {
        // Xóa các record transfer trước khi rollback để tránh lỗi constraint
        DB::statement("DELETE FROM batch_lineage WHERE transformation_type = 'transfer'");

        DB::statement("
            ALTER TABLE batch_lineage
            MODIFY COLUMN transformation_type
            ENUM('split', 'merge') NOT NULL
        ");
    }
};