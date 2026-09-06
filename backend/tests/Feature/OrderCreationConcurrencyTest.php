<?php

namespace Tests\Feature;

use App\Models\InventoryKey;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderCreationConcurrencyTest extends TestCase
{
    public function test_fifty_concurrent_order_requests_create_one_order(): void
    {
        DB::table('payment_events')->delete();
        DB::table('provider_issuances')->delete();
        DB::table('order_items')->delete();
        DB::table('orders')->delete();
        DB::table('inventory_keys')->delete();
        DB::table('products')->delete();

        $product = Product::create([
            'sku' => 'KEY-CS2-RACE',
            'name' => 'CS2 Race Test',
            'type' => 'key',
            'price' => 1290,
            'currency' => 'RUB',
            'image' => null,
        ]);

        InventoryKey::create([
            'product_id' => $product->id,
            'code' => 'ORDER-RACE-KEY',
            'status' => 'available',
        ]);

        $orderId = (string) Str::uuid();

        $processes = [];

        for ($i = 1; $i <= 50; $i++) {
            $payload = json_encode([
                'order_id' => $orderId,
                'sku' => $product->sku,
                'quantity' => 1,
            ], JSON_THROW_ON_ERROR);

            $command = sprintf(
                'curl -s -X POST http://nginx/api/orders '
                . '-H "Content-Type: application/json" '
                . '-H "Accept: application/json" '
                . '-d %s',
                escapeshellarg($payload)
            );

            $processes[] = $this->startProcess($command);
        }

        foreach ($processes as $process) {
            $this->finishProcess($process);
        }

        $this->assertSame(1, Order::count());
        $this->assertSame(1, \App\Models\OrderItem::count());

        $order = Order::query()
            ->where('public_id', $orderId)
            ->first();

        $this->assertNotNull($order);
        $this->assertSame(1290, $order->amount);
    }

    private function startProcess(string $command): array
    {
        $pipes = [];

        $process = proc_open(
            ['sh', '-c', $command],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            base_path(),
        );

        if (!is_resource($process)) {
            $this->fail('Unable to start concurrent order process.');
        }

        return [
            'process' => $process,
            'pipes' => $pipes,
        ];
    }

    private function finishProcess(array $process): void
    {
        fclose($process['pipes'][0]);

        $stdout = stream_get_contents($process['pipes'][1]);
        $stderr = stream_get_contents($process['pipes'][2]);

        fclose($process['pipes'][1]);
        fclose($process['pipes'][2]);

        $exitCode = proc_close($process['process']);

        if ($exitCode !== 0) {
            $this->fail(
                "Order process failed.\nSTDOUT: {$stdout}\nSTDERR: {$stderr}"
            );
        }
    }
}
