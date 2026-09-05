<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentWebhookRequest;
use App\Services\PaymentWebhookService;
use Illuminate\Http\JsonResponse;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentWebhookService $service,
    ) {
    }

    public function __invoke(
        PaymentWebhookRequest $request
    ): JsonResponse {
        $event = $this->service->handle(
            $request->validated()
        );

        return response()->json([
            'ok' => true,
            'event_id' => $event->event_id,
        ]);
    }
}
