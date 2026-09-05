<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_id' => [
                'required',
                'string',
                'max:128',
            ],

            'order_id' => [
                'required',
                'string',
                'max:64',
            ],

            'status' => [
                'required',
                'string',
                'in:paid,failed',
            ],

            'amount' => [
                'required',
                'integer',
                'min:0',
            ],

            'currency' => [
                'required',
                'string',
                'size:3',
            ],

            'created_at' => [
                'required',
                'date',
            ],
        ];
    }
}
