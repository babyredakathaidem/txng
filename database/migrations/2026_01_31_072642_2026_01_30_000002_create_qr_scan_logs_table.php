<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qr_scan_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('qrcode_id')->nullable()
                ->constrained('qrcodes')->nullOnDelete();

            $table->foreignId('enterprise_id')->nullable()
                ->constrained('enterprises')->nullOnDelete();

            $table->foreignId('batch_id')->nullable()
                ->constrained('batches')->nullOnDelete();

            $table->string('qr_type'); // public|private
            $table->string('token', 80);

            // expected snapshot (public)
            $table->string('expected_place_name')->nullable();
            $table->decimal('expected_lat', 10, 7)->nullable();
            $table->decimal('expected_lng', 10, 7)->nullable();
            $table->unsignedInteger('expected_radius_m')->nullable();

            // actual scan
            $table->timestamp('scanned_at');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedInteger('distance_m')->nullable();

            // device
            $table->string('device_name', 255)->nullable();
            $table->string('device_platform', 100)->nullable();
            $table->string('ip', 64)->nullable();
            $table->text('user_agent')->nullable();

            // decision
            $table->string('decision'); // allowed|blocked|expired|denied_no_location|invalid
            $table->string('reason')->nullable();

            $table->timestamps();

            $table->index(['token']);
            $table->index(['enterprise_id','batch_id','scanned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_scan_logs');
    }
};
