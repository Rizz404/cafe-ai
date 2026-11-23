<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'reference', 'cafe_id', 'menu_item_id', 'session_hash', 'request_key',
    'item_name', 'price', 'currency', 'status', 'starts_at', 'finishes_at',
])]
class CoffeeOrder extends Model
{
    protected $hidden = ['session_hash', 'request_key'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'starts_at' => 'immutable_datetime',
            'finishes_at' => 'immutable_datetime',
        ];
    }
}
