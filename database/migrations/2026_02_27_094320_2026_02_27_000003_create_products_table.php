<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('products')) return;

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')
                ->constrained('enterprises')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('gtin', 14)->nullable()->comment('Mã GS1/GTIN sản phẩm');
            $table->text('description')->nullable();
            $table->string('image_path', 500)->nullable();
            $table->string('unit', 50)->nullable()->comment('Đơn vị tính: kg, hộp, thùng...');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->unique(['enterprise_id', 'gtin']);
            $table->index(['enterprise_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};