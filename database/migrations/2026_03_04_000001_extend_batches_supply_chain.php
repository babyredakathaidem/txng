<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── Mở rộng bảng batches ─────────────────────────────────
        Schema::table('batches', function (Blueprint $table) {
            // Loại lô: original=tạo mới, split=tách ra, merged=gộp lại, received=nhận từ DN khác
            if (!Schema::hasColumn('batches', 'batch_type')) {
                $table->enum('batch_type', ['original', 'split', 'merged', 'received'])
                    ->default('original')
                    ->after('code');
            }
            // Số lượng hiện tại (giảm khi split/transfer)
            if (!Schema::hasColumn('batches', 'current_quantity')) {
                $table->unsignedInteger('current_quantity')->nullable()->after('quantity');
            }
            // DN tạo ra lô đầu tiên — không bao giờ thay đổi
            if (!Schema::hasColumn('batches', 'origin_enterprise_id')) {
                $table->foreignId('origin_enterprise_id')
                    ->nullable()
                    ->after('enterprise_id')
                    ->constrained('enterprises')
                    ->nullOnDelete();
            }
            // Lô cha trực tiếp (nullable — lô gốc không có cha)
            if (!Schema::hasColumn('batches', 'parent_batch_id')) {
                $table->foreignId('parent_batch_id')
                    ->nullable()
                    ->after('origin_enterprise_id')
                    ->constrained('batches')
                    ->nullOnDelete();
            }
        });

        // Mở rộng status của batches: thêm split, consumed, received
        // MySQL không cho alter enum trực tiếp → dùng raw
        DB::statement("ALTER TABLE batches MODIFY COLUMN status 
            ENUM('active','inactive','recalled','split','consumed','received') 
            NOT NULL DEFAULT 'active'");

        // ── Bảng batch_lineage ────────────────────────────────────
        // Ghi lại mối quan hệ input → output khi split/merge
        Schema::create('batch_lineage', function (Blueprint $table) {
            $table->id();
            $table->enum('transformation_type', ['split', 'merge']);
            $table->foreignId('input_batch_id')
                ->constrained('batches')
                ->cascadeOnDelete();
            $table->foreignId('output_batch_id')
                ->constrained('batches')
                ->cascadeOnDelete();
            $table->unsignedInteger('quantity')->nullable();
            $table->string('unit', 50)->nullable();
            // FK đến trace_events — TransformationEvent publish IPFS
            $table->foreignId('event_id')
                ->nullable()
                ->constrained('trace_events')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['input_batch_id', 'transformation_type']);
            $table->index(['output_batch_id']);
        });

        // ── Bảng batch_transfers ──────────────────────────────────
        // Chuyển giao lô giữa 2 DN
        Schema::create('batch_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')
                ->constrained('batches')
                ->cascadeOnDelete();
            $table->foreignId('from_enterprise_id')
                ->constrained('enterprises')
                ->restrictOnDelete();
            $table->foreignId('to_enterprise_id')
                ->constrained('enterprises')
                ->restrictOnDelete();
            $table->unsignedInteger('quantity')->nullable();
            $table->string('unit', 50)->nullable();
            $table->string('invoice_no', 100)->nullable();
            $table->text('note')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected'])
                ->default('pending');
            // FK đến trace_events — TransactionEvent publish IPFS khi accepted
            $table->foreignId('transfer_event_id')
                ->nullable()
                ->constrained('trace_events')
                ->nullOnDelete();
            $table->timestamp('transferred_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['batch_id', 'status']);
            $table->index(['to_enterprise_id', 'status']);
            $table->index(['from_enterprise_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_transfers');
        Schema::dropIfExists('batch_lineage');

        Schema::table('batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_batch_id');
            $table->dropConstrainedForeignId('origin_enterprise_id');
            $table->dropColumn(['batch_type', 'current_quantity']);
        });

        DB::statement("ALTER TABLE batches MODIFY COLUMN status 
            ENUM('active','inactive','recalled') NOT NULL DEFAULT 'active'");
    }
};