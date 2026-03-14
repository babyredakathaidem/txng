<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trace_events', function (Blueprint $table) {
            $table->enum('event_category', ['observation', 'transformation', 'transfer_in', 'transfer_out'])
                ->default('observation')->after('enterprise_id');
            
            $table->string('event_code', 80)->nullable()->unique()->after('event_category')
                ->comment('Format: (251)EVT-{ENT_CODE}-{CTE}-{YYYYMM}-{SEQ3}');

            $table->foreignId('trace_location_id')->nullable()->after('event_code')
                ->constrained('trace_locations')->nullOnDelete();

            $table->foreignId('to_enterprise_id')->nullable()->after('trace_location_id')
                ->constrained('enterprises')->nullOnDelete();

            $table->foreignId('from_enterprise_id')->nullable()->after('to_enterprise_id')
                ->constrained('enterprises')->nullOnDelete();

            $table->string('external_party_name')->nullable()->after('from_enterprise_id');
            $table->string('external_ref')->nullable()->after('external_party_name');

            $table->enum('transfer_status', ['pending', 'accepted', 'rejected'])->nullable()->after('external_ref');
            $table->timestamp('accepted_at')->nullable()->after('transfer_status');
            $table->timestamp('rejected_at')->nullable()->after('accepted_at');
            $table->text('rejection_reason')->nullable()->after('rejected_at');

            $table->string('gs1_document_ref')->nullable()->after('rejection_reason')
                ->comment('AI(400) số chứng từ GS1');

            // Drop Batch ID
            $table->dropConstrainedForeignId('batch_id');
        });
    }

    public function down(): void
    {
        Schema::table('trace_events', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->after('enterprise_id')->constrained('batches')->cascadeOnDelete();
            
            $table->dropColumn([
                'event_category', 'event_code', 'trace_location_id', 
                'to_enterprise_id', 'from_enterprise_id', 'external_party_name',
                'external_ref', 'transfer_status', 'accepted_at', 
                'rejected_at', 'rejection_reason', 'gs1_document_ref'
            ]);
        });
    }
};
