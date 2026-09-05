<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProviderIssuance;
use App\Services\MockProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MockProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_timeout_once_returns_same_code_on_retry(): void
    {
        config()->set('providers.provider_a', [
            'mode' => 'timeout_once',
            'delay_ms' => 0,
            'timeout_once' => true,
        ]);

        $product = Product::create([
            'sku' => 'TEST-PROVIDER',
            'name' => 'Provider Test',
            'type' => 'key',
            'price' => 1000,
            'currency' => 'RUB',
        ]);

        $order = Order::create([
            'public_id' => 'provider-test-order',
            'status' => 'created',
            'amount' => 1000,
            'currency' => 'RUB',
        ]);

        $provider = app(MockProvider::class);

        try {
            $provider->issue(
                'provider_a',
                'timeout-test-001',
                $product->sku,
                $order->id,
            );
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'Mock provider timeout.',
                $exception->getMessage()
            );
        }

        $issuance = ProviderIssuance::query()
            ->where('provider', 'provider_a')
            ->where('request_id', 'timeout-test-001')
            ->firstOrFail();

        $this->assertSame('issued', $issuance->status);
        $this->assertNotNull($issuance->code);

        $firstCode = $issuance->code;

        $secondCode = $provider->issue(
            'provider_a',
            'timeout-test-001',
            $product->sku,
            $order->id,
        );

        $this->assertSame($firstCode, $secondCode);

        $this->assertSame(
            1,
            ProviderIssuance::query()
                ->where('provider', 'provider_a')
                ->where('request_id', 'timeout-test-001')
                ->count()
        );
    }
}
