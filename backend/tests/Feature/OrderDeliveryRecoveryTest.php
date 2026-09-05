<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\InventoryKey;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDeliveryRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_out_of_stock_order_can_be_retried_after_stock_is_replenished(): void
    {
        config([
            'providers.default' => 'provider_a',
            'providers.provider_a.mode' => 'success',
            'providers.provider_a.delay_ms' => 0,
        ]);

        $product = Product::create([
            'sku' => 'TEST-RECOVERY',
            'name' => 'Recovery Test Product',
            'type' => 'key',
            'price' => 1000,
            'currency' => 'RUB',
        ]);

        $order = Order::create([
            'public_id' => 'recovery-test-order',
            'status' => OrderStatus::PAID,
            'amount' => 1000,
            'currency' => 'RUB',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'price' => $product->price,
            'currency' => $product->currency,
            'quantity' => 1,
        ]);

        $service = app(OrderDeliveryService::class);

        // First attempt: no stock.
        $order = $service->deliver($order);

        $this->assertSame(
            OrderStatus::OUT_OF_STOCK,
            $order->status
        );

        $this->assertSame(
            0,
            $order->inventoryKeys()->count()
        );

        // Replenish stock.
        InventoryKey::create([
            'product_id' => $product->id,
            'code' => 'TEST-RECOVERY-KEY-001',
            'status' => 'available',
        ]);

        // Retry.
        $order = $service->deliver($order->refresh());

        $this->assertSame(
            OrderStatus::DELIVERED,
            $order->status
        );

        $this->assertSame(
            1,
            $order->inventoryKeys()->count()
        );

        $this->assertSame(
            'TEST-RECOVERY-KEY-001',
            $order->inventoryKeys()->first()->code
        );

        // Retry again must not issue another key.
        $order = $service->deliver($order->refresh());

        $this->assertSame(
            OrderStatus::DELIVERED,
            $order->status
        );

        $this->assertSame(
            1,
            $order->inventoryKeys()->count()
        );

        $this->assertSame(
            1,
            InventoryKey::where('status', 'issued')->count()
        );
    }
}
