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

    'autoprocess' => [
        'enabled' => (bool) env('STREAM_AUTOPROCESS_ENABLED', true),
        'ffmpeg_binary' => env('FFMPEG_BINARY', 'ffmpeg'),
        'ffprobe_binary' => env('FFPROBE_BINARY', 'ffprobe'),
        'default_heights' => array_values(array_filter(array_map(
            fn ($value) => (int) trim((string) $value),
            explode(',', (string) env('STREAM_DEFAULT_RENDITIONS', '360,480,720,1080'))
        ))),
        'hls_segment_seconds' => (int) env('STREAM_HLS_SEGMENT_SECONDS', 6),
        'preview_seconds' => (int) env('STREAM_PREVIEW_SECONDS', 20),
        'ffmpeg_preset' => env('STREAM_FFMPEG_PRESET', 'veryfast'),
        'timeout_seconds' => (int) env('STREAM_AUTOPROCESS_TIMEOUT_SECONDS', 0),
    ],
];
