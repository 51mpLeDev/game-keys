<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'public_id',
        'status',
        'amount',
        'currency',
        'reservation_expires_at',
        'paid_at',
        'delivered_at',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'amount' => 'integer',
        'reservation_expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function paymentEvents(): HasMany
    {
        return $this->hasMany(
            PaymentEvent::class,
            'order_public_id',
            'public_id'
        );
    }

    public function inventoryKeys(): HasMany
    {
        return $this->hasMany(InventoryKey::class);
    }

    public function providerIssuances(): HasMany
    {
        return $this->hasMany(ProviderIssuance::class);
    }

    public function promoCodeUsage(): HasOne
    {
        return $this->hasOne(PromoCodeUsage::class);
    }
}
