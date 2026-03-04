<?php

return [
    'free' => [
        'daily_seconds' => (int) env('FREE_DAILY_SECONDS', 1800),
    ],

    'downloads' => [
        'premium_only' => (bool) env('DOWNLOADS_PREMIUM_ONLY', true),
        'free_daily_limit' => (int) env('FREE_DAILY_DOWNLOAD_LIMIT', 1),
        'signed_url_minutes' => (int) env('DOWNLOAD_URL_MINUTES', 10),
    ],

    'online' => [
        'base' => (int) env('ONLINE_USERS_BASE', 120),
        'window_minutes' => (int) env('ONLINE_USERS_WINDOW_MINUTES', 5),
    ],

    'signed_playlist_minutes' => (int) env('PLAYLIST_URL_MINUTES', 10),
];
