<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng certificates — Quản lý chứng chỉ tiêu chuẩn của doanh nghiệp.
 *
 * Thay thế cột certifications JSON trong bảng batches bằng module đầy đủ:
 * - Lưu file chứng chỉ gốc (ảnh / PDF)
 * - Theo dõi ngày hiệu lực, ngày hết hạn
 * - Multi-tenant (mỗi DN quản lý chứng chỉ riêng)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();

            // Multi-tenant
            $table->foreignId('enterprise_id')
                ->constrained('enterprises')
                ->cascadeOnDelete();

            // Tên chứng chỉ chuẩn (VietGAP, GlobalGAP, HACCP, ISO 22000, ...)
            $table->string('name', 100);

            // Tổ chức cấp (VD: Cục BVTV, TUV, SGS, Bureau Veritas, ...)
            $table->string('organization', 255)->nullable();

            // Số chứng chỉ / mã certificate
            $table->string('certificate_no', 100)->nullable();

            // Phạm vi áp dụng (scope) — VD: "Gạo ST25, sản xuất tại Cần Thơ"
            $table->string('scope', 500)->nullable();

            // Ngày cấp và hạn hiệu lực
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();

            // File chứng chỉ gốc (ảnh hoặc PDF) — lưu path trên storage
            $table->string('image_path', 500)->nullable();

            // Trạng thái: active (còn hiệu lực) | expired | revoked
            $table->enum('status', ['active', 'expired', 'revoked'])->default('active');

            // Ghi chú thêm
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['enterprise_id', 'status']);
            $table->index(['enterprise_id', 'name']);
        });

        // Bảng trung gian batch ↔ certificate (many-to-many)
        Schema::create('batch_certificates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('batch_id')
                ->constrained('batches')
                ->cascadeOnDelete();

            $table->foreignId('certificate_id')
                ->constrained('certificates')
                ->cascadeOnDelete();

            // Ghi nhận thời điểm áp dụng chứng chỉ cho lô
            $table->timestamp('applied_at')->useCurrent();

            $table->unique(['batch_id', 'certificate_id']);
            $table->index(['batch_id']);
            $table->index(['certificate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_certificates');
        Schema::dropIfExists('certificates');
    }
};