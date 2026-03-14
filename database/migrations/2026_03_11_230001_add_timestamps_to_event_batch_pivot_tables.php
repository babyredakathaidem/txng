<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('event_input_batches', function (Blueprint $table) {
            $table->timestamps();
        });
        Schema::table('event_output_batches', function (Blueprint $table) {
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('event_input_batches', function (Blueprint $table) {
            $table->dropTimestamps();
        });
        Schema::table('event_output_batches', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }
};
