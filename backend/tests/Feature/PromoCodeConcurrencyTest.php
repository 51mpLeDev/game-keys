<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PromoCodeConcurrencyTest extends TestCase
{
    public function test_fifty_concurrent_orders_respect_promo_max_uses(): void
    {
        DB::table('payment_events')->delete();
        DB::table('provider_issuances')->delete();
        DB::table('promo_code_usages')->delete();
        DB::table('order_items')->delete();
        DB::table('orders')->delete();
        DB::table('inventory_keys')->delete();
        DB::table('promo_codes')->delete();
        DB::table('products')->delete();

        $product = Product::create([
            'sku' => 'PROMO-RACE-TEST',
            'name' => 'Promo Race Test',
            'type' => 'key',
            'price' => 1000,
            'currency' => 'RUB',
            'image' => null,
        ]);

        PromoCode::create([
            'code' => 'PROMO10',
            'discount_percent' => 10,
            'max_uses' => 10,
            'uses' => 0,
            'is_active' => true,
        ]);

        $processes = [];

        for ($i = 1; $i <= 50; $i++) {
            $orderId = (string) Str::uuid();

            $payload = json_encode([
                'order_id' => $orderId,
                'sku' => $product->sku,
                'quantity' => 1,
                'promo_code' => 'PROMO10',
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

        $successful = 0;
        $failed = 0;

        foreach ($processes as $process) {
            $result = $this->finishProcess($process);

            if ($result['exit_code'] === 0) {
                $response = json_decode(
                    $result['stdout'],
                    true
                );

                if (
                    is_array($response)
                    && isset($response['data'])
                    && isset($response['data']['amount'])
                    && $response['data']['amount'] === 900
                ) {
                    $successful++;
                } else {
                    $failed++;
                }
            } else {
                $failed++;
            }
        }

        $promo = PromoCode::query()
            ->where('code', 'PROMO10')
            ->firstOrFail();

        $this->assertSame(10, $successful);
        $this->assertSame(40, $failed);

        $this->assertSame(10, $promo->uses);

        $this->assertSame(
            10,
            PromoCodeUsage::count()
        );

        $this->assertSame(
            10,
            Order::count()
        );

        $this->assertSame(
            10,
            Order::query()
                ->where('amount', 900)
                ->count()
        );

        $this->assertSame(
            0,
            Order::query()
                ->where('amount', 1000)
                ->count()
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
            $this->fail(
                'Unable to start concurrent promo process.'
            );
        }

        return [
            'process' => $process,
            'pipes' => $pipes,
        ];
    }

    private function finishProcess(array $process): array
    {
        fclose($process['pipes'][0]);

        $stdout = stream_get_contents(
            $process['pipes'][1]
        );

        $stderr = stream_get_contents(
            $process['pipes'][2]
        );

        fclose($process['pipes'][1]);
        fclose($process['pipes'][2]);

        $exitCode = proc_close(
            $process['process']
        );

        if ($exitCode !== 0) {
            $this->fail(
                "Promo process failed.\n"
                . "STDOUT: {$stdout}\n"
                . "STDERR: {$stderr}"
            );
        }

        return [
            'stdout' => $stdout,
            'stderr' => $stderr,
            'exit_code' => $exitCode,
        ];
    }
}
