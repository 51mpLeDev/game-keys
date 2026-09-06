<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\PaymentEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use App\Jobs\DeliverOrderJob;

class PaymentWebhookService
{

    public function handle(array $payload): PaymentEvent
    {
        $event = $this->storeEvent($payload);

        $order = Order::query()
            ->where('public_id', $event->order_public_id)
            ->first();

        if ($order) {
            $this->processOrder($order);
        }

        return $event;
    }

    private function storeEvent(array $payload): PaymentEvent
    {
        try {
            return PaymentEvent::create([
                'event_id' => $payload['event_id'],
                'order_public_id' => $payload['order_id'],
                'status' => $payload['status'],
                'amount' => $payload['amount'],
                'currency' => strtoupper($payload['currency']),
                'provider_created_at' => $payload['created_at'],
                'payload' => $payload,
            ]);
        } catch (QueryException $exception) {
            if (!$this->isDuplicateEvent($exception)) {
                throw $exception;
            }

            return PaymentEvent::query()
                ->where('event_id', $payload['event_id'])
                ->firstOrFail();
        }
    }

    public function processOrder(Order $order): void
    {
        $shouldDeliver = DB::transaction(function () use ($order) {
            $order = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $latestEvent = PaymentEvent::query()
                ->where('order_public_id', $order->public_id)
                ->orderByDesc('provider_created_at')
                ->orderByDesc('id')
                ->first();

            if (!$latestEvent) {
                return false;
            }

            if (
                $latestEvent->amount !== $order->amount ||
                strtoupper($latestEvent->currency) !== strtoupper($order->currency)
            ) {
                return false;
            }

            if ($latestEvent->status === 'failed') {
                if ($order->status === OrderStatus::CREATED) {
                    $order->update([
                        'status' => OrderStatus::PAYMENT_FAILED,
                    ]);
                }

                $latestEvent->update([
                    'processed_at' => $latestEvent->processed_at ?? now(),
                ]);

                return false;
            }

            if ($latestEvent->status !== 'paid') {
                return false;
            }

            if ($order->status === OrderStatus::DELIVERED) {
                $latestEvent->update([
                    'processed_at' => $latestEvent->processed_at ?? now(),
                ]);

                return false;
            }

            if (
                $order->status === OrderStatus::PAID ||
                $order->status === OrderStatus::DELIVERING
            ) {
                return false;
            }

            if ($order->status !== OrderStatus::CREATED) {
                return false;
            }

            $order->update([
                'status' => OrderStatus::PAID,
                'paid_at' => now(),
            ]);

            $latestEvent->update([
                'processed_at' => now(),
            ]);

            return true;
        });

        if ($shouldDeliver) {
            $order->refresh();

            DeliverOrderJob::dispatch($order->id);
        }
    }

    private function isDuplicateEvent(QueryException $exception): bool
    {
        return str_contains(
            strtolower($exception->getMessage()),
            'duplicate entry'
        );
    }
}
