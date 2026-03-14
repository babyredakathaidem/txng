<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trace_events', function (Blueprint $table) {
            // CTE code (chuẩn hóa hoặc 'custom')
            if (!Schema::hasColumn('trace_events', 'cte_code')) {
                $table->string('cte_code', 60)->nullable()->after('note')
                    ->comment('Mã CTE chuẩn: harvest|packaging|... hoặc custom');
            }

            // KDE data — toàn bộ dữ liệu 5W có cấu trúc
            if (!Schema::hasColumn('trace_events', 'kde_data')) {
                $table->json('kde_data')->nullable()->after('cte_code')
                    ->comment('Key Data Elements theo 5W');
            }

            // Các cột index nhanh (trích từ kde_data để query/hiển thị)
            if (!Schema::hasColumn('trace_events', 'who_name')) {
                $table->string('who_name', 255)->nullable()->after('kde_data')
                    ->comment('WHO: Người/đơn vị thực hiện');
            }
            if (!Schema::hasColumn('trace_events', 'where_address')) {
                $table->string('where_address', 255)->nullable()->after('who_name')
                    ->comment('WHERE: Địa điểm');
            }
            if (!Schema::hasColumn('trace_events', 'where_lat')) {
                $table->decimal('where_lat', 10, 7)->nullable()->after('where_address');
            }
            if (!Schema::hasColumn('trace_events', 'where_lng')) {
                $table->decimal('where_lng', 10, 7)->nullable()->after('where_lat');
            }
            if (!Schema::hasColumn('trace_events', 'why_reason')) {
                $table->string('why_reason', 255)->nullable()->after('where_lng')
                    ->comment('WHY: Tiêu chuẩn/Căn cứ thực hiện');
            }

            // Đính kèm file — mảng IPFS CIDs
            // [{cid, url, name, mime_type, size_bytes}]
            if (!Schema::hasColumn('trace_events', 'attachments')) {
                $table->json('attachments')->nullable()->after('why_reason');
            }

            // IPFS fields
            if (!Schema::hasColumn('trace_events', 'ipfs_cid')) {
                $table->string('ipfs_cid', 100)->nullable()->after('attachments');
                $table->index('ipfs_cid');
            }
            if (!Schema::hasColumn('trace_events', 'ipfs_url')) {
                $table->string('ipfs_url', 500)->nullable()->after('ipfs_cid');
            }
            if (!Schema::hasColumn('trace_events', 'published_by')) {
                $table->foreignId('published_by')->nullable()->after('ipfs_url')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('trace_events', function (Blueprint $table) {
            $cols = [
                'cte_code', 'kde_data',
                'who_name', 'where_address', 'where_lat', 'where_lng', 'why_reason',
                'attachments',
                'ipfs_cid', 'ipfs_url', 'published_by',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('trace_events', $col)) {
                    if ($col === 'published_by') {
                        $table->dropConstrainedForeignId($col);
                    } else {
                        $table->dropColumn($col);
                    }
                }
            }
        });
    }
};