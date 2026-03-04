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

            @keyframes authScan {
                0% { transform: translateX(-18%) skewX(-14deg); opacity: 0; }
                25% { opacity: 0.22; }
                100% { transform: translateX(118%) skewX(-14deg); opacity: 0; }
            }
        </style>
    </head>
    @php
        $isCinematicAuth = request()->routeIs('login') || request()->routeIs('register');
        $authActionVideos = [
            'https://storage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
            'https://storage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyrides.mp4',
            'https://storage.googleapis.com/gtv-videos-bucket/sample/ForBiggerMeltdowns.mp4',
            'https://storage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
        ];
    @endphp
    <body class="font-sans antialiased text-white">
        <div class="relative min-h-screen overflow-hidden bg-slate-950">
            @if ($isCinematicAuth)
                <div class="pointer-events-none absolute inset-0">
                    <video
                        id="auth-action-video-main"
                        autoplay
                        muted
                        playsinline
                        preload="metadata"
                        class="absolute inset-0 h-full w-full object-cover opacity-60 transition-opacity duration-700 [animation:authFloat_22s_ease-in-out_infinite]"
                    ></video>

                    <video
                        id="auth-action-video-overlay"
                        autoplay
                        muted
                        playsinline
                        preload="metadata"
                        class="absolute inset-0 h-full w-full object-cover opacity-30 mix-blend-screen transition-opacity duration-700 [animation:authFloat_28s_ease-in-out_infinite_reverse]"
                    ></video>

                    <div class="absolute inset-0 bg-gradient-to-br from-black/90 via-slate-900/72 to-red-950/52"></div>
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(239,68,68,0.34),transparent_48%),radial-gradient(circle_at_80%_70%,rgba(16,185,129,0.24),transparent_42%)]"></div>
                    <div class="absolute -inset-[24%] rounded-full bg-[conic-gradient(from_0deg,rgba(239,68,68,0.1),rgba(251,146,60,0.24),rgba(16,185,129,0.1),rgba(239,68,68,0.1))] blur-3xl [animation:authPulse_20s_linear_infinite]"></div>
                    <div class="absolute inset-y-0 left-[-22%] w-[42%] bg-gradient-to-r from-transparent via-red-500/16 to-transparent [animation:authScan_9s_linear_infinite]"></div>
                </div>
            @else
                <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-slate-950 via-slate-900 to-black"></div>
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_12%_18%,rgba(239,68,68,0.25),transparent_35%),radial-gradient(circle_at_80%_72%,rgba(59,130,246,0.2),transparent_30%)]"></div>
            @endif

            <div class="relative z-10 flex min-h-screen flex-col items-center justify-center px-4 py-8 sm:px-6">
                <a href="/" class="mb-6 inline-flex items-center justify-center">
                    <x-application-logo variant="red-green" class="text-3xl drop-shadow-[0_10px_26px_rgba(15,23,42,0.7)] sm:text-4xl" />
                </a>

                <div class="w-full max-w-md rounded-2xl border border-white/20 bg-slate-950/65 p-6 shadow-[0_24px_80px_rgba(2,6,23,0.65)] backdrop-blur-md sm:p-7">
                    {{ $slot }}
                </div>

                @if ($isCinematicAuth)
                    <p class="mt-4 text-center text-xs text-slate-200/70">
                        Action-style background clips with automatic fallback.
                    </p>
                @endif
            </div>
        </div>

        @if ($isCinematicAuth)
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const clips = @js($authActionVideos);

                    const mountPlaylist = (video, startIndex = 0) => {
                        if (!video || clips.length === 0) {
                            return;
                        }

                        let cursor = startIndex % clips.length;
                        let failed = 0;

                        const playCurrent = () => {
                            video.src = clips[cursor];
                            const playPromise = video.play();
                            if (playPromise && typeof playPromise.catch === 'function') {
                                playPromise.catch(() => {});
                            }
                        };

                        const nextClip = () => {
                            failed += 1;
                            if (failed >= clips.length) {
                                video.classList.add('opacity-0');
                                return;
                            }

                            cursor = (cursor + 1) % clips.length;
                            playCurrent();
                        };

                        video.addEventListener('canplay', () => {
                            failed = 0;
                            video.classList.remove('opacity-0');
                        });
                        video.addEventListener('ended', () => {
                            cursor = (cursor + 1) % clips.length;
                            playCurrent();
                        });
                        video.addEventListener('error', nextClip);
                        video.addEventListener('stalled', nextClip);
                        video.defaultMuted = true;
                        video.muted = true;

                        playCurrent();
                    };

                    mountPlaylist(document.getElementById('auth-action-video-main'), 0);
                    mountPlaylist(document.getElementById('auth-action-video-overlay'), 1);
                });
            </script>
        @endif
    </body>
</html>
