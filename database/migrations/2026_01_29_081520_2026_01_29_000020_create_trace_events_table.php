<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trace_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained('enterprises')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();

            $table->string('event_type');      // VD: Thu hoạch, Đóng gói, Vận chuyển...
            $table->timestamp('event_time');   // thời gian xảy ra
            $table->string('location')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['enterprise_id', 'batch_id', 'event_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trace_events');
    }
};
