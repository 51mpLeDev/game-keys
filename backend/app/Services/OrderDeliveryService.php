<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\InventoryKey;
use App\Models\Order;
use App\Models\ProviderIssuance;
use Illuminate\Support\Facades\DB;
use Throwable;
use RuntimeException;

class OrderDeliveryService
{
    public function __construct(
        private readonly DeliveryProviderService $provider,
    )
    {
    }

    public function deliver(Order $order): Order
    {
        /*
         * STEP 1.
         *
         * Короткая транзакция:
         * - блокируем заказ;
         * - выбираем один ключ;
         * - резервируем его.
         *
         * Provider здесь НЕ вызываем.
         */
        $delivery = DB::transaction(function () use ($order) {
            $order = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status === OrderStatus::DELIVERED) {
                return null;
            }

            if (!in_array($order->status, [
                OrderStatus::PAID,
                OrderStatus::OUT_OF_STOCK,
                OrderStatus::DELIVERY_FAILED,
            ], true)) {
                return null;
            }

            $item = $order->items()
                ->orderBy('id')
                ->first();

            if (!$item) {
                throw new RuntimeException('Order has no items.');
            }

            $providerName = config(
                'providers.default',
                'provider_a'
            );

            $requestId = sprintf(
                'order-%d-item-%d',
                $order->id,
                $item->id,
            );

            /*
             * Если provider уже знает этот request_id,
             * используем его существующую выдачу.
             */
            $issuance = ProviderIssuance::query()
                ->where('provider', $providerName)
                ->where('request_id', $requestId)
                ->lockForUpdate()
                ->first();

            /*
             * Если Provider уже выдал код, повторно ничего
             * резервировать/вызывать не нужно.
             */
            if (
                $issuance &&
                $issuance->status === 'issued' &&
                $issuance->code !== null
            ) {
                $key = $issuance->inventory_key_id
                    ? InventoryKey::query()
                        ->whereKey($issuance->inventory_key_id)
                        ->lockForUpdate()
                        ->first()
                    : null;

                if ($key && $key->status !== 'issued') {
                    $key->update([
                        'status' => 'issued',
                        'order_id' => $order->id,
                        'issued_at' => now(),
                    ]);
                }

                $order->update([
                    'status' => OrderStatus::DELIVERED,
                    'delivered_at' => $order->delivered_at ?? now(),
                ]);

                return null;
            }

            /*
             * Сначала пытаемся найти ключ, который уже
             * зарезервирован именно этим заказом.
             */
            $key = InventoryKey::query()
                ->where('product_id', $item->product_id)
                ->where('order_id', $order->id)
                ->whereIn('status', ['reserved', 'issued'])
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            /*
             * Если ключа ещё нет — берём первый свободный.
             */
            if (!$key) {
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

                    return null;
                }

                $key->update([
                    'status' => 'reserved',
                    'order_id' => $order->id,
                ]);
            }

            $order->update([
                'status' => OrderStatus::DELIVERING,
            ]);

            return [
                'order_id' => $order->id,
                'item_id' => $item->id,
                'sku' => $item->sku,
                'request_id' => $requestId,
                'inventory_key_id' => $key->id,
            ];
        });

        /*
         * Уже обработан / нет stock / неподходящий статус.
         */
        if ($delivery === null) {
            return $order
                ->refresh()
                ->load('items');
        }

        /*
         * STEP 2.
         *
         * Provider вызывается ПОСЛЕ commit.
         */
        try {
            $code = $this->provider->issue(
                requestId: $delivery['request_id'],
                sku: $delivery['sku'],
                orderId: $delivery['order_id'],
                inventoryKeyId: $delivery['inventory_key_id'],
            );
        } catch (Throwable $exception) {
            /*
             * Provider мог успеть выдать результат,
             * но клиент получил timeout.
             *
             * Поэтому сначала проверяем ProviderIssuance.
             */
            $issuance = ProviderIssuance::query()
                ->where('request_id', $delivery['request_id'])
                ->where('provider', config('providers.default', 'provider_a'))
                ->first();

            /*
             * Если результат уже сохранён как issued,
             * НЕ переводим заказ в delivery_failed.
             *
             * Следующий retry сможет завершить доставку.
             */
            if ($issuance?->status === 'issued') {
                DB::transaction(function () use ($delivery, $issuance) {
                    $lockedOrder = Order::query()
                        ->whereKey($delivery['order_id'])
                        ->lockForUpdate()
                        ->firstOrFail();

                    $key = InventoryKey::query()
                        ->whereKey($issuance->inventory_key_id)
                        ->lockForUpdate()
                        ->first();

                    if ($key && $key->status !== 'issued') {
                        $key->update([
                            'status' => 'issued',
                            'order_id' => $lockedOrder->id,
                            'issued_at' => now(),
                        ]);
                    }

                    if ($lockedOrder->status !== OrderStatus::DELIVERED) {
                        $lockedOrder->update([
                            'status' => OrderStatus::DELIVERED,
                            'delivered_at' => $lockedOrder->delivered_at ?? now(),
                        ]);
                    }
                });

                return $order
                    ->refresh()
                    ->load('items');
            }

            DB::transaction(function () use ($delivery) {
                $lockedOrder = Order::query()
                    ->whereKey($delivery['order_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedOrder->status !== OrderStatus::DELIVERED) {
                    $lockedOrder->update([
                        'status' => OrderStatus::DELIVERY_FAILED,
                    ]);
                }
            });

            return $order
                ->refresh()
                ->load('items');
        }

        /*
         * STEP 3.
         *
         * Provider успешно выдал код.
         * Теперь фиксируем всё одной короткой транзакцией.
         */
        DB::transaction(function () use ($delivery, $code) {
            $order = Order::query()
                ->whereKey($delivery['order_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $key = InventoryKey::query()
                ->whereKey($delivery['inventory_key_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $provider = config(
                'providers.default',
                'provider_a'
            );

            $issuance = ProviderIssuance::query()
                ->where('provider', $provider)
                ->where('request_id', $delivery['request_id'])
                ->lockForUpdate()
                ->first();

            /*
             * Provider должен был создать issuance.
             * Но если его нет — создаём его здесь как защиту.
             */
            if (!$issuance) {
                $issuance = ProviderIssuance::create([
                    'provider' => $provider,
                    'request_id' => $delivery['request_id'],
                    'order_id' => $order->id,
                    'inventory_key_id' => $key->id,
                    'sku' => $delivery['sku'],
                    'status' => 'issued',
                    'code' => $code,
                    'issued_at' => now(),
                ]);
            } else {
                $issuance->update([
                    'inventory_key_id' => $key->id,
                    'status' => 'issued',
                    'code' => $code,
                    'error' => null,
                    'issued_at' => $issuance->issued_at ?? now(),
                ]);
            }

            /*
             * Один inventory key → один order.
             */
            if ($key->status !== 'issued') {
                $key->update([
                    'status' => 'issued',
                    'order_id' => $order->id,
                    'issued_at' => now(),
                ]);
            }

            if ($order->status !== OrderStatus::DELIVERED) {
                $order->update([
                    'status' => OrderStatus::DELIVERED,
                    'delivered_at' => now(),
                ]);
            }
        });

        return $order
            ->refresh()
            ->load('items');
    }
}
