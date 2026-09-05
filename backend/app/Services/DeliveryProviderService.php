<?php

namespace App\Services;

use RuntimeException;

class DeliveryProviderService
{
    public function __construct(
        private readonly MockProvider $provider,
    ) {}

    public function issue(
        string $requestId,
        string $sku,
        int $orderId,
        int $inventoryKeyId,
    ): string {
        $provider = config('providers.default', 'provider_a');

        if (!in_array($provider, ['provider_a', 'provider_b'], true)) {
            throw new RuntimeException(
                "Unsupported delivery provider: {$provider}"
            );
        }

        return $this->provider->issue(
            provider: $provider,
            requestId: $requestId,
            sku: $sku,
            orderId: $orderId,
            inventoryKeyId: $inventoryKeyId,
        );
    }
}
