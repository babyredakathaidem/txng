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

        // Mở rộng batches: status + completed_at + product_id (nếu chưa có)
        Schema::table('batches', function (Blueprint $table) {
            if (!Schema::hasColumn('batches', 'status')) {
                $table->enum('status', ['active', 'completed', 'recalled'])
                    ->default('active')
                    ->after('unit');
            }
            if (!Schema::hasColumn('batches', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('status');
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
            $table->dropColumn(array_filter(
                ['status', 'completed_at'],
                fn($col) => Schema::hasColumn('batches', $col)
            ));
        });
    }
};