<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\InventoryKey;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_order_is_delivered_through_provider(): void
    {
        config([
            'providers.default' => 'provider_a',
            'providers.provider_a.mode' => 'success',
            'providers.provider_a.delay_ms' => 0,
        ]);

        $product = Product::create([
            'sku' => 'KEY-TEST-PROVIDER',
            'name' => 'Provider Test Key',
            'type' => 'key',
            'price' => 1000,
            'currency' => 'RUB',
        ]);

        $key = InventoryKey::create([
            'product_id' => $product->id,
            'code' => 'TEST-PROVIDER-KEY-001',
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

        $result = app(OrderDeliveryService::class)->deliver($order);

        $result->refresh();

        $key->refresh();

        $this->assertSame(
            OrderStatus::DELIVERED,
            $result->status,
        );

        $this->assertSame(
            'issued',
            $key->status,
        );

        $this->assertSame(
            $order->id,
            $key->order_id,
        );

        $this->assertDatabaseHas('provider_issuances', [
            'provider' => 'provider_a',
            'request_id' => "order-{$order->id}-item-{$item->id}",
            'order_id' => $order->id,
            'inventory_key_id' => $key->id,
            'status' => 'issued',
            'code' => 'TEST-PROVIDER-KEY-001',
        ]);
    }
}
