<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentEvent extends Model
{
    protected $fillable = [
        'event_id',
        'order_public_id',
        'status',
        'amount',
        'currency',
        'provider_created_at',
        'processed_at',
        'payload',
    ];

    protected $casts = [
        'amount' => 'integer',
        'provider_created_at' => 'datetime',
        'processed_at' => 'datetime',
        'payload' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(
            Order::class,
            'order_public_id',
            'public_id'
        );
    }
}
