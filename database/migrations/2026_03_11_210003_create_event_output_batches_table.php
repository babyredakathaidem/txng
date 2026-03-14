<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('event_output_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trace_event_id')->constrained('trace_events')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->decimal('quantity', 12, 3)->nullable();
            $table->string('unit', 50)->nullable();
            $table->unique(['trace_event_id', 'batch_id']);
            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_output_batches');
    }
};
