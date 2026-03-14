<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Support\Collection;
use App\Services\GS1Service;
use App\Services\QrCodeService;
use App\Models\Certificate;

class Batch extends Model
{
    use BelongsToTenant;

    const TYPE_RAW = 'raw_material';
    const TYPE_WIP = 'wip';
    const TYPE_FINISHED = 'finished';

    protected $fillable = [
        'enterprise_id',
        'product_id',
        'code',
        'product_name',
        'description',
        'production_date',
        'expiry_date',
        'quantity',
        'unit',
        'status',
        'batch_type',
        'current_quantity',
        'origin_enterprise_id',
        'origin_event_id',
        'parent_batch_id',
        'completed_at',
        'certifications',
        'gtin_cached',
        'gs1_128_label',
    ];

    protected $casts = [
        'production_date' => 'date',
        'expiry_date'     => 'date',
        'completed_at'    => 'datetime',
        'certifications'  => 'array',
    ];

    // ── Relations ─────────────────────────────────────────

    public function originEvent(): BelongsTo
    {
        return $this->belongsTo(TraceEvent::class, 'origin_event_id');
    }

    /**
     * Các sự kiện mà lô này đóng vai trò nguyên liệu (Input)
     */
    public function inputEvents(): BelongsToMany
    {
        return $this->belongsToMany(TraceEvent::class, 'event_input_batches', 'batch_id', 'trace_event_id')
            ->withPivot(['quantity', 'unit'])
            ->withTimestamps();
    }

    /**
     * Các sự kiện mà lô này được tạo ra (Output)
     */
    public function outputEvents(): BelongsToMany
    {
        return $this->belongsToMany(TraceEvent::class, 'event_output_batches', 'batch_id', 'trace_event_id')
            ->withPivot(['quantity', 'unit'])
            ->withTimestamps();
    }

    public function certificates(): BelongsToMany
    {
        return $this->belongsToMany(Certificate::class, 'batch_certificates')
            ->withPivot(['applied_at']);
    }

    public function eventCertificates(): HasMany
    {
        return $this->hasMany(EventCertificate::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function originEnterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class, 'origin_enterprise_id');
    }

    /**
     * Quan hệ với Sự kiện (Event-centric)
     * Lô hàng đóng vai trò là đầu vào (Input) của sự kiện.
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(TraceEvent::class, 'event_input_batches', 'batch_id', 'trace_event_id')
            ->withPivot(['quantity', 'unit'])
            ->withTimestamps();
    }

    public function publishedEvents(): BelongsToMany
    {
        return $this->belongsToMany(TraceEvent::class, 'event_input_batches', 'batch_id', 'trace_event_id')
            ->withPivot(['quantity', 'unit'])
            ->withTimestamps()
            ->where('trace_events.status', 'published');
    }

    public function qrcodes(): HasMany
    {
        return $this->hasMany(Qrcode::class);
    }

    public function recalls(): HasMany
    {
        return $this->hasMany(BatchRecall::class)->latest();
    }

    // ── Helpers ───────────────────────────────────────────

    public function isConsumed(): bool
    {
        return $this->current_quantity <= 0;
    }

    public function isTransferred(): bool
    {
        return !is_null($this->origin_enterprise_id) && $this->origin_enterprise_id !== $this->enterprise_id;
    }

    public function isActive(): bool
    {
        return $this->status === 'active' || $this->status === 'completed';
    }

    /**
     * Lấy GTIN-14 và cache lại để query nhanh
     */
    public function getGtinCached(): string
    {
        if ($this->gtin_cached) return $this->gtin_cached;

        $service = new QrCodeService();
        $gtin = $service->resolveGtin($this);
        
        $this->update(['gtin_cached' => $gtin]);
        return $gtin;
    }

    /**
     * Lấy nhãn GS1-128 và cache
     */
    public function getGs1128Label(): string
    {
        if ($this->gs1_128_label) return $this->gs1_128_label;

        $service = new GS1Service();
        $label = $service->generateLabel($this);
        
        $this->update(['gs1_128_label' => $label]);
        return $label;
    }

    public function getDisplayName(): string
    {
        return $this->product?->name ?? $this->product_name ?? 'Không tên';
    }

    // ═══════════════════════════════════════════════════════════════════
    // GIỮ NGUYÊN LOGIC CŨ (RECALL, LINEAGE...)
    // ═══════════════════════════════════════════════════════════════════

    public function getAllDescendants(int $maxDepth = 10, array &$visited = []): Collection
    {
        $visited[] = $this->id;
        if ($maxDepth <= 0) return collect();
        $descendants = collect();

        $splitChildren = self::where('parent_batch_id', $this->id)
            ->whereNotIn('id', $visited)->get();

        foreach ($splitChildren as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants($maxDepth - 1, $visited));
        }

        $mergeOutputIds = BatchLineage::where('input_batch_id', $this->id)
            ->where('transformation_type', 'merge')->pluck('output_batch_id');

        $mergeOutputs = self::whereIn('id', $mergeOutputIds)
            ->whereNotIn('id', $visited)->get();

        foreach ($mergeOutputs as $output) {
            $descendants->push($output);
            $descendants = $descendants = $descendants->merge($output->getAllDescendants($maxDepth - 1, $visited));
        }

        return $descendants->unique('id')->values();
    }
}
