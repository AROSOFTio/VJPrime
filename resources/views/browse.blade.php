<x-layouts.stream :title="'Browse - VJPrime'" :wallpaper-posters="$movies->pluck('poster_url')">
    <section class="mb-6 rounded-xl border border-white/10 bg-slate-900/70 p-4">
        <form method="GET" action="{{ route('browse') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <input
                type="text"
                name="search"
                value="{{ $filters['search'] ?? '' }}"
                placeholder="Search title..."
                class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm text-white placeholder:text-slate-400"
            >

            <select name="genre" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm text-white">
                <option value="">All Genres</option>
                @foreach ($genres as $genre)
                    <option value="{{ $genre->slug }}" @selected(($filters['genre'] ?? '') === $genre->slug)>{{ $genre->name }}</option>
                @endforeach
            </select>

            <select name="language" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm text-white">
                <option value="">All Languages</option>
                @foreach ($languages as $language)
                    <option value="{{ $language->code }}" @selected(($filters['language'] ?? '') === $language->code)>{{ $language->name }}</option>
                @endforeach
            </select>

            <select name="vj" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm text-white">
                <option value="">All VJs</option>
                @foreach ($vjs as $vj)
                    <option value="{{ $vj->slug }}" @selected(($filters['vj'] ?? '') === $vj->slug)>{{ $vj->name }}</option>
                @endforeach
            </select>

            <select name="type" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm text-white">
                <option value="">All Types</option>
                <option value="movie" @selected(($filters['type'] ?? '') === 'movie')>Movies</option>
                <option value="series" @selected(($filters['type'] ?? '') === 'series')>Series</option>
            </select>

            <div class="flex gap-2">
                <select name="sort" class="flex-1 rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm text-white">
                    <option value="trending" @selected(($filters['sort'] ?? 'trending') === 'trending')>Trending</option>
                    <option value="new" @selected(($filters['sort'] ?? '') === 'new')>Newest</option>
                    <option value="rating" @selected(($filters['sort'] ?? '') === 'rating')>Rating</option>
                </select>
                <button class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white">Apply</button>
            </div>
        </form>
    </section>

    <section>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6">
            @forelse ($movies as $movie)
                @include('partials.movie-card', ['movie' => $movie])
            @empty
                <p class="col-span-full rounded-md border border-white/10 bg-slate-900/60 p-4 text-sm text-slate-300">
                    No movies matched your filters.
                </p>
            @endforelse
        </div>
    </section>

    <div class="mt-6">
        {{ $movies->links() }}
    </div>
</x-layouts.stream>

