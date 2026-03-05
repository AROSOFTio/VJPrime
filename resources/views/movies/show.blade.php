<x-layouts.stream :title="$movie->title . ' - VJPrime'" :wallpaper-posters="[$movie->backdrop_url, $movie->poster_url]">
    @php
        $posterFallback = asset('images/vjprime-poster-fallback.svg');
        $posterSrc = $movie->poster_url ?: $posterFallback;
    @endphp
    <section class="grid gap-6 lg:grid-cols-[280px,1fr]">
        <div>
            <img
                src="{{ $posterSrc }}"
                alt="{{ $movie->title }}"
                loading="lazy"
                decoding="async"
                onerror="this.onerror=null;this.src='{{ $posterFallback }}';"
                class="w-full rounded-xl border border-white/10 object-cover"
            >
        </div>

        <div class="space-y-4">
            <p class="text-xs uppercase tracking-[0.25em] text-red-400">
                {{ ucfirst($movie->content_type ?? 'movie') }} - {{ $movie->language->name }} - {{ $movie->vj->name }}
                @if (($movie->content_type ?? 'movie') === 'series')
                    - S{{ $movie->season_number ?? 1 }}E{{ $movie->episode_number ?? 1 }}
                @endif
            </p>
            <h1 class="text-3xl font-semibold">{{ $movie->title }}</h1>
            @if (($movie->content_type ?? 'movie') === 'series' && $movie->series_title)
                <p class="text-sm text-slate-300">Series: {{ $movie->series_title }}</p>
            @endif
            <p class="text-sm text-slate-300">{{ $movie->description }}</p>
            <p class="text-xs text-slate-400">
                {{ $movie->year }} - {{ gmdate('H:i:s', $movie->duration_seconds) }} - {{ strtoupper($movie->age_rating ?? 'PG') }}
            </p>
            <div class="flex flex-wrap gap-2">
                @foreach ($movie->genres as $genre)
                    <span class="rounded-full border border-white/20 px-3 py-1 text-xs">{{ $genre->name }}</span>
                @endforeach
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                @auth
                    <a href="{{ route('player.show', $movie->slug) }}" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white">Play</a>
                    <form action="{{ route('downloads.link', $movie) }}" method="POST">
                        @csrf
                        <button class="rounded-md bg-white/15 px-4 py-2 text-sm font-semibold text-white">Download</button>
                    </form>
                    @if ($isFavorite)
                        <form action="{{ route('favorites.destroy', $movie) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button class="rounded-md border border-white/20 px-4 py-2 text-sm text-white">Remove Favorite</button>
                        </form>
                    @else
                        <form action="{{ route('favorites.store', $movie) }}" method="POST">
                            @csrf
                            <button class="rounded-md border border-white/20 px-4 py-2 text-sm text-white">Add to Favorites</button>
                        </form>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white">Login to Watch</a>
                @endauth
            </div>

            <div class="rounded-md border border-white/10 bg-slate-900/70 p-4">
                <p class="text-sm text-slate-200">
                    Rating:
                    <span class="font-semibold">{{ number_format((float) $movie->reviews_avg_rating, 1) }}</span>
                    ({{ $movie->reviews_count }} reviews)
                </p>
                @if ($watchProgress)
                    <p class="mt-2 text-xs text-slate-400">
                        Continue from {{ gmdate('i:s', $watchProgress->last_position_seconds) }}.
                    </p>
                @endif
            </div>
        </div>
    </section>

    @auth
        <section class="mt-10 rounded-xl border border-white/10 bg-slate-900/70 p-5">
            <h2 class="mb-3 text-lg font-semibold">Your Review</h2>
            <form action="{{ route('reviews.store', $movie) }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="mb-1 block text-xs text-slate-300">Rating (1-5)</label>
                    <input type="number" name="rating" min="1" max="5" value="{{ old('rating', optional($movie->reviews->firstWhere('user_id', auth()->id()))->rating ?? 5) }}" class="w-24 rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
                </div>
                <textarea name="body" rows="3" maxlength="1000" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm" placeholder="Write a short review...">{{ old('body', optional($movie->reviews->firstWhere('user_id', auth()->id()))->body) }}</textarea>
                <button class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white">Save Review</button>
            </form>
        </section>
    @endauth

    <section class="mt-8">
        <h2 class="mb-3 text-lg font-semibold">Latest Reviews</h2>
        <div class="space-y-3">
            @forelse ($movie->reviews->sortByDesc('updated_at')->take(5) as $review)
                <div class="rounded-md border border-white/10 bg-slate-900/70 p-4">
                    <p class="text-sm font-semibold">{{ $review->user->name }} - {{ $review->rating }}/5</p>
                    <p class="mt-1 text-sm text-slate-300">{{ $review->body ?: 'No comment' }}</p>
                </div>
            @empty
                <p class="rounded-md border border-white/10 bg-slate-900/70 p-4 text-sm text-slate-300">No reviews yet.</p>
            @endforelse
        </div>
    </section>

    @if ($related->isNotEmpty())
        <section class="mt-10">
            <h2 class="mb-3 text-lg font-semibold">You May Also Like</h2>
            <div class="flex gap-3 overflow-x-auto pb-2">
                @foreach ($related as $item)
                    @include('partials.movie-card', ['movie' => $item])
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.stream>
