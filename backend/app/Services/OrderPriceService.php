<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderPriceService
{
    public function refresh(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== OrderStatus::CREATED) {
                throw new RuntimeException(
                    'Only unpaid orders can refresh price.'
                );
            }

            if (
                $order->reservation_expires_at === null ||
                $order->reservation_expires_at->isPast()
            ) {
                throw new RuntimeException(
                    'Order reservation has expired.'
                );
            }

            $item = OrderItem::query()
                ->where('order_id', $order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $product = Product::query()
                ->whereKey($item->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $item->update([
                'price' => $product->price,
                'currency' => $product->currency,
            ]);

            $baseAmount = $product->price * $item->quantity;

            $discount = (int) (
                $order->promoCodeUsage?->discount ?? 0
            );

            $order->update([
                'amount' => max(0, $baseAmount - $discount),
                'currency' => $product->currency,
            ]);

            return $order
                ->refresh()
                ->load(['items', 'inventoryKeys']);
        });
    }
}
