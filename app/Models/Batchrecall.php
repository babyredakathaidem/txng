<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchRecall extends Model
{
    protected $fillable = [
        'batch_id',
        'recalled_by',
        'reason',
        'notice_content',
        'recalled_at',
        'status',
        'resolved_by',
        'resolved_at',
        'resolved_note',
    ];

    protected $casts = [
        'recalled_at'  => 'datetime',
        'resolved_at'  => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function recalledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recalled_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
    public function recaller()
    {
        return $this->belongsTo(User::class, 'recalled_by');
    }
}