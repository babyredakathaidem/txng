<?php
use App\Models\TraceEvent;
use App\Models\Batch;
use App\Models\Enterprise;

$ent = Enterprise::first();
$batch = Batch::first();

if (!$ent || !$batch) {
    echo "Lỗi: Máy ông hổng có Enterprise hay Batch nào hết trơn!\n";
    exit;
}

echo "Enterprise ID: " . $ent->id . "\n";
echo "Batch ID: " . $batch->id . "\n";

$e = TraceEvent::create([
    'enterprise_id'  => $ent->id,
    'event_category' => TraceEvent::CAT_OBSERVATION,
    'event_code'     => 'TEST-EVT-' . time(),
    'cte_code'       => 'planting',
    'status'         => 'draft',
    'event_time'     => now(),
]);

echo "Created Event Code: " . $e->event_code . "\n";
echo "isObservation: " . ($e->isObservation() ? "YES" : "NO") . "\n";

$e->inputBatches()->attach($batch->id, ['quantity' => 10, 'unit' => 'tấn']);
$refreshed = TraceEvent::with('inputBatches')->find($e->id);

echo "Input Batches Count: " . $refreshed->inputBatches->count() . "\n";
echo "First Batch Code: " . $refreshed->inputBatches->first()->code . "\n";
echo "Test thành công mỹ mãn!\n";
