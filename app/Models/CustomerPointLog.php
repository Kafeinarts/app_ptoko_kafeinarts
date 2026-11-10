<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPointLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $fillable = [
        'customer_id',
        'type',
        'points',
        'reference_number',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
