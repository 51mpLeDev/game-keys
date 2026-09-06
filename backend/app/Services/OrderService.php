<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class OrderService
{
    public function create(
        Product $product,
        int $quantity = 1,
        ?string $publicId = null,
        ?string $promoCode = null,
    ): Order {
        if ($quantity !== 1) {
            throw new RuntimeException('Only one item per order is supported.');
        }

        if ($publicId) {
            $existingOrder = Order::query()
                ->where('public_id', $publicId)
                ->first();

            if ($existingOrder) {
                return $existingOrder->load('items');
            }
        }

        $publicId ??= (string) Str::uuid();

        try {
            $order = DB::transaction(function () use (
                $product,
                $quantity,
                $publicId,
                $promoCode
            ) {
                $originalAmount = $product->price * $quantity;

                $order = Order::create([
                    'public_id' => $publicId,
                    'status' => OrderStatus::CREATED,
                    'amount' => $originalAmount,
                    'currency' => $product->currency,
                ]);


                if ($promoCode !== null && trim($promoCode) !== '') {
                    $promo = app(PromoCodeService::class)->apply(
                        $promoCode,
                        $order
                    );

                    $order->amount = $promo['amount'];
                    $order->save();
                }

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
        } catch (QueryException $exception) {
            if ($publicId && $this->isDuplicatePublicId($exception)) {
                return Order::query()
                    ->where('public_id', $publicId)
                    ->firstOrFail()
                    ->load('items');
            }

            throw $exception;
        }

        app(PaymentWebhookService::class)->processOrder($order);

        return $order->refresh()->load('items');
    }

    private function isDuplicatePublicId(QueryException $exception): bool
    {
        return $exception->getCode() === '23000';
    }
}
