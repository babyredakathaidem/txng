<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class EventCertificate extends Model
{
    protected $fillable = [
        'trace_event_id',
        'batch_id',
        'certificate_id',
        'result',
        'reference_no',
        'issued_date',
        'expiry_date',
        'note',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'expiry_date' => 'date',
    ];

    // ── Relations ─────────────────────────────────────────

    public function traceEvent(): BelongsTo
    {
        return $this->belongsTo(TraceEvent::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    // ── Helpers ───────────────────────────────────────────

    public function isExpired(): bool
    {
        if (!$this->expiry_date) return false;
        return $this->expiry_date->isPast();
    }

    public function getDisplayName(): string
    {
        return $this->certificate ? $this->certificate->name : 'Chứng chỉ tùy chỉnh';
    }
}
