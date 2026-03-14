<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductProcess extends Model
{
    protected $fillable = [
        'product_id',
        'enterprise_id',
        'step_order',
        'name_vi',
        'cte_code',
        'description',
        'is_required',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function traceEvents(): HasMany
    {
        return $this->hasMany(TraceEvent::class, 'process_step_id');
    }
}
