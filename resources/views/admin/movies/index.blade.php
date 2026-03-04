<x-layouts.admin :title="'Admin Movies - VJPrime'">

    <section class="mt-5 rounded-xl border border-white/10 bg-slate-900/70 p-4">
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-lg font-semibold">Movies & Series</h1>
            @can('manage-content')
                <a href="{{ route('admin.movies.create') }}" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white">Add Content</a>
            @endcan
        </div>

        <form method="GET" action="{{ route('admin.movies.index') }}" class="mb-4 grid gap-3 rounded-lg border border-white/10 bg-slate-950/40 p-3 sm:grid-cols-2 lg:grid-cols-6">
            <input
                type="text"
                name="search"
                value="{{ $filters['search'] ?? '' }}"
                placeholder="Search title, series, slug..."
                class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm sm:col-span-2 lg:col-span-2"
            >

            <select name="status" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Draft</option>
                <option value="published" @selected(($filters['status'] ?? '') === 'published')>Published</option>
            </select>

            <select name="type" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
                <option value="">All Types</option>
                <option value="movie" @selected(($filters['type'] ?? '') === 'movie')>Movie</option>
                <option value="series" @selected(($filters['type'] ?? '') === 'series')>Series Episode</option>
            </select>

            <select name="language_id" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
                <option value="">All Languages</option>
                @foreach ($languages as $language)
                    <option value="{{ $language->id }}" @selected((int) ($filters['language_id'] ?? 0) === $language->id)>{{ $language->name }}</option>
                @endforeach
            </select>

            <select name="vj_id" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
                <option value="">All VJs</option>
                @foreach ($vjs as $vj)
                    <option value="{{ $vj->id }}" @selected((int) ($filters['vj_id'] ?? 0) === $vj->id)>{{ $vj->name }}</option>
                @endforeach
            </select>

            <div class="flex gap-2 sm:col-span-2 lg:col-span-2">
                <select name="sort" class="flex-1 rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
                    <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Newest</option>
                    <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>Oldest</option>
                    <option value="title" @selected(($filters['sort'] ?? '') === 'title')>Title A-Z</option>
                </select>
                <button class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white">Apply</button>
                <a href="{{ route('admin.movies.index') }}" class="rounded-md border border-white/20 px-4 py-2 text-sm text-slate-200">Reset</a>
            </div>
        </form>

        <div class="space-y-2">
            @forelse ($movies as $movie)
                <div class="flex items-center justify-between rounded-md border border-white/10 bg-slate-950/60 px-3 py-2">
                    <div>
                        <p class="text-sm font-medium">{{ $movie->title }}</p>
                        <p class="text-xs text-slate-400">
                            {{ ucfirst($movie->content_type ?? 'movie') }} -
                            {{ $movie->language?->name }} -
                            {{ $movie->vj?->name }} -
                            {{ ucfirst($movie->status) }}
                            @if (($movie->content_type ?? 'movie') === 'series')
                                - S{{ $movie->season_number ?? 1 }}E{{ $movie->episode_number ?? 1 }}
                            @endif
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.movies.edit', $movie) }}" class="rounded-md border border-white/20 px-3 py-1 text-xs">Edit</a>
                        @can('delete-content')
                            <form action="{{ route('admin.movies.destroy', $movie) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-md border border-red-500/40 px-3 py-1 text-xs text-red-300">Delete</button>
                            </form>
                        @endcan
                    </div>
                </div>
            @empty
                <p class="rounded-md border border-white/10 bg-slate-950/50 px-3 py-4 text-sm text-slate-300">
                    No movies matched your filters.
                </p>
            @endforelse
        </div>

        <div class="mt-4">{{ $movies->links() }}</div>
    </section>
</x-layouts.admin>
