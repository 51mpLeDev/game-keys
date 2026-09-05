<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderIssuance extends Model
{
    protected $fillable = [
        'provider',
        'request_id',
        'order_id',
        'inventory_key_id',
        'sku',
        'status',
        'code',
        'error',
        'issued_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function inventoryKey(): BelongsTo
    {
        return $this->belongsTo(InventoryKey::class);
    }
}
