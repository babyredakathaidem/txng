<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchTransfer extends Model
{
    protected $fillable = [
        'batch_id',
        'from_enterprise_id',
        'to_enterprise_id',
        'quantity',
        'unit',
        'invoice_no',
        'note',
        'status',
        'transfer_event_id',
        'accepted_event_id',  // Link với sự kiện nhận hàng tại đích
        'sscc_code',          // Serial Shipping Container Code (AI 00)
        'transferred_at',
        'accepted_at',
        'rejected_at',
        'rejection_reason',
    ];

    public function validateSscc(): bool
    {
        return \App\Services\GS1Validator::isValid($this->sscc_code);
    }

    protected $casts = [
        'transferred_at' => 'datetime',
        'accepted_at'    => 'datetime',
        'rejected_at'    => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function fromEnterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class, 'from_enterprise_id');
    }

    public function toEnterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class, 'to_enterprise_id');
    }

    public function transferEvent(): BelongsTo
    {
        return $this->belongsTo(TraceEvent::class, 'transfer_event_id');
    }

    public function isPending(): bool   { return $this->status === 'pending'; }
    public function isAccepted(): bool  { return $this->status === 'accepted'; }
    public function isRejected(): bool  { return $this->status === 'rejected'; }
}