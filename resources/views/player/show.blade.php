<x-layouts.stream :title="$movie->title . ' - Player'" :wallpaper-posters="[$movie->backdrop_url, $movie->poster_url]">
    <section class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-red-400">{{ $movie->language->name }} · {{ $movie->vj->name }}</p>
                <h1 class="text-2xl font-semibold">{{ $movie->title }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <label for="quality-select" class="text-xs text-slate-300">Resolution</label>
                <select id="quality-select" class="rounded-md border border-white/10 bg-slate-900 px-3 py-1.5 text-sm">
                    <option value="-1">Auto</option>
                </select>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-xl border border-white/10 bg-black">
            <video id="player" controls playsinline class="aspect-video w-full bg-black"></video>
            <div id="playback-blocked" class="absolute inset-0 hidden items-center justify-center bg-slate-950/85 p-6 text-center">
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
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <script>
        (async () => {
            const player = document.getElementById('player');
            const qualitySelect = document.getElementById('quality-select');
            const blocked = document.getElementById('playback-blocked');
            const remainingEl = document.getElementById('remaining-seconds');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const qualityWrap = qualitySelect?.closest('div');

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

            const startResponse = await fetch('/api/playback/start', {
                method: 'POST',
                credentials: 'same-origin',
                headers: jsonHeaders,
                body: JSON.stringify({ movie_id: movieId }),
            });

            const startData = await startResponse.json();
            if (!startResponse.ok) {
                if (startResponse.status === 402) {
                    blockPlayback(startData.message || 'Daily free limit reached');
                    return;
                }
                alert(startData.message || 'Unable to start playback.');
                return;
            }

            viewId = startData.view_id;
            if (remainingEl && typeof startData.remaining_seconds === 'number') {
                remainingEl.textContent = startData.remaining_seconds;
            }

            initPlayer(startData.hls_url, startData.stream_type || 'hls');
            startHeartbeat();
            window.addEventListener('beforeunload', () => sendStop(false));
            player.addEventListener('ended', () => sendStop(true));

            function initPlayer(sourceUrl, streamType) {
                if (streamType === 'hls' && window.Hls && window.Hls.isSupported()) {
                    hlsInstance = new Hls();
                    hlsInstance.loadSource(sourceUrl);
                    hlsInstance.attachMedia(player);

                    hlsInstance.on(Hls.Events.MANIFEST_PARSED, () => {
                        player.play().catch(() => {});
                        populateQualities();
                    });

                    hlsInstance.on(Hls.Events.ERROR, (_event, data) => {
                        if (! data?.fatal) {
                            return;
                        }

                        hlsInstance?.destroy();
                        hlsInstance = null;
                        fallbackToDirect(sourceUrl);
                    });
                } else if (streamType === 'hls' && player.canPlayType('application/vnd.apple.mpegurl')) {
                    player.src = sourceUrl;
                    player.addEventListener('loadedmetadata', () => player.play().catch(() => {}));
                } else {
                    fallbackToDirect(sourceUrl);
                }
            }

            function fallbackToDirect(sourceUrl) {
                qualityWrap?.classList.add('hidden');
                player.src = sourceUrl;
                player.addEventListener('loadedmetadata', () => player.play().catch(() => {}), { once: true });
            }

            function populateQualities() {
                if (!hlsInstance) return;
                const seen = new Set();
                hlsInstance.levels.forEach((level, index) => {
                    if (seen.has(level.height)) return;
                    seen.add(level.height);
                    const option = document.createElement('option');
                    option.value = String(index);
                    option.textContent = `${level.height}p`;
                    qualitySelect.appendChild(option);
                });

                qualitySelect.addEventListener('change', (event) => {
                    hlsInstance.currentLevel = Number(event.target.value);
                });
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
            }
        })();
    </script>
</x-layouts.stream>
