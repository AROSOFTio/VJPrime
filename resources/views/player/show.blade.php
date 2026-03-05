<x-layouts.stream :title="$movie->title . ' - Player'" :wallpaper-posters="[$movie->backdrop_url, $movie->poster_url]">
    <section class="space-y-4">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-red-400">{{ $movie->language->name }} &middot; {{ $movie->vj->name }}</p>
            <h1 class="text-2xl font-semibold">{{ $movie->title }}</h1>
        </div>

        <div class="relative overflow-hidden rounded-xl border border-white/10 bg-black">
            <video id="player" controls playsinline preload="metadata" class="aspect-video w-full bg-black"></video>
            <div id="playback-blocked" class="absolute inset-0 hidden items-center justify-center bg-slate-950/85 p-6 text-center">
                <div>
                    <p class="text-lg font-semibold text-white">Daily free limit reached</p>
                    <p class="mt-2 text-sm text-slate-300">You have used your 30 free minutes for this 24-hour window.</p>
                    <a href="{{ route('account.index') }}" class="mt-4 inline-block rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white">Upgrade Prompt</a>
                </div>
            </div>
        </div>

        <div class="rounded-md border border-white/10 bg-slate-900/70 p-3">
            <div class="flex flex-wrap items-center gap-2">
                <button id="control-play-toggle" type="button" class="rounded-md border border-white/10 bg-slate-800 px-3 py-1.5 text-sm hover:bg-slate-700">Play</button>
                <button id="control-rewind" type="button" class="rounded-md border border-white/10 bg-slate-800 px-3 py-1.5 text-sm hover:bg-slate-700">-10s</button>
                <button id="control-forward" type="button" class="rounded-md border border-white/10 bg-slate-800 px-3 py-1.5 text-sm hover:bg-slate-700">+10s</button>
                <button id="control-fullscreen" type="button" class="rounded-md border border-white/10 bg-slate-800 px-3 py-1.5 text-sm hover:bg-slate-700">Fullscreen</button>

                <div class="ms-auto flex flex-wrap items-center gap-2">
                    <label for="speed-select" class="text-xs text-slate-300">Speed</label>
                    <select id="speed-select" class="rounded-md border border-white/10 bg-slate-900 px-3 py-1.5 text-sm">
                        <option value="0.5">0.5x</option>
                        <option value="0.75">0.75x</option>
                        <option value="1" selected>1x</option>
                        <option value="1.25">1.25x</option>
                        <option value="1.5">1.5x</option>
                        <option value="2">2x</option>
                    </select>

                    <div id="quality-wrap" class="flex items-center gap-2">
                        <label for="quality-select" class="text-xs text-slate-300">Resolution</label>
                        <select id="quality-select" class="rounded-md border border-white/10 bg-slate-900 px-3 py-1.5 text-sm">
                            <option value="-1">Auto</option>
                        </select>
                    </div>
                </div>
            </div>
            <p id="player-status" class="mt-2 text-xs text-slate-400"></p>
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
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <script>
        (async () => {
            const player = document.getElementById('player');
            const playToggleBtn = document.getElementById('control-play-toggle');
            const rewindBtn = document.getElementById('control-rewind');
            const forwardBtn = document.getElementById('control-forward');
            const fullscreenBtn = document.getElementById('control-fullscreen');
            const speedSelect = document.getElementById('speed-select');
            const qualitySelect = document.getElementById('quality-select');
            const qualityWrap = document.getElementById('quality-wrap');
            const playerStatus = document.getElementById('player-status');
            const blocked = document.getElementById('playback-blocked');
            const remainingEl = document.getElementById('remaining-seconds');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

            let viewId = null;
            let isBlocked = false;
            let hlsInstance = null;
            let lastHeartbeatPosition = 0;
            const movieId = {{ $movie->id }};

            const jsonHeaders = {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
            };

            let startResponse;
            let startData = {};
            try {
                startResponse = await fetch('/api/playback/start', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: jsonHeaders,
                    body: JSON.stringify({ movie_id: movieId }),
                });

                const startPayload = await startResponse.text();
                startData = startPayload ? JSON.parse(startPayload) : {};
            } catch (_error) {
                setStatus('Unable to load playback data. Check network and try again.');
                return;
            }

            if (!startResponse || !startResponse.ok) {
                if (startResponse?.status === 402) {
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

            bindControls();
            initPlayer(startData.hls_url, startData.stream_type || 'hls');
            startHeartbeat();
            window.addEventListener('beforeunload', () => sendStop(false));
            player.addEventListener('ended', () => sendStop(true));
            player.addEventListener('play', syncPlayState);
            player.addEventListener('pause', syncPlayState);
            syncPlayState();

            function initPlayer(sourceUrl, streamType) {
                if (streamType === 'hls' && window.Hls && window.Hls.isSupported()) {
                    hlsInstance = new Hls();
                    hlsInstance.loadSource(sourceUrl);
                    hlsInstance.attachMedia(player);

                    hlsInstance.on(Hls.Events.MANIFEST_PARSED, () => {
                        setStatus('Adaptive streaming enabled. Choose Auto or manual resolution below.');
                        player.play().catch(() => {});
                        populateQualities();
                    });

                    hlsInstance.on(Hls.Events.ERROR, (_event, data) => {
                        if (!data?.fatal) {
                            return;
                        }

                        hlsInstance?.destroy();
                        hlsInstance = null;
                        setStatus('Stream playback error. Try reloading this page.');
                    });
                } else if (streamType === 'hls' && player.canPlayType('application/vnd.apple.mpegurl')) {
                    player.src = sourceUrl;
                    player.addEventListener('loadedmetadata', () => player.play().catch(() => {}), { once: true });
                    setQualityLocked('Auto');
                    setStatus('Native HLS playback enabled.');
                } else {
                    fallbackToDirect(sourceUrl);
                }
            }

            function fallbackToDirect(sourceUrl) {
                setQualityLocked('Source');
                player.src = sourceUrl;
                setStatus('Playing source file stream.');
                player.addEventListener('loadedmetadata', () => player.play().catch(() => {}), { once: true });
            }

            function populateQualities() {
                if (!hlsInstance || !qualitySelect) {
                    return;
                }

                qualitySelect.innerHTML = '';
                const autoOption = document.createElement('option');
                autoOption.value = '-1';
                autoOption.textContent = 'Auto';
                qualitySelect.appendChild(autoOption);

                const levelsByHeight = new Map();
                hlsInstance.levels.forEach((level, index) => {
                    const height = Number(level.height || 0);
                    if (!height || levelsByHeight.has(height)) {
                        return;
                    }
                    levelsByHeight.set(height, index);
                });

                Array.from(levelsByHeight.entries())
                    .sort((a, b) => a[0] - b[0])
                    .forEach(([height, index]) => {
                        const option = document.createElement('option');
                        option.value = String(index);
                        option.textContent = `${height}p`;
                        qualitySelect.appendChild(option);
                    });

                qualitySelect.disabled = false;
                qualitySelect.value = '-1';
                qualitySelect.onchange = (event) => {
                    if (!hlsInstance) {
                        return;
                    }
                    hlsInstance.currentLevel = Number(event.target.value);
                };
            }

            function bindControls() {
                if (playToggleBtn) {
                    playToggleBtn.addEventListener('click', async () => {
                        if (player.paused) {
                            await player.play().catch(() => {});
                        } else {
                            player.pause();
                        }
                        syncPlayState();
                    });
                }

                if (rewindBtn) {
                    rewindBtn.addEventListener('click', () => {
                        player.currentTime = Math.max(0, (player.currentTime || 0) - 10);
                    });
                }

                if (forwardBtn) {
                    forwardBtn.addEventListener('click', () => {
                        const duration = Number.isFinite(player.duration) ? player.duration : Number.MAX_SAFE_INTEGER;
                        player.currentTime = Math.min(duration, (player.currentTime || 0) + 10);
                    });
                }

                if (speedSelect) {
                    speedSelect.addEventListener('change', (event) => {
                        player.playbackRate = Number(event.target.value || 1);
                    });
                }

                if (fullscreenBtn) {
                    fullscreenBtn.addEventListener('click', async () => {
                        const wrap = player.closest('.relative');
                        if (!document.fullscreenElement && wrap?.requestFullscreen) {
                            await wrap.requestFullscreen().catch(() => {});
                            return;
                        }
                        if (document.fullscreenElement && document.exitFullscreen) {
                            await document.exitFullscreen().catch(() => {});
                        }
                    });
                }
            }

            function syncPlayState() {
                if (!playToggleBtn) {
                    return;
                }
                playToggleBtn.textContent = player.paused ? 'Play' : 'Pause';
            }

            function setQualityLocked(label) {
                if (!qualitySelect || !qualityWrap) {
                    return;
                }
                qualityWrap.classList.remove('hidden');
                qualitySelect.innerHTML = '';
                const option = document.createElement('option');
                option.value = '-1';
                option.textContent = label;
                qualitySelect.appendChild(option);
                qualitySelect.disabled = true;
            }

            function setStatus(message) {
                if (!playerStatus) {
                    return;
                }
                playerStatus.textContent = message;
            }

            function startHeartbeat() {
                setInterval(async () => {
                    if (isBlocked || player.paused || player.ended || !viewId) return;

                    const currentPosition = Math.floor(player.currentTime || 0);
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
                        last_position_seconds: Math.floor(player.currentTime || 0),
                        completed: completed,
                    }),
                });
            }

            function blockPlayback(message) {
                isBlocked = true;
                player.pause();
                blocked.classList.remove('hidden');
                blocked.classList.add('flex');
                blocked.querySelector('p')?.textContent = message;
                setStatus(message);
            }
        })();
    </script>
</x-layouts.stream>
