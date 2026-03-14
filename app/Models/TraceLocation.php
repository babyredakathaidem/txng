<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Services\GS1Service;

/**
 * TraceLocation — Địa điểm truy vết theo TCVN 13274:2020
 *
 * AI types được hỗ trợ:
 *   410 = Địa điểm nhận hàng    (ship-to)
 *   411 = Địa điểm gửi hàng    (bill-to)
 *   412 = Địa điểm mua hàng    (purchased-from / nhà cung cấp)
 *   414 = Địa điểm vật lý      (kho, điểm bảo quản)
 *   416 = Địa điểm sản xuất    (vùng trồng, nhà máy)
 *   417 = Địa điểm giao dịch   (party location)
 */
class TraceLocation extends Model
{
    protected $fillable = [
        'enterprise_id',
        'ai_type',
        'gln',
        'code',
        'name',
        'province',
        'district',
        'address_detail',
        'lat',
        'lng',
        'area_ha',
        'farm_code',
        'product_types',
        'status',
        'note',
    ];

    protected $casts = [
        'lat'     => 'float',
        'lng'     => 'float',
        'area_ha' => 'float',
    ];

    // ─────────────────────────────────────────────────────────────────
    // Hằng số AI theo TCVN 13274:2020
    // ─────────────────────────────────────────────────────────────────

    /** Bảng nhãn tiếng Việt cho từng AI type */
    public const AI_LABELS = [
        '410' => 'Địa điểm nhận hàng',
        '411' => 'Địa điểm gửi hàng',
        '412' => 'Địa điểm mua hàng (nhà cung cấp)',
        '414' => 'Địa điểm vật lý (kho)',
        '416' => 'Địa điểm sản xuất / Vùng trồng',
        '417' => 'Địa điểm giao dịch',
    ];

    // ─────────────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────────────

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function traceEvents(): BelongsToMany
    {
        return $this->belongsToMany(
            TraceEvent::class,
            'trace_event_locations',
            'trace_location_id',
            'trace_event_id'
        )->withPivot('ai_type')->withTimestamps();
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    /** Nhãn tiếng Việt của AI type hiện tại */
    public function getAiLabelAttribute(): string
    {
        return self::AI_LABELS[$this->ai_type] ?? "AI({$this->ai_type})";
    }

    /** Địa chỉ đầy đủ ghép lại */
    public function getFullAddressAttribute(): string
    {
        return implode(', ', array_filter([
            $this->address_detail,
            $this->district,
            $this->province,
        ], fn($v) => filled($v)));
    }

    /** Có toạ độ GPS không */
    public function hasGps(): bool
    {
        return ! is_null($this->lat) && ! is_null($this->lng);
    }

    /** Đây có phải là vùng trồng/sản xuất không */
    public function isProductionSite(): bool
    {
        return $this->ai_type === '416';
    }

    /**
     * Tạo chuỗi AI GS1 chuẩn cho địa điểm này.
     *
     * Ví dụ: "(416)8930000000018"
     * Dùng để nhúng vào IPFS payload và GS1-128.
     */
    public function toGs1AiString(): ?string
    {
        if (! $this->gln) return null;
        return "({$this->ai_type}){$this->gln}";
    }

    /**
     * Payload chuẩn để nhúng vào IPFS TraceEvent payload.
     *
     * Dùng trong: TraceEvent::toIpfsPayload() → 'locations' array
     */
    public function toIpfsFragment(): array
    {
        return [
            'ai_type'      => $this->ai_type,
            'ai_label'     => $this->ai_label,
            'gln'          => $this->gln,
            'gs1_ai_str'   => $this->toGs1AiString(),
            'code'         => $this->code,
            'farm_code'    => $this->farm_code,
            'name'         => $this->name,
            'address'      => $this->full_address,
            'province'     => $this->province,
            'gps'          => $this->hasGps()
                ? ['lat' => $this->lat, 'lng' => $this->lng]
                : null,
            'area_ha'      => $this->area_ha,
            'product_types'=> $this->product_types,
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOfType($query, string $aiType)
    {
        return $query->where('ai_type', $aiType);
    }

    /** Chỉ lấy vùng trồng / địa điểm sản xuất (AI 416) */
    public function scopeProductionSites($query)
    {
        return $query->where('ai_type', '416');
    }
}
