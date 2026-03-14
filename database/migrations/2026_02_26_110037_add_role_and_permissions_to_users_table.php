<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                // enterprise_admin | enterprise_staff | null (super admin không dùng role)
                $table->string('role', 30)->nullable()->after('enterprise_id');
            }
            if (!Schema::hasColumn('users', 'permissions')) {
                // JSON array: ["enterprise.products.view", "enterprise.batches.manage", ...]
                $table->json('permissions')->nullable()->after('role');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'permissions']);
        });
    }
};