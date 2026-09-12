<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductPriceUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $productId,
        public int $price,
        public string $currency,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('products'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'product.price.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'product_id' => $this->productId,
            'price' => $this->price,
            'currency' => $this->currency,
        ];
    }
}
