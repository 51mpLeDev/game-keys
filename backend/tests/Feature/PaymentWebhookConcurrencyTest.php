<?php

namespace Tests\Feature;

use App\Models\InventoryKey;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentWebhookConcurrencyTest extends TestCase
{
    public function test_fifty_concurrent_webhooks_issue_exactly_one_key(): void
    {
        DB::table('payment_events')->delete();
        DB::table('provider_issuances')->delete();
        DB::table('order_items')->delete();
        DB::table('orders')->delete();
        DB::table('inventory_keys')->delete();
        DB::table('products')->delete();

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
            'code' => 'RACE-TEST-KEY',
            'status' => 'available',
        ]);

        $order = Order::create([
            'public_id' => (string) Str::uuid(),
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

        $processes = [];

        for ($i = 1; $i <= 50; $i++) {
            $payload = json_encode([
                'event_id' => "race-test-$i",
                'order_id' => $order->public_id,
                'status' => 'paid',
                'amount' => 1290,
                'currency' => 'RUB',
                'created_at' => now()->toISOString(),
            ], JSON_THROW_ON_ERROR);

            $command = sprintf(
                'curl -s -X POST http://nginx/api/webhooks/payment '
                . '-H "Content-Type: application/json" '
                . '-d %s',
                escapeshellarg($payload)
            );

            $processes[] = $this->startProcess($command);
        }

        foreach ($processes as $process) {
            $this->finishProcess($process);
        }

        $order->refresh();

        $this->assertSame(
            'paid',
            $order->status->value
        );

        exec(
            'php artisan queue:work database --stop-when-empty --tries=1 --no-interaction',
            $output,
            $exitCode
        );

        $this->assertSame(0, $exitCode);

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
            $this->fail('Unable to start concurrent webhook process.');
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
                "Webhook process failed.\nSTDOUT: {$stdout}\nSTDERR: {$stderr}"
            );
        }
    }
}
