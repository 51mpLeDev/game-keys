<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\InventoryKey;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderDeliveryService
{
    public function deliver(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Повторная обработка уже доставленного заказа
             * ничего не делает.
             */
            if ($order->status === OrderStatus::DELIVERED) {
                return $order;
            }

            /*
             * Только paid должен переходить в delivering.
             */
            if ($order->status !== OrderStatus::PAID) {
                return $order;
            }

            $order->update([
                'status' => OrderStatus::DELIVERING,
            ]);

            $item = $order->items()
                ->orderBy('id')
                ->first();

            if (!$item) {
                throw new RuntimeException(
                    'Order has no items.'
                );
            }

            /*
             * Критически важный участок.
             *
             * lockForUpdate() блокирует выбранный inventory row
             * до конца транзакции.
             */
            $key = InventoryKey::query()
                ->where('product_id', $item->product_id)
                ->where('status', 'available')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (!$key) {
                $order->update([
                    'status' => OrderStatus::OUT_OF_STOCK,
                ]);

                return $order;
            }

            $key->update([
                'status' => 'issued',
                'order_id' => $order->id,
                'issued_at' => now(),
            ]);

            $order->update([
                'status' => OrderStatus::DELIVERED,
                'delivered_at' => now(),
            ]);

            return $order->load('items');
        });
    }
}
