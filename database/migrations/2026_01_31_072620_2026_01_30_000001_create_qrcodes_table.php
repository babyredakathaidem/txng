<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qrcodes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enterprise_id')->constrained('enterprises')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();

            $table->string('type'); // public | private
            $table->string('token', 80)->unique();

            // Public config
            $table->string('place_name')->nullable();
            $table->decimal('allowed_lat', 10, 7)->nullable();
            $table->decimal('allowed_lng', 10, 7)->nullable();
            $table->unsignedInteger('allowed_radius_m')->nullable();

            // Private TTL
            $table->timestamp('first_scanned_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->unique(['batch_id','type']);
            $table->index(['enterprise_id','batch_id','type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qrcodes');
    }
};
