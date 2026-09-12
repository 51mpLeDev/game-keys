<?php

namespace App\Services;

use App\Events\ProductStockUpdated;
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
        Order   $order,
    ): InventoryKey
    {
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

        $order->update([
            'reservation_expires_at' => $key->reserved_until,
        ]);

        $productId = $product->id;

        DB::afterCommit(function () use ($productId) {
            $stock = InventoryKey::query()
                ->where('product_id', $productId)
                ->where('status', 'available')
                ->count();

            ProductStockUpdated::dispatch($productId, $stock);
        });

        return $key->fresh();
    }

    public function releaseExpired(): int
    {
        return DB::transaction(function () {
            $expired = InventoryKey::query()
                ->where('status', 'reserved')
                ->whereNotNull('reserved_until')
                ->where('reserved_until', '<=', now())
                ->lockForUpdate()
                ->get(['id', 'product_id']);

            if ($expired->isEmpty()) {
                return 0;
            }

            InventoryKey::query()
                ->whereIn('id', $expired->pluck('id'))
                ->update([
                    'status' => 'available',
                    'order_id' => null,
                    'reserved_until' => null,
                ]);

            $productIds = $expired
                ->pluck('product_id')
                ->unique()
                ->values();

            DB::afterCommit(function () use ($productIds) {
                foreach ($productIds as $productId) {
                    ProductStockUpdated::dispatch(
                        $productId,
                        $this->availableStock($productId),
                    );
                }
            });

            return $expired->count();
        });
    }

    private function availableStock(int $productId): int
    {
        return InventoryKey::query()
            ->where('product_id', $productId)
            ->where('status', 'available')
            ->count();
    }
}
