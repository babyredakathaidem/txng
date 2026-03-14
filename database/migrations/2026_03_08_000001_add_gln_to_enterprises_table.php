<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('enterprises', function (Blueprint $table) {

            if (!Schema::hasColumn('enterprises', 'gln')) {
                $table->string('gln', 13)
                    ->nullable()
                    ->unique()
                    ->after('code')
                    ->comment('GS1 GLN — Global Location Number 13 số, prefix 893 (Vietnam)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enterprises', function (Blueprint $table) {
            if (Schema::hasColumn('enterprises', 'gln')) {
                $table->dropUnique(['gln']);
                $table->dropColumn('gln');
            }
        });
    }
};