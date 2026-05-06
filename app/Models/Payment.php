<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'idempotency_key',
    ];

    protected $casts = [
        'status' => \App\Enums\PaymentStatus::class,
    ];
}