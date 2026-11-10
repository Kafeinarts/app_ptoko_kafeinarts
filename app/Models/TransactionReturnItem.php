<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionReturnItem extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function return(): BelongsTo
    {
        return $this->belongsTo(TransactionReturn::class, 'transaction_return_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(TransactionItem::class, 'transaction_item_id');
    }
}
