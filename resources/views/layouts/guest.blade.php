<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'VJPrime') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            @keyframes authFloat {
                0% { transform: scale(1.08) translate3d(0, 0, 0); }
                50% { transform: scale(1.14) translate3d(-1.5%, -1.5%, 0); }
                100% { transform: scale(1.08) translate3d(0, 0, 0); }
            }

            @keyframes authPulse {
                0% { opacity: 0.25; transform: rotate(0deg) scale(1); }
                50% { opacity: 0.55; transform: rotate(180deg) scale(1.1); }
                100% { opacity: 0.25; transform: rotate(360deg) scale(1); }
            }
        </style>
    </head>
    @php
        $isCinematicAuth = request()->routeIs('login') || request()->routeIs('register');
    @endphp
    <body class="font-sans antialiased text-white">
        <div class="relative min-h-screen overflow-hidden bg-slate-950">
            @if ($isCinematicAuth)
                <div class="pointer-events-none absolute inset-0">
                    <video
                        autoplay
                        muted
                        loop
                        playsinline
                        preload="none"
                        class="absolute inset-0 h-full w-full object-cover opacity-55 [animation:authFloat_22s_ease-in-out_infinite]"
                    >
                        <source src="https://assets.mixkit.co/videos/preview/mixkit-dj-turning-on-the-spotlight-34697-large.mp4" type="video/mp4">
                    </video>

                    <video
                        autoplay
                        muted
                        loop
                        playsinline
                        preload="none"
                        class="absolute inset-0 h-full w-full object-cover opacity-35 mix-blend-screen [animation:authFloat_28s_ease-in-out_infinite_reverse]"
                    >
                        <source src="https://assets.mixkit.co/videos/preview/mixkit-crowd-swaying-to-a-dj-at-a-music-festival-2440-large.mp4" type="video/mp4">
                    </video>

                    <div class="absolute inset-0 bg-gradient-to-br from-black/85 via-slate-900/70 to-red-950/55"></div>
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(244,63,94,0.32),transparent_46%),radial-gradient(circle_at_80%_70%,rgba(249,115,22,0.28),transparent_40%)]"></div>
                    <div class="absolute -inset-[24%] rounded-full bg-[conic-gradient(from_0deg,rgba(239,68,68,0.1),rgba(251,146,60,0.24),rgba(59,130,246,0.08),rgba(239,68,68,0.1))] blur-3xl [animation:authPulse_20s_linear_infinite]"></div>
                </div>
            @else
                <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-slate-950 via-slate-900 to-black"></div>
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_12%_18%,rgba(239,68,68,0.25),transparent_35%),radial-gradient(circle_at_80%_72%,rgba(59,130,246,0.2),transparent_30%)]"></div>
            @endif

            <div class="relative z-10 flex min-h-screen flex-col items-center justify-center px-4 py-8 sm:px-6">
                <a href="/" class="mb-6 inline-flex items-center justify-center">
                    <x-application-logo class="h-14 w-auto text-white drop-shadow-[0_10px_26px_rgba(15,23,42,0.7)] sm:h-16" />
                </a>

                <div class="w-full max-w-md rounded-2xl border border-white/20 bg-slate-950/65 p-6 shadow-[0_24px_80px_rgba(2,6,23,0.65)] backdrop-blur-md sm:p-7">
                    {{ $slot }}
                </div>

                @if ($isCinematicAuth)
                    <p class="mt-4 text-center text-xs text-slate-200/70">
                        Background clips sourced from Mixkit free stock video.
                    </p>
                @endif
            </div>
        </div>
    </body>
</html>
