<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('enterprises', function (Blueprint $table) {
            // code: dùng làm mã DN / tenant code (nên unique)
            $table->string('code', 50)->nullable()->unique()->after('name');

            // email DN (optional)
            $table->string('email')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('enterprises', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'email']);
        });
    }
};
