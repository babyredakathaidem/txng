<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cte_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->constrained('product_categories')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('step_order');
            $table->string('code', 60);             // harvest, packaging, transport...
            $table->string('name_vi');              // Tên tiếng Việt
            $table->boolean('is_required')->default(false); // CTE bắt buộc theo TCVN

            // Schema 5W dạng JSON — định nghĩa các KDE field cần nhập
            // [{key, label, w, type, required, placeholder, options[]}]
            $table->json('kde_schema');

            $table->string('tcvn_note')->nullable(); // Ghi chú tiêu chuẩn áp dụng

            $table->timestamps();

            $table->unique(['category_id', 'code']);
            $table->index(['category_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cte_templates');
    }
};