<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mở rộng bảng batch_recalls để hỗ trợ Cascade Recall:
 *
 *  - parent_recall_id: FK đến recall gốc (nếu là recall cascade)
 *  - ipfs_cid:         CID của sự kiện recall đã ghi lên IPFS
 *
 * TCVN 12850:2019 — mỗi lệnh thu hồi phải được lưu bằng chứng bất biến.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batch_recalls', function (Blueprint $table) {
            // FK đến recall gốc — null nếu đây là recall chính, có giá trị nếu là cascade
            if (! Schema::hasColumn('batch_recalls', 'parent_recall_id')) {
                $table->foreignId('parent_recall_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('batch_recalls')
                    ->nullOnDelete();
            }

            // CID của sự kiện recall trên IPFS — bằng chứng bất biến
            if (! Schema::hasColumn('batch_recalls', 'ipfs_cid')) {
                $table->string('ipfs_cid', 100)->nullable()->after('notice_content');
            }

            // resolved_by, resolved_at, resolved_note (nếu chưa có)
            if (! Schema::hasColumn('batch_recalls', 'resolved_by')) {
                $table->foreignId('resolved_by')
                    ->nullable()
                    ->after('recalled_by')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('batch_recalls', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('recalled_at');
            }

            if (! Schema::hasColumn('batch_recalls', 'resolved_note')) {
                $table->text('resolved_note')->nullable()->after('resolved_at');
            }
        });

        // Index để tra nhanh cascade recalls
        Schema::table('batch_recalls', function (Blueprint $table) {
            $table->index('parent_recall_id');
        });
    }

    public function down(): void
    {
        Schema::table('batch_recalls', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_recall_id');
            $table->dropColumn(['ipfs_cid', 'resolved_by', 'resolved_at', 'resolved_note']);
        });
    }
};