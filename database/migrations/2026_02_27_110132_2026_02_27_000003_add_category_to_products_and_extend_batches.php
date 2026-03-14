<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Thêm category_id vào products
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'category_id')) {
                $table->foreignId('category_id')
                    ->nullable()
                    ->after('enterprise_id')
                    ->constrained('product_categories')
                    ->nullOnDelete();
            }
        });

        // Mở rộng batches — thêm tất cả cột còn thiếu
        // Không dùng ->after() để tránh lỗi cột tham chiếu chưa tồn tại
        Schema::table('batches', function (Blueprint $table) {
            if (!Schema::hasColumn('batches', 'product_id')) {
                $table->foreignId('product_id')
                    ->nullable()
                    ->constrained('products')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('batches', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('batches', 'production_date')) {
                $table->date('production_date')->nullable();
            }
            if (!Schema::hasColumn('batches', 'expiry_date')) {
                $table->date('expiry_date')->nullable();
            }
            if (!Schema::hasColumn('batches', 'quantity')) {
                $table->unsignedInteger('quantity')->nullable();
            }
            if (!Schema::hasColumn('batches', 'unit')) {
                $table->string('unit', 50)->nullable();
            }
            if (!Schema::hasColumn('batches', 'status')) {
                $table->enum('status', ['active', 'completed', 'recalled'])->default('active');
            }
            if (!Schema::hasColumn('batches', 'completed_at')) {
                $table->timestamp('completed_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'category_id')) {
                $table->dropConstrainedForeignId('category_id');
            }
        });

        Schema::table('batches', function (Blueprint $table) {
            $cols = ['description', 'production_date', 'expiry_date', 'quantity', 'unit', 'status', 'completed_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('batches', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('batches', 'product_id')) {
                $table->dropConstrainedForeignId('product_id');
            }
        });
    }
};