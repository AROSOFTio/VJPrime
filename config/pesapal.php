<?php

return [
    'enabled' => (bool) env('PESAPAL_ENABLED', false),
    'base_url' => rtrim(env('PESAPAL_BASE_URL', 'https://cybqa.pesapal.com/pesapalv3'), '/'),
    'consumer_key' => env('PESAPAL_CONSUMER_KEY'),
    'consumer_secret' => env('PESAPAL_CONSUMER_SECRET'),
    'currency' => env('PESAPAL_CURRENCY', 'UGX'),
    'default_amount' => (float) env('PESAPAL_PREMIUM_AMOUNT', 10000),
    'notification_type' => env('PESAPAL_NOTIFICATION_TYPE', 'GET'),
    'notification_id' => env('PESAPAL_NOTIFICATION_ID'),
];
