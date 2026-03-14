<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('event_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trace_event_id')->constrained('trace_events')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->foreignId('certificate_id')->nullable()->constrained('certificates')->nullOnDelete();
            
            $table->enum('result', ['pass', 'fail', 'conditional'])->default('pass');
            $table->string('reference_no', 100)->nullable();
            $table->date('issued_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('note')->nullable();
            
            $table->index('batch_id');
            $table->index('trace_event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_certificates');
    }
};
