<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductModifierGroup extends Model
{
    protected $guarded = [];

    public function modifiers(): HasMany
    {
        return $this->hasMany(ProductModifier::class, 'modifier_group_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_modifier_group_product');
    }
}
