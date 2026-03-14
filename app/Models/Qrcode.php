<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Qrcode extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'enterprise_id',
        'batch_id',
        'type',
        'token',
        'gs1_digital_link',  // ← mới: URL GS1 Digital Link đầy đủ
        'gtin_used',         // ← mới: GTIN đã dùng để tạo link
        'place_name',
        'allowed_lat',
        'allowed_lng',
        'allowed_radius_m',
        'first_scanned_at',
        'expires_at',
    ];

    protected $casts = [
        'first_scanned_at' => 'datetime',
        'expires_at'       => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    // ── Helpers ───────────────────────────────────────────

    /**
     * URL thực tế in vào QR code.
     *
     * Ưu tiên GS1 Digital Link (chuẩn quốc tế).
     * Fallback về token URL (cũ) nếu chưa có gs1_digital_link.
     */
    public function getScanUrlAttribute(): string
    {
        if ($this->gs1_digital_link) {
            return $this->gs1_digital_link;
        }

        // Legacy fallback
        $base = rtrim(config('app.url'), '/');
        return $this->type === 'public'
            ? "{$base}/t/{$this->token}"
            : "{$base}/v/{$this->token}";
    }

    /**
     * Kiểm tra QR đã được cấu hình GS1 Digital Link chưa.
     */
    public function hasGs1Link(): bool
    {
        return ! empty($this->gs1_digital_link);
    }
}