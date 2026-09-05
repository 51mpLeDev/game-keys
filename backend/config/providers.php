<?php

return [
    'default' => env('DELIVERY_PROVIDER', 'provider_a'),

    'provider_a' => [
        'mode' => env('PROVIDER_A_MODE', 'success'),
        'delay_ms' => (int)env('PROVIDER_A_DELAY_MS', 0),
        'timeout_once' => filter_var(
            env('PROVIDER_A_TIMEOUT_ONCE', false),
            FILTER_VALIDATE_BOOL
        ),
    ],

    'provider_b' => [
        'mode' => env('PROVIDER_B_MODE', 'success'),
        'delay_ms' => (int)env('PROVIDER_B_DELAY_MS', 0),
        'timeout_once' => filter_var(
            env('PROVIDER_B_TIMEOUT_ONCE', false),
            FILTER_VALIDATE_BOOL
        ),
    ],
];
