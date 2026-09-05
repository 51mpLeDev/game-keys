<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function store(
        StoreOrderRequest $request,
        OrderService $service
    ): JsonResponse {
        $product = Product::query()
            ->where('sku', $request->string('sku'))
            ->firstOrFail();

        $order = $service->create(
            $product,
            1,
            $request->input('order_id'),
        );

        return response()->json([
            'data' => $this->transform($order),
        ], 201);
    }

    public function show(string $order): JsonResponse
    {
        $order = Order::query()
            ->where('public_id', $order)
            ->firstOrFail();

        $order->load([
            'items',
            'inventoryKeys',
        ]);

        return response()->json([
            'data' => $this->transform($order),
        ]);
    }

    private function transform(Order $order): array
    {
        return [
            'id' => $order->public_id,
            'status' => $order->status->value,
            'amount' => $order->amount,
            'currency' => $order->currency,

            'items' => $order->items->map(
                fn ($item) => [
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'price' => $item->price,
                    'currency' => $item->currency,
                    'quantity' => $item->quantity,
                ]
            )->values(),

            'keys' => $order->inventoryKeys->map(
                fn ($key) => [
                    'code' => $key->code,
                ]
            )->values(),

            'paid_at' => $order->paid_at?->toISOString(),
            'delivered_at' => $order->delivered_at?->toISOString(),
        ];
    }
}
