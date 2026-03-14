<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    protected $fillable = ['code', 'name_vi', 'icon', 'tcvn_ref', 'sort_order'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function cteTemplates(): HasMany
    {
        return $this->hasMany(CteTemplate::class, 'category_id')
            ->orderBy('step_order');
    }

    public function requiredCtes(): HasMany
    {
        return $this->hasMany(CteTemplate::class, 'category_id')
            ->where('is_required', true)
            ->orderBy('step_order');
    }
}