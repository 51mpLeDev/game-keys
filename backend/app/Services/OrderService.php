<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class OrderService
{
    public function create(Product $product, int $quantity = 1): Order
    {
        if ($quantity < 1) {
            throw new RuntimeException(
                'Quantity must be greater than zero.'
            );
        }

        $order = DB::transaction(function () use ($product, $quantity) {
            $order = Order::create([
                'public_id' => (string)Str::uuid(),
                'status' => OrderStatus::CREATED,
                'amount' => $product->price * $quantity,
                'currency' => $product->currency,
            ]);

            $order->items()->create([
                'product_id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'price' => $product->price,
                'currency' => $product->currency,
                'quantity' => $quantity,
            ]);

            return $order->load('items');
        });

        /*
         * Webhook мог прийти до создания заказа.
         *
         * Теперь, когда заказ появился, проверяем,
         * нет ли уже сохранённого payment event.
         */
        app(PaymentWebhookService::class)->processOrder($order);

        return $order->refresh()->load('items');
    }
}
