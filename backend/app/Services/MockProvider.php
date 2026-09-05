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
        /*
         * Сначала ищем уже существующий request.
         *
         * request_id является идемпотентным ключом Provider API.
         */
        $existing = ProviderIssuance::query()
            ->where('provider', $provider)
            ->where('request_id', $requestId)
            ->first();

        if ($existing) {
            /*
             * Provider уже выдал результат.
             * Повторный запрос должен вернуть тот же code.
             */
            if (
                $existing->status === 'issued' &&
                $existing->code !== null
            ) {
                return $existing->code;
            }

            /*
             * pending означает, что предыдущий запрос мог
             * закончиться timeout.
             *
             * Поэтому продолжаем обработку того же request_id.
             */
            $issuance = $existing;

            /*
             * Если старый request принадлежит другому ключу,
             * это попытка использовать тот же request_id
             * с другими параметрами.
             */
            if (
                $issuance->inventory_key_id !== null &&
                $issuance->inventory_key_id !== $inventoryKeyId
            ) {
                throw new RuntimeException(
                    'Provider request_id is already bound to another inventory key.'
                );
            }
        } else {
            /*
             * Создаём pending issuance.
             */
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

        /*
         * Mock delay.
         */
        $delayMs = (int) ($config['delay_ms'] ?? 0);

        if ($delayMs > 0) {
            usleep($delayMs * 1000);
        }

        $mode = $config['mode'] ?? 'success';

        /*
         * Mock provider 500.
         *
         * В отличие от старой версии failed не является
         * окончательным состоянием. Retry сможет повторить request.
         */
        if ($mode === 'error') {
            $issuance->update([
                'status' => 'failed',
                'error' => 'Mock provider returned 500.',
            ]);

            throw new RuntimeException(
                'Mock provider returned 500.'
            );
        }

        /*
         * Provider выдаёт реальный код из inventory.
         */
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
            /*
             * Standalone provider test.
             *
             * Если inventory key не передан, используем
             * синтетический provider code.
             */
            $code = strtoupper($provider) . '-' . strtoupper($requestId);
        }

        /*
         * Timeout.
         *
         * В timeout_once Provider фактически выдаёт ключ,
         * но клиент не получает ответ.
         *
         * Следующий запрос с тем же request_id увидит issued
         * и вернёт тот же code.
         */
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

        /*
         * Обычный успешный ответ.
         */
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
