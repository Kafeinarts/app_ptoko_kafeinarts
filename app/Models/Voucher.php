<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'is_active' => 'boolean',
            'value' => 'decimal:2',
            'min_purchase' => 'decimal:2',
            'max_discount' => 'decimal:2',
        ];
    }

    public function isUsable(float $subtotal): bool
    {
        return $this->is_active
            && now()->between($this->valid_from, $this->valid_until)
            && $subtotal >= (float) $this->min_purchase;
    }

    public function discountFor(float $subtotal): float
    {
        $discount = $this->type === 'PERCENTAGE'
            ? $subtotal * ((float) $this->value / 100)
            : (float) $this->value;

        if ((float) $this->max_discount > 0) {
            $discount = min($discount, (float) $this->max_discount);
        }

        return round(min($discount, $subtotal), 2);
    }
}
