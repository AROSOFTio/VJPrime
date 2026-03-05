<x-layouts.stream :title="$movie->title . ' - Player'" :wallpaper-posters="[$movie->backdrop_url, $movie->poster_url]">
    <section class="mx-auto max-w-3xl space-y-4">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-red-400">{{ $movie->language->name }} &middot; {{ $movie->vj->name }}</p>
            <h1 class="text-2xl font-semibold">{{ $movie->title }}</h1>
        </div>

        <div class="relative overflow-hidden rounded-xl border border-white/10 bg-black shadow-[0_20px_60px_rgba(2,6,23,0.6)]">
            <video
                id="player"
                controls
                playsinline
                preload="auto"
                poster="{{ $movie->backdrop_url ?: $movie->poster_url }}"
                class="aspect-video w-full bg-black object-contain md:max-h-[58vh]"
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

        <div class="rounded-md border border-white/10 bg-slate-900/70 p-4">
            <div class="flex flex-wrap items-center gap-2">
                <button
                    id="download-button"
                    type="button"
                    class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    Download
                </button>

                <div class="relative">
                    <button
                        id="share-toggle"
                        type="button"
                        class="inline-flex items-center rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-slate-100 hover:bg-white/20"
                    >
                        Share
                    </button>

                    <div
                        id="share-menu"
                        class="absolute left-0 z-30 mt-2 hidden w-56 overflow-hidden rounded-md border border-white/15 bg-slate-900/95 shadow-xl"
                    >
                        <a data-share-network="telegram" href="#" target="_blank" rel="noopener noreferrer" class="block px-4 py-2 text-sm text-slate-200 hover:bg-slate-800">Telegram</a>
                        <a data-share-network="whatsapp" href="#" target="_blank" rel="noopener noreferrer" class="block px-4 py-2 text-sm text-slate-200 hover:bg-slate-800">WhatsApp</a>
                        <a data-share-network="x" href="#" target="_blank" rel="noopener noreferrer" class="block px-4 py-2 text-sm text-slate-200 hover:bg-slate-800">X</a>
                        <a data-share-network="facebook" href="#" target="_blank" rel="noopener noreferrer" class="block px-4 py-2 text-sm text-slate-200 hover:bg-slate-800">Facebook</a>
                        <button data-share-copy type="button" class="block w-full px-4 py-2 text-left text-sm text-slate-200 hover:bg-slate-800">Copy Link</button>
                    </div>
                </div>
            </div>
            <p id="action-status" class="mt-2 text-xs text-slate-400"></p>
        </div>
    </section>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/plyr@3.7.8/dist/plyr.css" />
    <style>
        .plyr--video .plyr__control--overlaid {
            display: none !important;
        }

        .plyr--video .plyr__controls {
            opacity: 1 !important;
            transform: translateY(0) !important;
        }

        .plyr--video.plyr--hide-controls .plyr__controls {
            opacity: 1 !important;
            pointer-events: auto !important;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.17/dist/hls.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/plyr@3.7.8/dist/plyr.polyfilled.min.js"></script>
    <script>
        (() => {
            const playerElement = document.getElementById('player');
            const blocked = document.getElementById('playback-blocked');
            const blockedMessageEl = blocked?.querySelector('[data-blocked-message]');
            const remainingEl = document.getElementById('remaining-seconds');
            const statusEl = document.getElementById('player-status');
            const actionStatusEl = document.getElementById('action-status');
            const downloadButton = document.getElementById('download-button');
            const shareToggle = document.getElementById('share-toggle');
            const shareMenu = document.getElementById('share-menu');
            const copyShareButton = document.querySelector('[data-share-copy]');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const movieId = {{ $movie->id }};
            const downloadEndpoint = @json(route('downloads.link', $movie));
            const shareUrl = @json(route('movies.show', $movie->slug));
            const shareTitle = @json($movie->title . ' | VJPrime');

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
            const postHeaders = {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            };

            const basePlyrOptions = {
                controls: [
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
                muted: false,
                volume: 0.4,
                hideControls: false,
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
            setInitialVolume(0.4);
            attachFirstInteractionAudio();
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

            initShareMenu();
            initDownloadButton();

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
                        lowLatencyMode: true,
                        startLevel: 0,
                        backBufferLength: 30,
                        maxBufferLength: 20,
                        maxMaxBufferLength: 30,
                        capLevelToPlayerSize: true,
                        abrEwmaDefaultEstimate: 800000,
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
                setInitialVolume(0.4);
                playerElement.muted = false;
                const promise = playerElement.play();
                if (promise && typeof promise.catch === 'function') {
                    promise.catch(async () => {
                        try {
                            playerElement.muted = true;
                            await playerElement.play();
                            setStatus('Autoplay started muted by browser. Tap player to hear sound.');
                        } catch (_error) {
                            setStatus('Tap play to start. Default volume is 40%.');
                        }
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

            function setActionStatus(message) {
                if (!actionStatusEl) {
                    return;
                }
                actionStatusEl.textContent = message;
            }

            function initDownloadButton() {
                if (!downloadButton) {
                    return;
                }

                downloadButton.addEventListener('click', async () => {
                    if (downloadButton.disabled) {
                        return;
                    }

                    downloadButton.disabled = true;
                    downloadButton.textContent = 'Preparing...';
                    setActionStatus('');

                    try {
                        const response = await fetch(downloadEndpoint, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: postHeaders,
                        });

                        let payload = {};
                        try {
                            payload = await response.json();
                        } catch (_error) {
                            payload = {};
                        }

                        if (!response.ok || !payload.download_url) {
                            setActionStatus(payload.message || 'Download is not available for this movie.');
                            return;
                        }

                        setActionStatus('Download starting...');
                        window.location.assign(payload.download_url);
                    } catch (_error) {
                        setActionStatus('Failed to create download link. Check internet and try again.');
                    } finally {
                        downloadButton.disabled = false;
                        downloadButton.textContent = 'Download';
                    }
                });
            }

            function initShareMenu() {
                if (!shareToggle || !shareMenu) {
                    return;
                }

                const encodedUrl = encodeURIComponent(shareUrl);
                const encodedText = encodeURIComponent(`Watch ${shareTitle}`);
                const links = {
                    telegram: `https://t.me/share/url?url=${encodedUrl}&text=${encodedText}`,
                    whatsapp: `https://wa.me/?text=${encodedText}%20${encodedUrl}`,
                    x: `https://twitter.com/intent/tweet?text=${encodedText}&url=${encodedUrl}`,
                    facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`,
                };

                shareMenu.querySelectorAll('[data-share-network]').forEach((node) => {
                    const network = node.getAttribute('data-share-network');
                    const url = network ? links[network] : null;
                    if (url) {
                        node.setAttribute('href', url);
                    }
                });

                shareToggle.addEventListener('click', () => {
                    shareMenu.classList.toggle('hidden');
                });

                document.addEventListener('click', (event) => {
                    if (!shareMenu.contains(event.target) && !shareToggle.contains(event.target)) {
                        shareMenu.classList.add('hidden');
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        shareMenu.classList.add('hidden');
                    }
                });

                if (copyShareButton) {
                    copyShareButton.addEventListener('click', async () => {
                        const copied = await copyText(shareUrl);
                        setActionStatus(copied ? 'Link copied to clipboard.' : 'Copy failed. Copy the URL manually.');
                        shareMenu.classList.add('hidden');
                    });
                }
            }

            async function copyText(text) {
                if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                    try {
                        await navigator.clipboard.writeText(text);
                        return true;
                    } catch (_error) {
                        // Fallback below.
                    }
                }

                const temp = document.createElement('textarea');
                temp.value = text;
                temp.setAttribute('readonly', 'readonly');
                temp.style.position = 'absolute';
                temp.style.left = '-9999px';
                document.body.appendChild(temp);
                temp.select();
                const success = document.execCommand('copy');
                document.body.removeChild(temp);
                return success;
            }

            function setInitialVolume(volume) {
                const safeVolume = Math.min(1, Math.max(0, Number(volume) || 0.4));
                playerElement.volume = safeVolume;
            }

            function attachFirstInteractionAudio() {
                const enableAudio = () => {
                    playerElement.muted = false;
                    setInitialVolume(0.4);
                    cleanup();
                };

                const cleanup = () => {
                    playerElement.removeEventListener('pointerdown', enableAudio);
                    playerElement.removeEventListener('touchstart', enableAudio);
                    document.removeEventListener('keydown', enableAudio);
                };

                playerElement.addEventListener('pointerdown', enableAudio, { once: true });
                playerElement.addEventListener('touchstart', enableAudio, { once: true, passive: true });
                document.addEventListener('keydown', enableAudio, { once: true });
            }
        })();
    </script>
</x-layouts.stream>
