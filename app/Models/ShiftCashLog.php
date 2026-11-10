<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftCashLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $fillable = [
        'shift_id',
        'type',
        'amount',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
