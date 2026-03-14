<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('enterprises', function (Blueprint $table) {
            if (Schema::hasColumn('enterprises', 'tax_code')) {
                $table->dropColumn('tax_code');
            }
            if (Schema::hasColumn('enterprises', 'address')) {
                $table->dropColumn('address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enterprises', function (Blueprint $table) {
            if (!Schema::hasColumn('enterprises', 'tax_code')) {
                $table->string('tax_code', 255)->nullable();
            }
            if (!Schema::hasColumn('enterprises', 'address')) {
                $table->string('address', 255)->nullable();
            }
        });
    }
};