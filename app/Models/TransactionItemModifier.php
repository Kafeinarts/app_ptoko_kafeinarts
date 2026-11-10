<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionItemModifier extends Model
{
    public $timestamps = false;

    public function modifier(): BelongsTo
    {
        return $this->belongsTo(ProductModifier::class, 'product_modifier_id');
    }

    protected $guarded = [];

    protected $fillable = [
        'transaction_item_id',
        'product_modifier_id',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [];
    }
}
