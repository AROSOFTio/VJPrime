<?php

return [
    'enabled' => (bool) env('PESAPAL_ENABLED', false),
    'base_url' => rtrim(env('PESAPAL_BASE_URL', 'https://pay.pesapal.com/v3'), '/'),
    'consumer_key' => env('PESAPAL_CONSUMER_KEY'),
    'consumer_secret' => env('PESAPAL_CONSUMER_SECRET'),
    'currency' => env('PESAPAL_CURRENCY', 'UGX'),
    'callback_url' => env('PESAPAL_CALLBACK_URL', 'https://vjprime.arosoft.io/billing/pesapal/callback'),
    'ipn_url' => env('PESAPAL_IPN_URL', 'https://vjprime.arosoft.io/billing/pesapal/ipn'),
    'notification_type' => env('PESAPAL_NOTIFICATION_TYPE', 'GET'),
    'notification_id' => env('PESAPAL_NOTIFICATION_ID'),
    'default_plan' => env('PESAPAL_DEFAULT_PLAN', 'daily'),
    'plans' => [
        'daily' => [
            'code' => 'daily',
            'name' => 'Daily Unlimited',
            'amount' => (float) env('PESAPAL_PLAN_DAILY_AMOUNT', 1000),
            'days' => (int) env('PESAPAL_PLAN_DAILY_DAYS', 1),
        ],
        'weekly' => [
            'code' => 'weekly',
            'name' => 'Weekly Unlimited',
            'amount' => (float) env('PESAPAL_PLAN_WEEKLY_AMOUNT', 6000),
            'days' => (int) env('PESAPAL_PLAN_WEEKLY_DAYS', 7),
        ],
        'biweekly' => [
            'code' => 'biweekly',
            'name' => '2 Weeks Unlimited',
            'amount' => (float) env('PESAPAL_PLAN_BIWEEKLY_AMOUNT', 11000),
            'days' => (int) env('PESAPAL_PLAN_BIWEEKLY_DAYS', 14),
        ],
        'monthly' => [
            'code' => 'monthly',
            'name' => '30 Days Unlimited',
            'amount' => (float) env('PESAPAL_PLAN_MONTHLY_AMOUNT', 21000),
            'days' => (int) env('PESAPAL_PLAN_MONTHLY_DAYS', 30),
        ],
    ],
];
