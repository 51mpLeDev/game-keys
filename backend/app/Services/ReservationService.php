<?php

namespace App\Services;

use App\Exceptions\OutOfStockException;
use App\Models\InventoryKey;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    private const RESERVATION_MINUTES = 5;

    public function reserve(
        Product $product,
        Order $order,
    ): InventoryKey {
        $existing = InventoryKey::query()
            ->where('product_id', $product->id)
            ->where('order_id', $order->id)
            ->where('status', 'reserved')
            ->lockForUpdate()
            ->first();

        if ($existing) {
            return $existing;
        }

        InventoryKey::query()
            ->where('product_id', $product->id)
            ->where('status', 'reserved')
            ->whereNotNull('reserved_until')
            ->where('reserved_until', '<=', now())
            ->update([
                'status' => 'available',
                'order_id' => null,
                'reserved_until' => null,
            ]);

        $key = InventoryKey::query()
            ->where('product_id', $product->id)
            ->where('status', 'available')
            ->orderBy('id')
            ->lockForUpdate()
            ->first();

        if (!$key) {
            throw new OutOfStockException();
        }

        $key->update([
            'status' => 'reserved',
            'order_id' => $order->id,
            'reserved_until' => now()->addMinutes(self::RESERVATION_MINUTES),
        ]);

        return $key->fresh();
    }

    public function releaseExpired(): int
    {
        return DB::transaction(function () {
            return InventoryKey::query()
                ->where('status', 'reserved')
                ->whereNotNull('reserved_until')
                ->where('reserved_until', '<=', now())
                ->update([
                    'status' => 'available',
                    'order_id' => null,
                    'reserved_until' => null,
                ]);
        });
    }
}
