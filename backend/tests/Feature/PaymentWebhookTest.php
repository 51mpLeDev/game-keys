<?php

namespace Tests\Feature;

use App\Models\InventoryKey;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_event_id_does_not_issue_two_keys(): void
    {
        config([
            'providers.default' => 'provider_a',
            'providers.provider_a.mode' => 'success',
            'providers.provider_a.delay_ms' => 0,
        ]);

        $product = Product::create([
            'sku' => 'KEY-CS2-PRIME',
            'name' => 'CS2 Prime Status',
            'type' => 'key',
            'price' => 1290,
            'currency' => 'RUB',
            'image' => null,
        ]);

        InventoryKey::create([
            'product_id' => $product->id,
            'code' => 'TEST-KEY-001',
            'status' => 'available',
        ]);

        $order = Order::create([
            'public_id' => 'test-order-001',
            'status' => 'created',
            'amount' => 1290,
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

        $payload = [
            'event_id' => 'evt-test-001',
            'order_id' => $order->public_id,
            'status' => 'paid',
            'amount' => 1290,
            'currency' => 'RUB',
            'created_at' => now()->toISOString(),
        ];

        $this->postJson('/api/webhooks/payment', $payload)
            ->assertOk();

        $this->postJson('/api/webhooks/payment', $payload)
            ->assertOk();

        $order->refresh();

        $this->assertSame('delivered', $order->status->value);

        $this->assertSame(
            1,
            $order->inventoryKeys()->count()
        );

        $this->assertSame(
            1,
            InventoryKey::where('status', 'issued')->count()
        );
    }

    public function test_webhook_received_before_order_is_processed_after_order_creation(): void
    {
        config([
            'providers.default' => 'provider_a',
            'providers.provider_a.mode' => 'success',
            'providers.provider_a.delay_ms' => 0,
        ]);

        $product = Product::create([
            'sku' => 'KEY-CS2-PRIME',
            'name' => 'CS2 Prime Status',
            'type' => 'key',
            'price' => 1290,
            'currency' => 'RUB',
            'image' => null,
        ]);

        InventoryKey::create([
            'product_id' => $product->id,
            'code' => 'TEST-KEY-BEFORE-ORDER',
            'status' => 'available',
        ]);

        $orderId = 'before-order-test';

        $payload = [
            'event_id' => 'evt-before-order-test',
            'order_id' => $orderId,
            'status' => 'paid',
            'amount' => 1290,
            'currency' => 'RUB',
            'created_at' => now()->toISOString(),
        ];

        // Сначала приходит webhook.
        $this->postJson('/api/webhooks/payment', $payload)
            ->assertOk();

        $this->assertDatabaseHas('payment_events', [
            'event_id' => 'evt-before-order-test',
            'order_public_id' => $orderId,
        ]);

        $order = app(\App\Services\OrderService::class)->create(
            $product,
            1,
            $orderId,
        );

        $order->refresh();

        $this->assertSame(
            'delivered',
            $order->status->value
        );

        $this->assertSame(
            1,
            $order->inventoryKeys()->count()
        );

        $this->assertSame(
            'TEST-KEY-BEFORE-ORDER',
            $order->inventoryKeys()->first()->code
        );
    }
}
