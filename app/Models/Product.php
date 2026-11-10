<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function modifierGroups(): BelongsToMany
    {
        return $this->belongsToMany(ProductModifierGroup::class, 'product_modifier_group_product');
    }

    public function primaryVariant(): ?ProductVariant
    {
        return $this->variants()->orderBy('id')->first();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->image_path && file_exists(storage_path('app/public/'.$this->image_path))) {
            return asset('storage/'.$this->image_path);
        }

        return asset('assets/img/product-placeholder.svg');
    }

    public static function generateSku(string $name): string
    {
        $base = strtoupper(Str::slug($name, ''));
        $base = substr($base ?: 'PRD', 0, 6);
        $last = static::query()->where('sku', 'like', $base.'-%')->count() + 1;

        return $base.'-'.str_pad((string) $last, 4, '0', STR_PAD_LEFT);
    }
}
