<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained('enterprises')->cascadeOnDelete();
            $table->string('code'); // mã lô
            $table->string('product_name');
            $table->timestamps();

            $table->unique(['enterprise_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
