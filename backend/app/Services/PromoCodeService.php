<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use Illuminate\Support\Facades\DB;
use App\Exceptions\PromoCodeException;

class PromoCodeService
{
    public function apply(
        string $code,
        Order $order,
    ): array {
        $code = strtoupper(trim($code));

        return DB::transaction(function () use ($code, $order) {
            $promo = PromoCode::query()
                ->where('code', $code)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$promo) {
                throw new PromoCodeException(
                    'Promo code is invalid.'
                );
            }

            $existingUsage = PromoCodeUsage::query()
                ->where('promo_code_id', $promo->id)
                ->where('order_id', $order->id)
                ->first();

            if ($existingUsage) {
                $discount = $existingUsage->discount;

                return [
                    'code' => $promo->code,
                    'discount_percent' => $promo->discount_percent,
                    'discount' => $discount,
                    'amount' => max(0, $order->amount - $discount),
                ];
            }

            if ($promo->uses >= $promo->max_uses) {
                throw new PromoCodeException(
                    'Promo code usage limit exceeded.'
                );
            }

            $discount = intdiv(
                $order->amount * $promo->discount_percent,
                100
            );

            $finalAmount = max(
                0,
                $order->amount - $discount
            );

            PromoCodeUsage::create([
                'promo_code_id' => $promo->id,
                'order_id' => $order->id,
                'discount' => $discount,
            ]);

            $promo->increment('uses');

            return [
                'code' => $promo->code,
                'discount_percent' => $promo->discount_percent,
                'discount' => $discount,
                'amount' => $finalAmount,
            ];
        });
    }
}
