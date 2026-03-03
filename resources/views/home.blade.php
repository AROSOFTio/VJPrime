<x-layouts.stream :title="'VJPrime'" :wallpaper-posters="$wallpaperPosters">
    @php
        $hero = $featured->first() ?? $trending->first();
    @endphp

    @if ($hero)
        <section class="mb-8 overflow-hidden rounded-2xl border border-white/10 bg-slate-900/60">
            <div class="relative p-6 sm:p-10">
                <img src="{{ $hero->backdrop_url ?: $hero->poster_url }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-25">
                <div class="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-950/80 to-transparent"></div>
                <div class="relative max-w-2xl space-y-4">
                    <p class="text-xs uppercase tracking-[0.25em] text-red-400">Featured</p>
                    <h1 class="text-3xl font-semibold sm:text-4xl">{{ $hero->title }}</h1>
                    <p class="text-sm text-slate-200">{{ $hero->description }}</p>
                    <div class="flex gap-3">
                        <a href="{{ route('movies.show', $hero->slug) }}" class="rounded-md bg-white px-4 py-2 text-sm font-medium text-slate-900">Details</a>
                        @auth
                            <a href="{{ route('player.show', $hero->slug) }}" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white">Play</a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white">Login to Watch</a>
                        @endauth
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="space-y-8">
        <div>
            <h2 class="mb-3 text-lg font-semibold">Trending Now</h2>
            <div class="flex gap-3 overflow-x-auto pb-2">
                @foreach ($trending as $movie)
                    @include('partials.movie-card', ['movie' => $movie])
                @endforeach
            </div>
        </div>

        @auth
            @if ($continueWatching->isNotEmpty())
                <div>
                    <h2 class="mb-3 text-lg font-semibold">Continue Watching</h2>
                    <div class="flex gap-3 overflow-x-auto pb-2">
                        @foreach ($continueWatching as $progress)
                            @include('partials.movie-card', ['movie' => $progress->movie, 'progressSeconds' => $progress->last_position_seconds])
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($yourList->isNotEmpty())
                <div>
                    <h2 class="mb-3 text-lg font-semibold">Your List</h2>
                    <div class="flex gap-3 overflow-x-auto pb-2">
                        @foreach ($yourList as $movie)
                            @include('partials.movie-card', ['movie' => $movie])
                        @endforeach
                    </div>
                </div>
            @endif
        @endauth

        @foreach ($genreRows as $genre)
            <div>
                <h2 class="mb-3 text-lg font-semibold">{{ $genre->name }}</h2>
                <div class="flex gap-3 overflow-x-auto pb-2">
                    @foreach ($genre->movies as $movie)
                        @include('partials.movie-card', ['movie' => $movie])
                    @endforeach
                </div>
            </div>
        @endforeach
    </section>
</x-layouts.stream>

