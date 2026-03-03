@props([
    'movie',
    'progressSeconds' => null,
])

@php
    $poster = $movie->poster_url ?: 'https://picsum.photos/seed/VJPrime-fallback/600/900';
    $preview = $movie->asset?->preview_clip_path;
@endphp

<article class="preview-card group relative w-44 shrink-0 sm:w-52">
    <a href="{{ route('movies.show', $movie->slug) }}" class="block overflow-hidden rounded-lg border border-white/10 bg-slate-900/70">
        <div class="relative aspect-[2/3] overflow-hidden">
            <img src="{{ $poster }}" alt="{{ $movie->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
            @if ($preview)
                <video
                    data-preview
                    data-src="{{ $preview }}"
                    muted
                    loop
                    playsinline
                    preload="none"
                    class="absolute inset-0 h-full w-full object-cover opacity-0 transition duration-300 group-hover:opacity-100"
                ></video>
            @endif
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent"></div>
            <div class="absolute bottom-2 left-2 right-2">
                <p class="line-clamp-1 text-xs font-semibold text-white">{{ $movie->title }}</p>
                <p class="text-[11px] text-slate-300">
                    {{ ucfirst($movie->content_type ?? 'movie') }} - {{ $movie->language?->name }} - {{ $movie->vj?->name }}
                </p>
                @if (($movie->content_type ?? 'movie') === 'series')
                    <p class="text-[11px] text-slate-300">S{{ $movie->season_number ?? 1 }}E{{ $movie->episode_number ?? 1 }}</p>
                @endif
            </div>
        </div>
    </a>

    @if ($progressSeconds !== null && $movie->duration_seconds > 0)
        @php
            $progress = min(($progressSeconds / $movie->duration_seconds) * 100, 100);
        @endphp
        <div class="mt-1 h-1 w-full overflow-hidden rounded-full bg-white/10">
            <div class="h-full bg-red-500" style="width: {{ $progress }}%"></div>
        </div>
    @endif

    <div class="mt-2 flex items-center justify-between text-[11px] text-slate-300">
        <span>{{ $movie->year }}</span>
        <span>{{ strtoupper($movie->age_rating ?? 'PG') }}</span>
    </div>
</article>

