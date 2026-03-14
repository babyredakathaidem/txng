<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class TraceEvent extends Model
{
    // EPCIS Categories
    const CAT_OBSERVATION = 'observation';
    const CAT_TRANSFORMATION = 'transformation';
    const CAT_TRANSFER_IN = 'transfer_in';
    const CAT_TRANSFER_OUT = 'transfer_out';

    const CATEGORY_LABELS = [
        self::CAT_OBSERVATION    => 'Quan sát/Ghi nhận',
        self::CAT_TRANSFORMATION => 'Biến đổi/Chế biến',
        self::CAT_TRANSFER_IN    => 'Nhập kho/Tiếp nhận',
        self::CAT_TRANSFER_OUT   => 'Xuất kho/Chuyển giao',
    ];

    const CTE_ICONS = [
        'planting'    => '🌱',
        'fertilizing' => '🧴',
        'spraying'    => '💦',
        'harvesting'  => '🚜',
        'processing'  => '🏭',
        'packaging'   => '📦',
        'transport'   => '🚚',
        'delivery'    => '🏁',
        'inspection'  => '🔍',
    ];

    protected $fillable = [
        'enterprise_id',
        'event_category',
        'event_code',
        'event_token',
        'process_step_id',
        'event_type', // legacy
        'cte_code',
        'event_time',
        'trace_location_id',
        'to_enterprise_id',
        'from_enterprise_id',
        'external_party_name',
        'external_ref',
        'transfer_status',
        'accepted_at',
        'rejected_at',
        'rejection_reason',
        'gs1_document_ref',
        'kde_data',
        'who_name',
        'where_address',
        'where_lat',
        'where_lng',
        'why_reason',
        'attachments',
        'status',
        'content_hash',
        'ipfs_cid',
        'ipfs_url',
        'tx_hash',
        'published_at',
        'published_by',
    ];

    protected $casts = [
        'event_time'   => 'datetime',
        'accepted_at'  => 'datetime',
        'rejected_at'  => 'datetime',
        'published_at' => 'datetime',
        'kde_data'     => 'array',
        'attachments'  => 'array',
        'where_lat'    => 'float',
        'where_lng'    => 'float',
    ];

    // ── Relations ─────────────────────────────────────────

    public function inputBatches(): BelongsToMany
    {
        return $this->belongsToMany(Batch::class, 'event_input_batches', 'trace_event_id', 'batch_id')
            ->withPivot(['quantity', 'unit'])
            ->withTimestamps();
    }

    public function outputBatches(): BelongsToMany
    {
        return $this->belongsToMany(Batch::class, 'event_output_batches', 'trace_event_id', 'batch_id')
            ->withPivot(['quantity', 'unit'])
            ->withTimestamps();
    }

    public function traceLocation(): BelongsTo
    {
        return $this->belongsTo(TraceLocation::class, 'trace_location_id');
    }

    public function toEnterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class, 'to_enterprise_id');
    }

    public function fromEnterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class, 'from_enterprise_id');
    }

    public function eventCertificates(): HasMany
    {
        return $this->hasMany(EventCertificate::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class); // Backward compatibility nếu cần
    }

    // ── Helpers ───────────────────────────────────────────

    public function isObservation(): bool
    {
        return $this->event_category === self::CAT_OBSERVATION;
    }

    public function isTransformation(): bool
    {
        return $this->event_category === self::CAT_TRANSFORMATION;
    }

    public function isTransferIn(): bool
    {
        return $this->event_category === self::CAT_TRANSFER_IN;
    }

    public function isTransferOut(): bool
    {
        return $this->event_category === self::CAT_TRANSFER_OUT;
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isPending(): bool
    {
        return $this->transfer_status === 'pending';
    }

    public function canAddInputs(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Tự động sinh mã truy vết chuẩn Thông tư 02
     * Format: EVT-{ENT_CODE}-{CTE_UPPER_7}-{YYYYMM}-{SEQ3}
     */
    public static function generateEventCode(string $enterpriseCode, string $cteCode, int $seq): string
    {
        $cte = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $cteCode), 0, 7));
        $date = Carbon::now()->format('Ym');
        $seqStr = str_pad($seq, 3, '0', STR_PAD_LEFT);

        return "EVT-{$enterpriseCode}-{$cte}-{$date}-{$seqStr}";
    }

    /**
     * Payload chuẩn upload IPFS — bất biến sau publish
     */
    public function toIpfsPayload(): array
    {
        // Load relations nếu chưa load
        $this->loadMissing([
            'inputBatches',
            'outputBatches',
            'traceLocation',
            'enterprise',
            'eventCertificates.certificate' // Lấy đúng qua relation certificate
        ]);

        $gs1Service = app(\App\Services\GS1Service::class);

        return [
            'system'        => 'AGU Traceability',
            'version'       => '2.0',
            'tcvn_ref'      => 'TCVN 12850:2019',
            'event_code'    => $this->event_code,
            'ai251'         => "(251){$this->event_code}",
            'ai400'         => $this->gs1_document_ref ? "(400){$this->gs1_document_ref}" : null,
            'category'      => self::CATEGORY_LABELS[$this->event_category] ?? $this->event_category,
            'cte_code'      => $this->cte_code,

            // WHEN
            'event_time'    => optional($this->event_time)->toISOString(),

            // WHERE
            'location_gln'  => $this->traceLocation ? $this->traceLocation->gln : null,
            'address'       => $this->where_address ?? optional($this->traceLocation)->address_detail,
            'gps'           => ['lat' => $this->where_lat, 'lng' => $this->where_lng],

            // WHO
            'enterprise'    => optional($this->enterprise)->name,
            'operator'      => $this->who_name,

            // WHAT - OBJECTS
            'inputs'        => $this->inputBatches->map(fn($b) => [
                'batch_code' => $b->code,
                'gtin'       => $b->gtin_cached ?? $b->product?->gtin,
                'quantity'   => $b->pivot->quantity,
                'unit'       => $b->pivot->unit,
                'identifiers'=> $gs1Service->buildIdentifiers(
                    $b->gtin_cached ?? $b->product?->gtin ?? '',
                    $b->code,
                    $this->traceLocation ? $this->traceLocation->gln : null,
                    $b->expiry_date?->format('Y-m-d')
                ),
            ])->toArray(),

            'outputs'       => $this->outputBatches->map(fn($b) => [
                'batch_code' => $b->code,
                'gtin'       => $b->gtin_cached ?? $b->product?->gtin,
                'quantity'   => $b->pivot->quantity,
                'unit'       => $b->pivot->unit,
                'identifiers'=> $gs1Service->buildIdentifiers(
                    $b->gtin_cached ?? $b->product?->gtin ?? '',
                    $b->code,
                    $this->traceLocation ? $this->traceLocation->gln : null,
                    $b->expiry_date?->format('Y-m-d')
                ),
            ])->toArray(),

            // WHY & EVIDENCE
            'why'            => $this->why_reason,
            'attachments'    => $this->attachments ?? [],
            'certifications' => $this->eventCertificates->map(fn($c) => [
                'type'         => optional($c->certificate)->name ?? 'Unknown Certificate',
                'organization' => optional($c->certificate)->organization,
                'no'           => $c->reference_no,
                'result'       => $c->result
            ])->toArray(),
        ];
    }
}
