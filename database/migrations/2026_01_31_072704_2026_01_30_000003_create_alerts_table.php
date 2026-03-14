<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enterprise_id')->nullable()
                ->constrained('enterprises')->nullOnDelete();

            $table->foreignId('batch_id')->nullable()
                ->constrained('batches')->nullOnDelete();

            $table->foreignId('qrcode_id')->nullable()
                ->constrained('qrcodes')->nullOnDelete();

            $table->string('qr_type'); // public|private
            $table->string('token', 80);

            $table->string('type'); // geo_mismatch|denied_no_location|private_expired|invalid_token|public_not_configured
            $table->text('message');

            $table->timestamp('created_at')->useCurrent();

            $table->index(['token']);
            $table->index(['enterprise_id','type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
