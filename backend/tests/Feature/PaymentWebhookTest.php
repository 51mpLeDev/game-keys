<?php

namespace Tests\Feature;

use App\Models\InventoryKey;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
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

        $order = app(OrderService::class)->create(
            $product,
            1,
            'test-order-001',
        );

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
            'issued',
            $order->inventoryKeys()->first()->status
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

        $order = app(OrderService::class)->create(
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

    public function test_repeated_payment_of_paid_order_does_not_change_order_or_issue_second_key(): void
    {
        config([
            'providers.default' => 'provider_a',
            'providers.provider_a.mode' => 'success',
            'providers.provider_a.delay_ms' => 0,
        ]);

        $product = Product::create([
            'sku' => 'KEY-REPEAT-PAYMENT',
            'name' => 'Repeat Payment Test',
            'type' => 'key',
            'price' => 1500,
            'currency' => 'RUB',
            'image' => null,
        ]);

        InventoryKey::create([
            'product_id' => $product->id,
            'code' => 'TEST-PAID-KEY',
            'status' => 'available',
        ]);

        $order = app(OrderService::class)->create(
            $product,
            1,
            'repeat-payment-test',
        );

        $payload = [
            'event_id' => 'payment-repeat-001',
            'order_id' => $order->public_id,
            'status' => 'paid',
            'amount' => $order->amount,
            'currency' => $order->currency,
            'created_at' => now()->toISOString(),
        ];

        $this->postJson('/api/webhooks/payment', $payload)
            ->assertOk();

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
            'issued',
            $order->inventoryKeys()->first()->status
        );

        $this->postJson('/api/webhooks/payment', [
            ...$payload,
            'event_id' => 'payment-repeat-002',
        ])->assertOk();

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
            1,
            InventoryKey::where('status', 'issued')->count()
        );
    }

    public function test_payment_retry_after_lost_response_does_not_issue_second_key(): void
    {
        config([
            'providers.default' => 'provider_a',
            'providers.provider_a.mode' => 'success',
            'providers.provider_a.delay_ms' => 0,
        ]);

        $product = Product::create([
            'sku' => 'KEY-NETWORK-RETRY',
            'name' => 'Network Retry Test',
            'type' => 'key',
            'price' => 1700,
            'currency' => 'RUB',
            'image' => null,
        ]);

        InventoryKey::create([
            'product_id' => $product->id,
            'code' => 'NETWORK-RETRY-KEY',
            'status' => 'available',
        ]);

        $order = app(OrderService::class)->create(
            $product,
            1,
            'network-retry-order',
        );

        $payload = [
            'order_id' => $order->public_id,
            'status' => 'paid',
            'amount' => $order->amount,
            'currency' => $order->currency,
            'created_at' => now()->toISOString(),
        ];

        // Первый запрос — платёж успешно обработан.
        $this->postJson('/api/webhooks/payment', [
            ...$payload,
            'event_id' => 'network-retry-001',
        ])->assertOk();

        $order->refresh();

        $this->assertSame(
            'delivered',
            $order->status->value
        );

        $this->assertSame(
            1,
            InventoryKey::where('status', 'issued')->count()
        );

        // Клиент не получил ответ и повторяет запрос.
        $this->postJson('/api/webhooks/payment', [
            ...$payload,
            'event_id' => 'network-retry-002',
        ])->assertOk();

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
            1,
            InventoryKey::where('status', 'issued')->count()
        );
    }
}
