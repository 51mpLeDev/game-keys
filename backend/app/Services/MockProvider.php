<?php

namespace App\Services;

use App\Models\InventoryKey;
use App\Models\ProviderIssuance;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MockProvider
{
    public function issue(
        string $provider,
        string $requestId,
        string $sku,
        int $orderId,
        ?int $inventoryKeyId = null,
    ): string {

        $existing = ProviderIssuance::query()
            ->where('provider', $provider)
            ->where('request_id', $requestId)
            ->first();

        if ($existing) {

            if (
                $existing->status === 'issued' &&
                $existing->code !== null
            ) {
                return $existing->code;
            }

            $issuance = $existing;

            if (
                $issuance->inventory_key_id !== null &&
                $issuance->inventory_key_id !== $inventoryKeyId
            ) {
                throw new RuntimeException(
                    'Provider request_id is already bound to another inventory key.'
                );
            }
        } else {

            $issuance = DB::transaction(function () use (
                $provider,
                $requestId,
                $sku,
                $orderId,
                $inventoryKeyId,
            ) {
                return ProviderIssuance::create([
                    'provider' => $provider,
                    'request_id' => $requestId,
                    'order_id' => $orderId,
                    'inventory_key_id' => $inventoryKeyId,
                    'sku' => $sku,
                    'status' => 'pending',
                ]);
            });
        }

        $config = config("providers.{$provider}", []);

        $delayMs = (int) ($config['delay_ms'] ?? 0);

        if ($delayMs > 0) {
            usleep($delayMs * 1000);
        }

        $mode = $config['mode'] ?? 'success';

        if ($mode === 'error') {
            $issuance->update([
                'status' => 'failed',
                'error' => 'Mock provider returned 500.',
            ]);

            throw new RuntimeException(
                'Mock provider returned 500.'
            );
        }

        if ($inventoryKeyId !== null) {
            $key = InventoryKey::query()
                ->whereKey($inventoryKeyId)
                ->first();

            if (!$key) {
                throw new RuntimeException(
                    'Inventory key not found.'
                );
            }

            if ($key->product?->sku !== $sku) {
                throw new RuntimeException(
                    'Inventory key SKU does not match request SKU.'
                );
            }

            $code = $key->code;
        } else {
            $code = strtoupper($provider) . '-' . strtoupper($requestId);
        }

        if (
            $mode === 'timeout' ||
            ($mode === 'timeout_once' && $issuance->status === 'pending')
        ) {
            if ($mode === 'timeout_once') {
                $issuance->update([
                    'status' => 'issued',
                    'code' => $code,
                    'inventory_key_id' => $inventoryKeyId,
                    'issued_at' => now(),
                    'error' => null,
                ]);
            }

            throw new RuntimeException(
                'Mock provider timeout.'
            );
        }

        $issuance->update([
            'status' => 'issued',
            'code' => $code,
            'inventory_key_id' => $inventoryKeyId,
            'issued_at' => now(),
            'error' => null,
        ]);

        return $code;
    }
}
