<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('enterprises', function (Blueprint $table) {
            if (!Schema::hasColumn('enterprises', 'status')) {
                $table->string('status', 20)->default('pending')->after('code'); // pending|approved|rejected
                $table->index('status');
            }

            if (!Schema::hasColumn('enterprises', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('enterprises', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('approved_at')
                    ->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('enterprises', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('enterprises', 'rejected_by')) {
                $table->foreignId('rejected_by')->nullable()->after('rejected_at')
                    ->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('enterprises', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_by');
            }
        });
    }

    public function down(): void {}
};
