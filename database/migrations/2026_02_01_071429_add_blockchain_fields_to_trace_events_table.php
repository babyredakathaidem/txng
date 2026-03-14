<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trace_events', function (Blueprint $table) {
            // chỉ add nếu chưa có
            if (!Schema::hasColumn('trace_events', 'status')) {
                $table->string('status', 20)->default('draft')->after('note'); // draft|published
            }

            if (!Schema::hasColumn('trace_events', 'content_hash')) {
                $table->string('content_hash', 64)->nullable()->after('status');
                $table->index('content_hash');
            }

            if (!Schema::hasColumn('trace_events', 'tx_hash')) {
                $table->string('tx_hash', 120)->nullable()->after('content_hash');
                $table->index('tx_hash');
            }

            if (!Schema::hasColumn('trace_events', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('tx_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trace_events', function (Blueprint $table) {
            $table->dropIndex(['batch_id', 'status']);
            $table->dropIndex(['content_hash']);
            $table->dropIndex(['tx_hash']);

            $table->dropColumn(['status', 'content_hash', 'tx_hash', 'published_at']);
        });
    }
};
