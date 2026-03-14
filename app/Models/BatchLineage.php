<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchLineage extends Model
{
    protected $table = 'batch_lineage';
    protected $fillable = [
        'transformation_type',
        'input_batch_id',
        'output_batch_id',
        'quantity',
        'unit',
        'event_id',
    ];

    public function inputBatch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'input_batch_id');
    }

    public function outputBatch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'output_batch_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(TraceEvent::class, 'event_id');
    }
}