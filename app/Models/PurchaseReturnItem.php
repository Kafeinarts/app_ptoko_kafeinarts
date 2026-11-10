<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $fillable = [
        'purchase_return_id',
        'purchase_order_item_id',
        'qty',
        'unit_cost',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
