<x-layouts.stream :title="$movie->title . ' - Player'" :wallpaper-posters="[$movie->backdrop_url, $movie->poster_url]">
    <section class="mx-auto max-w-4xl space-y-4">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-red-400">{{ $movie->language->name }} &middot; {{ $movie->vj->name }}</p>
            <h1 class="text-2xl font-semibold">{{ $movie->title }}</h1>
        </div>

        <div class="relative overflow-hidden rounded-xl border border-white/10 bg-black shadow-[0_20px_60px_rgba(2,6,23,0.6)]">
            <video
                id="player"
                controls
                playsinline
                preload="metadata"
                poster="{{ $movie->backdrop_url ?: $movie->poster_url }}"
                class="aspect-video w-full bg-black"
            ></video>

            <div id="playback-blocked" class="absolute inset-0 hidden items-center justify-center bg-slate-950/90 p-6 text-center">
                <div>
                    <p data-blocked-message class="text-lg font-semibold text-white">Daily free limit reached</p>
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
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.17/dist/hls.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/plyr@3.7.8/dist/plyr.polyfilled.min.js"></script>
    <script>
        (() => {
            const playerElement = document.getElementById('player');
            const blocked = document.getElementById('playback-blocked');
            const blockedMessageEl = blocked?.querySelector('[data-blocked-message]');
            const remainingEl = document.getElementById('remaining-seconds');
            const statusEl = document.getElementById('player-status');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const movieId = {{ $movie->id }};

            let viewId = null;
            let isBlocked = false;
            let hlsInstance = null;
            let plyrInstance = null;
            let lastHeartbeatPosition = 0;
            let heartbeatTimer = null;
            let streamUrl = '';
            let sourceFallbackUrl = '';

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

            const createPlyr = (options) => {
                if (typeof window.Plyr === 'undefined') {
                    return null;
                }

                try {
                    return new window.Plyr(playerElement, options);
                } catch (_error) {
                    return null;
                }
            };

            playerElement.setAttribute('controls', 'controls');
            playerElement.addEventListener('play', () => {
                if (!isBlocked) {
                    setStatus('');
                }
            });
            playerElement.addEventListener('error', () => {
                if (isBlocked) {
                    return;
                }

                const fallbackCandidate = sourceFallbackUrl || streamUrl;
                if (fallbackCandidate) {
                    setStatus('Stream error detected. Switching to fallback source.');
                    initDirectPlayer(fallbackCandidate, 'Playing fallback stream.');
                } else {
                    setStatus('Playback failed for this title. Re-upload source video from admin.');
                }
            });

            initialize().catch(() => {
                enableNativeControls('Unexpected player error. Refresh and try again.');
            });

            async function initialize() {
                const startData = await startPlayback();
                if (!startData) {
                    return;
                }

                viewId = startData.view_id ?? null;
                streamUrl = String(startData.hls_url ?? '');
                sourceFallbackUrl = String(startData.source_url ?? '');

                if (remainingEl && typeof startData.remaining_seconds === 'number') {
                    remainingEl.textContent = startData.remaining_seconds;
                }

                const primaryUrl = streamUrl || sourceFallbackUrl;
                if (!primaryUrl) {
                    setStatus('No playable stream configured for this movie.');
                    return;
                }

                if ((startData.stream_type || 'hls') === 'hls') {
                    initHlsPlayer(primaryUrl, sourceFallbackUrl);
                } else {
                    initDirectPlayer(primaryUrl, 'Playing source stream.');
                }

                startHeartbeat();
                window.addEventListener('beforeunload', () => {
                    void sendStop(false);
                });
                playerElement.addEventListener('ended', () => {
                    void sendStop(true);
                });
            }

            async function startPlayback() {
                let response;
                try {
                    response = await fetch('/api/playback/start', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: jsonHeaders,
                        body: JSON.stringify({ movie_id: movieId }),
                    });
                } catch (_error) {
                    setStatus('Network error starting playback. Check internet/server.');
                    return null;
                }

                let payload = {};
                try {
                    payload = await response.json();
                } catch (_error) {
                    setStatus('Playback response is invalid JSON.');
                    return null;
                }

                if (!response.ok) {
                    if (response.status === 402) {
                        blockPlayback(payload.message || 'Daily free limit reached');
                        return null;
                    }

                    setStatus(payload.message || 'Unable to start playback.');
                    return null;
                }

                return payload;
            }

            function initHlsPlayer(hlsUrl, fallbackUrl) {
                destroyHls();
                destroyPlyr();

                if (window.Hls && window.Hls.isSupported()) {
                    hlsInstance = new window.Hls({
                        enableWorker: true,
                        backBufferLength: 90,
                    });

                    hlsInstance.loadSource(hlsUrl);
                    hlsInstance.attachMedia(playerElement);

                    hlsInstance.on(window.Hls.Events.MANIFEST_PARSED, () => {
                        const qualityHeights = Array.from(new Set(
                            hlsInstance.levels
                                .map((level) => Number(level.height || 0))
                                .filter((height) => height > 0)
                        )).sort((a, b) => a - b);

                        const plyrOptions = qualityHeights.length > 0
                            ? {
                                ...basePlyrOptions,
                                quality: {
                                    default: 0,
                                    options: [0, ...qualityHeights],
                                    forced: true,
                                    onChange: updateHlsQuality,
                                },
                                i18n: {
                                    qualityLabel: {
                                        0: 'Auto',
                                    },
                                },
                            }
                            : {
                                ...basePlyrOptions,
                                quality: undefined,
                                settings: ['speed'],
                            };

                        plyrInstance = createPlyr(plyrOptions);
                        if (!plyrInstance) {
                            enableNativeControls('Using standard browser controls.');
                        }

                        tryPlayImmediately();
                        setStatus('Adaptive streaming ready. Use settings for Auto/360/480/720/1080.');
                    });

                    hlsInstance.on(window.Hls.Events.ERROR, (_event, data) => {
                        if (!data?.fatal) {
                            return;
                        }

                        const fallbackCandidate = fallbackUrl || hlsUrl;
                        setStatus('Adaptive stream failed. Switching to fallback source.');
                        initDirectPlayer(fallbackCandidate, 'Playing fallback stream.');
                    });

                    return;
                }

                if (playerElement.canPlayType('application/vnd.apple.mpegurl')) {
                    playerElement.src = hlsUrl;
                    initNativeHlsPlayer();
                    return;
                }

                if (fallbackUrl && fallbackUrl !== hlsUrl) {
                    initDirectPlayer(fallbackUrl, 'HLS unsupported. Playing source stream.');
                    return;
                }

                setStatus('HLS player failed to load. Refresh page or upload source stream format.');
            }

            function initNativeHlsPlayer() {
                destroyPlyr();

                plyrInstance = createPlyr({
                    ...basePlyrOptions,
                    quality: undefined,
                    settings: ['speed'],
                });

                if (!plyrInstance) {
                    enableNativeControls('Using standard browser controls.');
                }

                tryPlayImmediately();
                setStatus('Native HLS playback active.');
            }

            function initDirectPlayer(sourceUrl, message = 'Playing source stream.') {
                if (!sourceUrl) {
                    setStatus('No fallback source URL configured.');
                    return;
                }

                destroyHls();
                destroyPlyr();

                playerElement.src = sourceUrl;
                plyrInstance = createPlyr({
                    ...basePlyrOptions,
                    quality: undefined,
                    settings: ['speed'],
                });

                if (!plyrInstance) {
                    enableNativeControls('Using standard browser controls.');
                }

                tryPlayImmediately();
                setStatus(message);
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

            function destroyHls() {
                if (!hlsInstance) {
                    return;
                }

                hlsInstance.destroy();
                hlsInstance = null;
            }

            function destroyPlyr() {
                if (!plyrInstance) {
                    return;
                }

                plyrInstance.destroy();
                plyrInstance = null;
            }

            function enableNativeControls(message = '') {
                destroyPlyr();
                playerElement.setAttribute('controls', 'controls');
                if (message) {
                    setStatus(message);
                }
            }

            function startHeartbeat() {
                if (heartbeatTimer) {
                    window.clearInterval(heartbeatTimer);
                }

                heartbeatTimer = window.setInterval(async () => {
                    if (isBlocked || playerElement.paused || playerElement.ended || !viewId) return;

                    const currentPosition = Math.floor(playerElement.currentTime || 0);
                    const delta = Math.max(currentPosition - lastHeartbeatPosition, 10);
                    lastHeartbeatPosition = currentPosition;

                    let response;
                    try {
                        response = await fetch('/api/playback/heartbeat', {
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
                    } catch (_error) {
                        return;
                    }

                    let data;
                    try {
                        data = await response.json();
                    } catch (_error) {
                        return;
                    }

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

                try {
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
                } catch (_error) {
                    // Ignore stop failures to avoid blocking unload.
                }
            }

            function blockPlayback(message) {
                isBlocked = true;
                playerElement.pause();
                blocked.classList.remove('hidden');
                blocked.classList.add('flex');
                if (blockedMessageEl) {
                    blockedMessageEl.textContent = message;
                }
                setStatus(message);
            }

            function setStatus(message) {
                if (!statusEl) return;
                statusEl.textContent = message;
            }
        })();
    </script>
</x-layouts.stream>
