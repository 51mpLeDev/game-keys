<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Services\PromoCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PromoCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_promo_code_calculates_discount_on_server(): void
    {
        $promo = PromoCode::create([
            'code' => 'PROMO10',
            'discount_percent' => 10,
            'max_uses' => 10,
            'uses' => 0,
            'is_active' => true,
        ]);

        $order = Order::create([
            'public_id' => fake()->uuid(),
            'status' => 'created',
            'amount' => 1290,
            'currency' => 'RUB',
        ]);

        $result = app(PromoCodeService::class)->apply(
            'promo10',
            $order
        );

        $this->assertSame('PROMO10', $result['code']);
        $this->assertSame(10, $result['discount_percent']);
        $this->assertSame(129, $result['discount']);
        $this->assertSame(1161, $result['amount']);

        $this->assertDatabaseHas('promo_codes', [
            'id' => $promo->id,
            'uses' => 1,
        ]);

        $this->assertDatabaseHas('promo_code_usages', [
            'promo_code_id' => $promo->id,
            'order_id' => $order->id,
            'discount' => 129,
        ]);
    }

    public function test_same_order_can_apply_promo_only_once(): void
    {
        PromoCode::create([
            'code' => 'PROMO10',
            'discount_percent' => 10,
            'max_uses' => 10,
            'uses' => 0,
            'is_active' => true,
        ]);

        $order = Order::create([
            'public_id' => fake()->uuid(),
            'status' => 'created',
            'amount' => 1290,
            'currency' => 'RUB',
        ]);

        $service = app(PromoCodeService::class);

        $first = $service->apply('PROMO10', $order);
        $second = $service->apply('PROMO10', $order);

        $this->assertSame(129, $first['discount']);
        $this->assertSame(129, $second['discount']);

        $this->assertSame(1161, $first['amount']);
        $this->assertSame(1161, $second['amount']);

        $this->assertDatabaseCount('promo_code_usages', 1);

        $this->assertDatabaseHas('promo_codes', [
            'code' => 'PROMO10',
            'uses' => 1,
        ]);
    }

    public function test_promo_code_respects_max_uses(): void
    {
        $promo = PromoCode::create([
            'code' => 'PROMO10',
            'discount_percent' => 10,
            'max_uses' => 2,
            'uses' => 0,
            'is_active' => true,
        ]);

        $orders = collect(range(1, 3))
            ->map(fn () => Order::create([
                'public_id' => fake()->uuid(),
                'status' => 'created',
                'amount' => 1000,
                'currency' => 'RUB',
            ]));

        $service = app(PromoCodeService::class);

        $service->apply('PROMO10', $orders[0]);
        $service->apply('PROMO10', $orders[1]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Promo code usage limit exceeded.'
        );

        $service->apply('PROMO10', $orders[2]);

        $promo->refresh();

        $this->assertSame(2, $promo->uses);

        $this->assertDatabaseCount('promo_code_usages', 2);
    }
}
