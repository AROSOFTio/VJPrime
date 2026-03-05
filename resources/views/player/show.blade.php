<x-layouts.stream :title="$movie->title . ' - Player'" :wallpaper-posters="[$movie->backdrop_url, $movie->poster_url]">
    <section class="mx-auto max-w-5xl space-y-4">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-red-400">{{ $movie->language->name }} &middot; {{ $movie->vj->name }}</p>
            <h1 class="text-2xl font-semibold">{{ $movie->title }}</h1>
        </div>

        <div class="relative overflow-hidden rounded-xl border border-white/10 bg-black shadow-[0_20px_60px_rgba(2,6,23,0.6)]">
            <video id="player" playsinline preload="metadata" class="aspect-video w-full bg-black"></video>

            <div id="playback-blocked" class="absolute inset-0 hidden items-center justify-center bg-slate-950/90 p-6 text-center">
                <div>
                    <p class="text-lg font-semibold text-white">Daily free limit reached</p>
                    <p class="mt-2 text-sm text-slate-300">You have used your 30 free minutes for this 24-hour window.</p>
                    <a href="{{ route('account.index') }}" class="mt-4 inline-block rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white">Upgrade Prompt</a>
                </div>
            </div>
        </div>

        <div class="rounded-md border border-white/10 bg-slate-900/70 p-4 text-sm text-slate-200">
            <p>
                Free quota:
                @if (auth()->user()->isPremium())
                    <span class="font-semibold text-emerald-300">Premium Unlimited</span>
                @else
                    <span id="remaining-seconds" class="font-semibold">{{ $quotaRemaining }}</span> seconds remaining today
                @endif
            </p>
            <p id="player-status" class="mt-2 text-xs text-slate-400"></p>
        </div>
    </section>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/plyr@3.7.8/dist/plyr.css" />
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/plyr@3.7.8/dist/plyr.polyfilled.min.js"></script>
    <script>
        (async () => {
            const playerElement = document.getElementById('player');
            const blocked = document.getElementById('playback-blocked');
            const remainingEl = document.getElementById('remaining-seconds');
            const statusEl = document.getElementById('player-status');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const movieId = {{ $movie->id }};

            let viewId = null;
            let isBlocked = false;
            let hlsInstance = null;
            let plyrInstance = null;
            let lastHeartbeatPosition = 0;

            const jsonHeaders = {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
            };

            const basePlyrOptions = {
                controls: [
                    'play-large',
                    'rewind',
                    'play',
                    'fast-forward',
                    'progress',
                    'current-time',
                    'duration',
                    'mute',
                    'volume',
                    'settings',
                    'pip',
                    'airplay',
                    'fullscreen',
                ],
                settings: ['quality', 'speed'],
                speed: {
                    selected: 1,
                    options: [0.5, 0.75, 1, 1.25, 1.5, 2],
                },
                autoplay: true,
                muted: true,
                keyboard: { focused: true, global: true },
                tooltips: { controls: true, seek: true },
                seekTime: 10,
            };

            const startResponse = await fetch('/api/playback/start', {
                method: 'POST',
                credentials: 'same-origin',
                headers: jsonHeaders,
                body: JSON.stringify({ movie_id: movieId }),
            });

            let startData = {};
            try {
                startData = await startResponse.json();
            } catch (_error) {
                setStatus('Failed to load playback data.');
                return;
            }

            if (!startResponse.ok) {
                if (startResponse.status === 402) {
                    blockPlayback(startData.message || 'Daily free limit reached');
                    return;
                }
                setStatus(startData.message || 'Unable to start playback.');
                return;
            }

            viewId = startData.view_id;
            if (remainingEl && typeof startData.remaining_seconds === 'number') {
                remainingEl.textContent = startData.remaining_seconds;
            }

            if ((startData.stream_type || 'hls') === 'hls') {
                initHlsPlayer(startData.hls_url);
            } else {
                initDirectPlayer(startData.hls_url);
            }

            startHeartbeat();
            window.addEventListener('beforeunload', () => sendStop(false));
            playerElement.addEventListener('ended', () => sendStop(true));

            function initHlsPlayer(sourceUrl) {
                if (window.Hls && window.Hls.isSupported()) {
                    hlsInstance = new Hls({
                        enableWorker: true,
                        backBufferLength: 90,
                    });

                    hlsInstance.loadSource(sourceUrl);
                    hlsInstance.attachMedia(playerElement);

                    hlsInstance.on(Hls.Events.MANIFEST_PARSED, () => {
                        const qualityHeights = Array.from(
                            new Set(
                                hlsInstance.levels
                                    .map((level) => Number(level.height || 0))
                                    .filter((height) => height > 0)
                            )
                        ).sort((a, b) => a - b);

                        plyrInstance = new Plyr(playerElement, {
                            ...basePlyrOptions,
                            quality: {
                                default: 0,
                                options: [0, ...qualityHeights],
                                forced: true,
                                onChange: (newQuality) => updateHlsQuality(newQuality),
                            },
                            i18n: {
                                qualityLabel: {
                                    0: 'Auto',
                                },
                            },
                        });

                        attachPlyrEvents();
                        tryPlayImmediately();
                        setStatus('Adaptive streaming ready. Use Settings to switch Auto/360/480/720/1080.');
                    });

                    hlsInstance.on(Hls.Events.ERROR, (_event, data) => {
                        if (!data?.fatal) {
                            return;
                        }

                        setStatus('HLS error detected, switching to normal stream mode.');
                        hlsInstance.destroy();
                        hlsInstance = null;
                        initDirectPlayer(sourceUrl);
                    });

                    return;
                }

                if (playerElement.canPlayType('application/vnd.apple.mpegurl')) {
                    playerElement.src = sourceUrl;
                    initNativeHlsPlayer();
                    return;
                }

                setStatus('This browser does not support HLS playback.');
            }

            function initNativeHlsPlayer() {
                plyrInstance = new Plyr(playerElement, {
                    ...basePlyrOptions,
                    quality: undefined,
                    settings: ['speed'],
                });

                attachPlyrEvents();
                tryPlayImmediately();
                setStatus('Native HLS playback active.');
            }

            function initDirectPlayer(sourceUrl) {
                if (hlsInstance) {
                    hlsInstance.destroy();
                    hlsInstance = null;
                }

                if (plyrInstance) {
                    plyrInstance.destroy();
                    plyrInstance = null;
                }

                playerElement.src = sourceUrl;
                plyrInstance = new Plyr(playerElement, {
                    ...basePlyrOptions,
                    quality: undefined,
                    settings: ['speed'],
                });

                attachPlyrEvents();
                tryPlayImmediately();
                setStatus('Playing normal stream mode.');
            }

            function updateHlsQuality(newQuality) {
                if (!hlsInstance) {
                    return;
                }

                if (Number(newQuality) === 0) {
                    hlsInstance.currentLevel = -1;
                    return;
                }

                const levelIndex = hlsInstance.levels.findIndex((level) => Number(level.height || 0) === Number(newQuality));
                if (levelIndex >= 0) {
                    hlsInstance.currentLevel = levelIndex;
                }
            }

            function tryPlayImmediately() {
                playerElement.muted = true;
                const promise = playerElement.play();
                if (promise && typeof promise.catch === 'function') {
                    promise.catch(() => {
                        setStatus('Tap play if autoplay is blocked by browser policy.');
                    });
                }
            }

            function attachPlyrEvents() {
                playerElement.addEventListener('play', () => {
                    if (!isBlocked) {
                        setStatus('');
                    }
                });
            }

            function startHeartbeat() {
                setInterval(async () => {
                    if (isBlocked || playerElement.paused || playerElement.ended || !viewId) return;

                    const currentPosition = Math.floor(playerElement.currentTime || 0);
                    const delta = Math.max(currentPosition - lastHeartbeatPosition, 10);
                    lastHeartbeatPosition = currentPosition;

                    const response = await fetch('/api/playback/heartbeat', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: jsonHeaders,
                        body: JSON.stringify({
                            movie_id: movieId,
                            view_id: viewId,
                            seconds_watched_delta: Math.min(delta, 30),
                            last_position_seconds: currentPosition,
                        }),
                    });

                    const data = await response.json();

                    if (remainingEl && typeof data.remaining_seconds === 'number') {
                        remainingEl.textContent = data.remaining_seconds;
                    }

                    if (response.status === 402) {
                        blockPlayback(data.message || 'Daily free limit reached');
                    }
                }, 15000);
            }

            async function sendStop(completed) {
                if (!viewId) return;

                await fetch('/api/playback/stop', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: jsonHeaders,
                    body: JSON.stringify({
                        movie_id: movieId,
                        view_id: viewId,
                        last_position_seconds: Math.floor(playerElement.currentTime || 0),
                        completed: completed,
                    }),
                });
            }

            function blockPlayback(message) {
                isBlocked = true;
                playerElement.pause();
                blocked.classList.remove('hidden');
                blocked.classList.add('flex');
                blocked.querySelector('p')?.textContent = message;
                setStatus(message);
            }

            function setStatus(message) {
                if (!statusEl) return;
                statusEl.textContent = message;
            }
        })();
    </script>
</x-layouts.stream>

