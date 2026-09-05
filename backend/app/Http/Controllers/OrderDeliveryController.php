<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderDeliveryService;
use Illuminate\Http\JsonResponse;

class OrderDeliveryController extends Controller
{
    public function retry(
        string $order,
        OrderDeliveryService $service,
    ): JsonResponse {
        $order = Order::query()
            ->where('public_id', $order)
            ->firstOrFail();

        $order = $service->deliver($order);

        return response()->json([
            'data' => [
                'id' => $order->public_id,
                'status' => $order->status->value,
                'keys' => $order->inventoryKeys()
                    ->get()
                    ->map(fn ($key) => [
                        'code' => $key->code,
                    ])
                    ->values(),
            ],
        ]);
    }
}
