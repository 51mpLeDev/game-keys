<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderDeliveryController;
use App\Http\Controllers\PaymentWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'game-keys-api',
    ]);
});

Route::post(
    '/orders',
    [OrderController::class, 'store']
);

Route::get(
    '/orders/{order}',
    [OrderController::class, 'show']
);

Route::post(
    '/orders/{order}/retry-delivery',
    [OrderDeliveryController::class, 'retry']
);

Route::post(
    '/webhooks/payment',
    PaymentWebhookController::class
);
