<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionPayment extends Model
{
    protected $guarded = [];

    protected $fillable = [
        'transaction_id',
        'payment_method',
        'amount_paid',
        'change_returned',
        'reference_note',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
