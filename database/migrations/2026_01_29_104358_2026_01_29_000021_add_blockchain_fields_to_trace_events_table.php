<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trace_events', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('note'); // draft | published
            $table->string('content_hash')->nullable()->after('status'); // hash nội dung
            $table->string('tx_hash')->nullable()->after('content_hash'); // tx blockchain (để sau)
            $table->timestamp('published_at')->nullable()->after('tx_hash');
        });
    }

    public function down(): void
    {
        Schema::table('trace_events', function (Blueprint $table) {
            $table->dropColumn(['status','content_hash','tx_hash','published_at']);
        });
    }
};
