<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionVoid extends Model
{
    protected $guarded = [];

    protected $fillable = [
        'transaction_id',
        'user_id',
        'approved_by',
        'reason',
        'voided_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
