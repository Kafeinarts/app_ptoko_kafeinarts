<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $guarded = [];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function priceFor(?int $outletId): ?ProductPrice
    {
        return $this->prices()->where('outlet_id', $outletId)->first()
            ?? $this->prices()->first();
    }

    public function stockFor(?int $outletId): int
    {
        return (int) ($this->stocks()->where('outlet_id', $outletId)->value('quantity') ?? 0);
    }

    public function getDisplayNameAttribute(): string
    {
        $product = $this->product?->name ?? '-';

        return $this->variant_name === 'Default' ? $product : $product.' - '.$this->variant_name;
    }
}
