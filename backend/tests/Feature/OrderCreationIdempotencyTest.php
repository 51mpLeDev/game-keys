<?php

namespace Tests\Feature;

use App\Models\InventoryKey;
use App\Models\Product;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderCreationIdempotencyTest extends TestCase
{
    public function test_same_order_id_does_not_create_second_order(): void
    {
        $product = Product::create([
            'sku' => 'KEY-IDEMPOTENCY-TEST',
            'name' => 'Idempotency Test Key',
            'type' => 'key',
            'price' => 1290,
            'currency' => 'RUB',
            'image' => null,
        ]);

        InventoryKey::create([
            'product_id' => $product->id,
            'code' => 'IDEMPOTENCY-TEST-KEY',
            'status' => 'available',
        ]);

        $orderId = (string) Str::uuid();

        $first = $this->postJson('/api/orders', [
            'order_id' => $orderId,
            'sku' => $product->sku,
            'quantity' => 1,
        ]);

        $first->assertStatus(201);

        $firstOrderId = $first->json('data.id');

        $second = $this->postJson('/api/orders', [
            'order_id' => $orderId,
            'sku' => $product->sku,
            'quantity' => 1,
        ]);

        $second->assertStatus(200);

        $this->assertSame(
            $firstOrderId,
            $second->json('data.id')
        );

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);

        $this->assertDatabaseHas('orders', [
            'public_id' => $orderId,
        ]);
    }
}
