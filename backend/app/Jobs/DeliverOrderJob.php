<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrderDeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeliverOrderJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(
        public readonly int $orderId,
    ) {
    }

    public function handle(OrderDeliveryService $deliveryService): void
    {
        $order = Order::query()->find($this->orderId);

        if (!$order) {
            return;
        }

        $deliveryService->deliver($order);
    }
}
