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

class ProviderTimeoutDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_timeout_once_returns_same_key_on_retry(): void
    {
        config([
            'providers.default' => 'provider_a',
            'providers.provider_a.mode' => 'timeout_once',
            'providers.provider_a.delay_ms' => 0,
        ]);

        $product = Product::create([
            'sku' => 'KEY-TIMEOUT-TEST',
            'name' => 'Timeout Test Key',
            'type' => 'key',
            'price' => 1000,
            'currency' => 'RUB',
        ]);

        $key = InventoryKey::create([
            'product_id' => $product->id,
            'code' => 'TIMEOUT-KEY-001',
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
         * Первый вызов:
         *
         * Provider фактически создаёт issuance + code,
         * но клиент получает timeout.
         */
        $result = app(OrderDeliveryService::class)->deliver($order);

        $result->refresh();
        $key->refresh();

        /*
         * Provider уже знает результат,
         * несмотря на потерянный response.
         */
        $issuance = ProviderIssuance::query()
            ->where('request_id', "order-{$order->id}-item-{$item->id}")
            ->first();

        $this->assertNotNull($issuance);

        $this->assertSame(
            'issued',
            $issuance->status,
        );

        $this->assertSame(
            'TIMEOUT-KEY-001',
            $issuance->code,
        );

        $this->assertSame(
            $key->id,
            $issuance->inventory_key_id,
        );

        /*
         * Ключ всё ещё принадлежит этому заказу.
         */
        $this->assertSame(
            $order->id,
            $key->order_id,
        );

        /*
         * Важно: timeout не должен потерять связь
         * request_id → конкретный key.
         */
        $requestId = $issuance->request_id;

        /*
         * Теперь Provider работает нормально.
         */
        config([
            'providers.provider_a.mode' => 'success',
        ]);

        /*
         * Retry.
         *
         * Тот же request_id должен вернуть
         * тот же code.
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
            'TIMEOUT-KEY-001',
            $key->code,
        );

        $this->assertSame(
            $requestId,
            $issuance->request_id,
        );

        $this->assertSame(
            'TIMEOUT-KEY-001',
            $issuance->code,
        );

        /*
         * Самое главное:
         * в inventory только один выданный ключ.
         */
        $this->assertSame(
            1,
            InventoryKey::query()
                ->where('order_id', $order->id)
                ->where('status', 'issued')
                ->count(),
        );

        /*
         * И только одна provider issuance.
         */
        $this->assertSame(
            1,
            ProviderIssuance::query()
                ->where('order_id', $order->id)
                ->count(),
        );
    }
}
