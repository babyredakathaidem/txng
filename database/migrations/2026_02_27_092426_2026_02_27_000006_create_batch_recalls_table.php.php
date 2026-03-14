<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('batch_recalls', function (Blueprint $table) {
            $table->id();

            $table->foreignId('batch_id')
                ->constrained('batches')
                ->cascadeOnDelete();

            $table->foreignId('recalled_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('reason');                     // Lý do thu hồi
            $table->text('notice_content')->nullable(); // Nội dung thông báo đến người tiêu dùng

            $table->timestamp('recalled_at');

            $table->enum('status', ['active', 'resolved'])->default('active');
            // active   = đang thu hồi
            // resolved = đã xử lý xong

            $table->foreignId('resolved_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolved_note')->nullable();

            $table->timestamps();

            $table->index(['batch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_recalls');
    }
};