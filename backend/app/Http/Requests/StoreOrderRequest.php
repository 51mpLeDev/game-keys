<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => [
                'required',
                'string',
                'max:100',
            ],

            'order_id' => [
                'nullable',
                'string',
                'max:64',
            ],

            'promo_code' => [
                'nullable',
                'string',
                'max:50',
            ],
        ];
    }
}
