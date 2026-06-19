<?php

return [
    'access_token'   => env('MP_ACCESS_TOKEN', ''),
    'public_key'     => env('MP_PUBLIC_KEY', ''),
    'webhook_secret' => env('MP_WEBHOOK_SECRET', ''),
    'trial_days'     => (int) env('MP_TRIAL_DAYS', 14),
    'grace_days'     => (int) env('MP_GRACE_DAYS', 7),
    'sandbox'        => (bool) env('MP_SANDBOX', true),
    'back_urls'      => [
        'success' => env('APP_URL_WEB', 'http://localhost:8001') . '/checkout/sucesso',
        'failure' => env('APP_URL_WEB', 'http://localhost:8001') . '/checkout/falha',
        'pending' => env('APP_URL_WEB', 'http://localhost:8001') . '/checkout/pendente',
    ],
];
