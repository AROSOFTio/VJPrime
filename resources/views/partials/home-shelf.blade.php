@props([
    'title',
    'movies' => collect(),
    'moreHref' => null,
    'progressByMovieId' => [],
    'auto' => false,
])

@php
    $movieCollection = $movies instanceof \Illuminate\Support\Collection ? $movies : collect($movies);
@endphp

@if ($movieCollection->isNotEmpty())
    <section data-shelf class="relative">
        <div class="mb-2 flex items-center justify-between">
            <h2 class="text-xl font-semibold tracking-tight text-white sm:text-2xl">{{ $title }}</h2>
            @if ($moreHref)
                <a href="{{ $moreHref }}" class="text-sm font-medium text-slate-300 transition hover:text-white">
                    More
                </a>
            @endif
        </div>

        <div class="relative">
            <button
                type="button"
                data-shelf-prev
                aria-label="Scroll left"
                class="absolute left-1 top-1/2 z-10 hidden -translate-y-1/2 rounded-full border border-white/30 bg-slate-900/70 p-2 text-white shadow-lg backdrop-blur transition hover:bg-slate-800/80 md:inline-flex"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M15 5l-7 7 7 7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>

            <div
                data-shelf-track
                @if ($auto) data-shelf-auto="1" @endif
                class="flex gap-2.5 overflow-x-auto pb-2 pt-1 scrollbar-none scroll-smooth snap-x snap-mandatory"
            >
                @foreach ($movieCollection as $movie)
                    <div class="snap-start">
                        @include('partials.movie-card', [
                            'movie' => $movie,
                            'compact' => true,
                            'progressSeconds' => $progressByMovieId[$movie->id] ?? null,
                        ])
                    </div>
                @endforeach
            </div>

            <button
                type="button"
                data-shelf-next
                aria-label="Scroll right"
                class="absolute right-1 top-1/2 z-10 hidden -translate-y-1/2 rounded-full border border-white/30 bg-slate-900/70 p-2 text-white shadow-lg backdrop-blur transition hover:bg-slate-800/80 md:inline-flex"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
        </div>
    </section>
@endif
