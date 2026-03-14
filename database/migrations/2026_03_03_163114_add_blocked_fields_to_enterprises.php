<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('enterprises', function (Blueprint $table) {
            $table->timestamp('blocked_at')->nullable()->after('rejection_reason');
            $table->foreignId('blocked_by')->nullable()->after('blocked_at')
                ->constrained('users')->nullOnDelete();
            $table->text('blocked_reason')->nullable()->after('blocked_by');
        });
    }

    public function down(): void
    {
        Schema::table('enterprises', function (Blueprint $table) {
            $table->dropColumn(['blocked_at', 'blocked_by', 'blocked_reason']);
        });
    }
};