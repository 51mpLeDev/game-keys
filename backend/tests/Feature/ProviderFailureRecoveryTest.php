<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\InventoryKey;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProviderIssuance;
use App\Services\OrderDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderFailureRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_failure_can_be_recovered_with_retry(): void
    {
        config([
            'providers.default' => 'provider_a',
            'providers.provider_a.mode' => 'error',
            'providers.provider_a.delay_ms' => 0,
        ]);

        $product = Product::create([
            'sku' => 'KEY-FAILURE-TEST',
            'name' => 'Failure Test Key',
            'type' => 'key',
            'price' => 1000,
            'currency' => 'RUB',
        ]);

        $key = InventoryKey::create([
            'product_id' => $product->id,
            'code' => 'FAILURE-KEY-001',
            'status' => 'available',
        ]);

        $order = Order::create([
            'public_id' => fake()->uuid(),
            'status' => OrderStatus::PAID,
            'amount' => 1000,
            'currency' => 'RUB',
        ]);

        $item = $order->items()->create([
            'product_id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'price' => $product->price,
            'currency' => $product->currency,
            'quantity' => 1,
        ]);

        /*
         * Первый вызов Provider возвращает 500.
         */
        $result = app(OrderDeliveryService::class)->deliver($order);

        $result->refresh();
        $key->refresh();

        $this->assertSame(
            OrderStatus::DELIVERY_FAILED,
            $result->status,
        );

        /*
         * Ключ уже зарезервирован за этим заказом,
         * но ещё не выдан.
         */
        $this->assertSame(
            'reserved',
            $key->status,
        );

        $this->assertSame(
            $order->id,
            $key->order_id,
        );

        $requestId = "order-{$order->id}-item-{$item->id}";

        $issuance = ProviderIssuance::query()
            ->where('request_id', $requestId)
            ->first();

        $this->assertNotNull($issuance);

        $this->assertSame(
            'failed',
            $issuance->status,
        );

        $this->assertNull($issuance->code);

        $this->assertSame(
            $key->id,
            $issuance->inventory_key_id,
        );

        /*
         * Provider восстановился.
         */
        config([
            'providers.provider_a.mode' => 'success',
        ]);

        /*
         * Retry должен использовать:
         *
         * тот же request_id
         * тот же inventory_key
         */
        $result = app(OrderDeliveryService::class)->deliver(
            $order->refresh()
        );

        $result->refresh();
        $key->refresh();
        $issuance->refresh();

        $this->assertSame(
            OrderStatus::DELIVERED,
            $result->status,
        );

        $this->assertSame(
            'issued',
            $key->status,
        );

        $this->assertSame(
            'FAILURE-KEY-001',
            $key->code,
        );

        $this->assertSame(
            'issued',
            $issuance->status,
        );

        $this->assertSame(
            'FAILURE-KEY-001',
            $issuance->code,
        );

        $this->assertSame(
            $key->id,
            $issuance->inventory_key_id,
        );

        /*
         * Повторный retry ничего не меняет.
         */
        $result = app(OrderDeliveryService::class)->deliver(
            $order->refresh()
        );

        $result->refresh();

        $this->assertSame(
            OrderStatus::DELIVERED,
            $result->status,
        );

        $this->assertSame(
            1,
            InventoryKey::query()
                ->where('order_id', $order->id)
                ->where('status', 'issued')
                ->count(),
        );

        $this->assertSame(
            1,
            ProviderIssuance::query()
                ->where('order_id', $order->id)
                ->count(),
        );
    }
}
