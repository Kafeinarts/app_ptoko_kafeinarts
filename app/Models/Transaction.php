<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TransactionPayment::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(TransactionPayment::class)->latestOfMany();
    }

    public function void(): HasOne
    {
        return $this->hasOne(TransactionVoid::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(TransactionReturn::class);
    }

    public function scopePaid($query)
    {
        return $query->whereIn('status', ['PAID', 'PARTIAL_REFUND']);
    }

    public function getProfitAttribute(): float
    {
        return (float) $this->items->sum(fn ($item) => ((float) $item->total) - ((float) $item->cost_price * $item->qty));
    }
}
