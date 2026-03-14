<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'enterprise_id',
        'category_id',
        'name',
        'gtin',
        'description',
        'image_path',
        'unit',
        'status',
        'technical_standards', // TCVN, QCVN
        'weight',              // Trọng lượng
        'volume',              // Thể tích
        'dimensions',          // Kích thước (DxRxC)
        'gs1_metadata',        // JSON metadata theo TCVN 13274:2020
    ];

    protected $casts = [
        'status' => 'string',
        'gs1_metadata' => 'array',
    ];

    public function validateGtin(): bool
    {
        return \App\Services\GS1Validator::isValid($this->gtin);
    }

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function processes(): HasMany
    {
        return $this->hasMany(ProductProcess::class)->orderBy('step_order');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function imageUrl(): ?string
    {
        return $this->image_path
            ? asset('storage/' . $this->image_path)
            : null;
    }
}