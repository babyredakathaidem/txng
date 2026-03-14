<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CteTemplate extends Model
{
    protected $fillable = [
        'category_id',
        'step_order',
        'code',
        'name_vi',
        'is_required',
        'kde_schema',
        'tcvn_note',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'kde_schema'  => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * Nhóm KDE fields theo chiều W (WHO/WHAT/WHERE/WHEN/WHY)
     */
    public function kdeGroupedByW(): array
    {
        $groups = ['WHO' => [], 'WHAT' => [], 'WHERE' => [], 'WHEN' => [], 'WHY' => []];

        foreach ($this->kde_schema ?? [] as $field) {
            $w = strtoupper($field['w'] ?? 'WHAT');
            if (isset($groups[$w])) {
                $groups[$w][] = $field;
            }
        }

        return array_filter($groups);
    }
}