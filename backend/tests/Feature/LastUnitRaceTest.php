<?php

namespace Tests\Feature;

use App\Models\InventoryKey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class LastUnitRaceTest extends TestCase
{
    public function test_two_concurrent_buyers_cannot_take_the_last_key(): void
    {
        DB::table('payment_events')->delete();
        DB::table('provider_issuances')->delete();
        DB::table('order_items')->delete();
        DB::table('orders')->delete();
        DB::table('inventory_keys')->delete();
        DB::table('products')->delete();

        $product = Product::create([
            'sku' => 'KEY-LAST-UNIT-RACE',
            'name' => 'Last Unit Race Test',
            'type' => 'key',
            'price' => 1990,
            'currency' => 'RUB',
            'image' => null,
        ]);

        $key = InventoryKey::create([
            'product_id' => $product->id,
            'code' => 'LAST-UNIT-KEY',
            'status' => 'available',
        ]);

        $orderIdA = (string) Str::uuid();
        $orderIdB = (string) Str::uuid();

        $processes = [
            $this->startProcess(
                $this->createCurlCommand($product->sku, $orderIdA)
            ),
            $this->startProcess(
                $this->createCurlCommand($product->sku, $orderIdB)
            ),
        ];

        $responses = [];

        foreach ($processes as $process) {
            $responses[] = $this->finishProcess($process);
        }

        $successful = array_filter(
            $responses,
            fn (array $response) => $response['status'] === 201
        );

        $outOfStock = array_filter(
            $responses,
            fn (array $response) => $response['status'] === 409
        );

        $this->assertCount(
            1,
            $successful,
            'Exactly one buyer must reserve the last key.'
        );

        $this->assertCount(
            1,
            $outOfStock,
            'Exactly one buyer must receive out_of_stock.'
        );

        $this->assertSame(
            1,
            Order::count(),
            'Only the winning buyer should have an order.'
        );

        $this->assertSame(
            1,
            OrderItem::count(),
            'Only the winning buyer should have an order item.'
        );

        $key->refresh();

        $this->assertSame(
            'reserved',
            $key->status,
            'The last key must belong to the winning order.'
        );

        $this->assertNotNull($key->order_id);

        $this->assertSame(
            1,
            InventoryKey::query()
                ->where('order_id', $key->order_id)
                ->count()
        );

        $winningOrder = Order::query()
            ->whereKey($key->order_id)
            ->firstOrFail();

        $this->assertSame(
            $winningOrder->public_id,
            $orderIdA === $winningOrder->public_id
                ? $orderIdA
                : $orderIdB
        );

        foreach ($responses as $response) {
            if ($response['status'] === 409) {
                $this->assertStringContainsString(
                    'out_of_stock',
                    $response['body']
                );
            }
        }
    }

    private function createCurlCommand(
        string $sku,
        string $orderId
    ): string {
        $payload = json_encode([
            'order_id' => $orderId,
            'sku' => $sku,
            'quantity' => 1,
        ], JSON_THROW_ON_ERROR);

        return sprintf(
            'curl -s -w "\n%%{http_code}" '
            . '-X POST http://nginx/api/orders '
            . '-H "Content-Type: application/json" '
            . '-H "Accept: application/json" '
            . '-d %s',
            escapeshellarg($payload)
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
            $this->fail('Unable to start concurrent buyer process.');
        }

        return [
            'process' => $process,
            'pipes' => $pipes,
        ];
    }

    private function finishProcess(array $process): array
    {
        fclose($process['pipes'][0]);

        $stdout = stream_get_contents($process['pipes'][1]);
        $stderr = stream_get_contents($process['pipes'][2]);

        fclose($process['pipes'][1]);
        fclose($process['pipes'][2]);

        $exitCode = proc_close($process['process']);

        if ($exitCode !== 0) {
            $this->fail(
                "Buyer process failed.\n"
                . "STDOUT: {$stdout}\n"
                . "STDERR: {$stderr}"
            );
        }

        $lines = preg_split('/\R/', trim($stdout));

        $status = (int) array_pop($lines);
        $body = implode("\n", $lines);

        return [
            'status' => $status,
            'body' => $body,
        ];
    }
}
