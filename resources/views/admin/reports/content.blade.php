<x-layouts.admin :title="'Content Reports - VJPrime'">

    <section class="mt-5 rounded-xl border border-white/10 bg-slate-900/70 p-4">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold">Content Reports</h1>
                <p class="text-xs text-slate-400">
                    {{ $periodLabel }}: {{ $from->format('Y-m-d H:i') }} to {{ $to->format('Y-m-d H:i') }}
                </p>
            </div>
            <a href="{{ route('admin.reports.export', ['section' => 'content'] + request()->query()) }}" class="rounded-md border border-amber-500/40 px-3 py-2 text-xs text-amber-200">Export CSV</a>
        </div>

        <form method="GET" action="{{ route('admin.reports.content') }}" class="mb-4 grid gap-3 rounded-lg border border-white/10 bg-slate-950/40 p-3 sm:grid-cols-2 lg:grid-cols-5">
            <select name="period" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
                @foreach ($periodOptions as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['period'] ?? 'weekly') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
            <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
            <select name="status" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="published" @selected(($filters['status'] ?? '') === 'published')>Published</option>
                <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Draft</option>
            </select>
            <select name="content_type" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
                <option value="">All Types</option>
                <option value="movie" @selected(($filters['content_type'] ?? '') === 'movie')>Movie</option>
                <option value="series" @selected(($filters['content_type'] ?? '') === 'series')>Series</option>
            </select>
            <select name="creator_id" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
                <option value="">All Creators</option>
                @foreach ($creators as $creator)
                    <option value="{{ $creator->id }}" @selected((int) ($filters['creator_id'] ?? 0) === $creator->id)>{{ $creator->name }} ({{ $creator->roleLabel() }})</option>
                @endforeach
            </select>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search title, slug..." class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
            <select name="sort" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
                <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Newest</option>
                <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>Oldest</option>
                <option value="title" @selected(($filters['sort'] ?? '') === 'title')>Title</option>
                <option value="streams" @selected(($filters['sort'] ?? '') === 'streams')>Most Streamed</option>
                <option value="downloads" @selected(($filters['sort'] ?? '') === 'downloads')>Most Downloaded</option>
            </select>
            <div class="flex gap-2">
                <button class="rounded-md bg-white/10 px-4 py-2 text-sm text-slate-100 hover:bg-white/20">Run</button>
                <a href="{{ route('admin.reports.content') }}" class="rounded-md border border-white/20 px-4 py-2 text-sm text-slate-200">Reset</a>
            </div>
        </form>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-lg border border-amber-500/30 bg-amber-500/10 p-3">
                <p class="text-xs uppercase tracking-wide text-amber-200">Content Count</p>
                <p class="mt-1 text-xl font-semibold text-amber-100">{{ number_format($metrics['content_count']) }}</p>
            </div>
            <div class="rounded-lg border border-amber-500/30 bg-amber-500/10 p-3">
                <p class="text-xs uppercase tracking-wide text-amber-200">Movies</p>
                <p class="mt-1 text-xl font-semibold text-amber-100">{{ number_format($metrics['movie_count']) }}</p>
            </div>
            <div class="rounded-lg border border-amber-500/30 bg-amber-500/10 p-3">
                <p class="text-xs uppercase tracking-wide text-amber-200">Series Episodes</p>
                <p class="mt-1 text-xl font-semibold text-amber-100">{{ number_format($metrics['series_count']) }}</p>
            </div>
            <div class="rounded-lg border border-sky-500/30 bg-sky-500/10 p-3">
                <p class="text-xs uppercase tracking-wide text-sky-200">Streams</p>
                <p class="mt-1 text-xl font-semibold text-sky-100">{{ number_format($metrics['stream_count']) }}</p>
            </div>
            <div class="rounded-lg border border-sky-500/30 bg-sky-500/10 p-3">
                <p class="text-xs uppercase tracking-wide text-sky-200">Downloads</p>
                <p class="mt-1 text-xl font-semibold text-sky-100">{{ number_format($metrics['download_count']) }}</p>
            </div>
        </div>
    </section>

    <section class="mt-4 grid gap-4 xl:grid-cols-2">
        <div class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-200">Top Streamed</h2>
            <div class="space-y-2">
                @forelse ($topStreamed as $movie)
                    <div class="flex items-center justify-between rounded-md border border-white/10 bg-slate-950/60 px-3 py-2">
                        <p class="text-sm">{{ $movie->title }}</p>
                        <p class="text-xs text-slate-300">{{ number_format($movie->streams_count) }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No streaming records found.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-200">Top Downloaded</h2>
            <div class="space-y-2">
                @forelse ($topDownloaded as $movie)
                    <div class="flex items-center justify-between rounded-md border border-white/10 bg-slate-950/60 px-3 py-2">
                        <p class="text-sm">{{ $movie->title }}</p>
                        <p class="text-xs text-slate-300">{{ number_format($movie->downloads_count) }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No download records found.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="mt-4 rounded-xl border border-white/10 bg-slate-900/70 p-4">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-200">Content List</h2>
        <div class="space-y-2">
            @forelse ($movies as $movie)
                <div class="rounded-md border border-white/10 bg-slate-950/60 px-3 py-2">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm">{{ $movie->title }}</p>
                        <span class="rounded bg-white/10 px-1.5 py-0.5 text-[11px]">{{ strtoupper($movie->status) }}</span>
                    </div>
                    <p class="text-xs text-slate-300">
                        {{ ucfirst($movie->content_type ?? 'movie') }} |
                        Creator: {{ $movie->creator?->name ?? 'Unknown' }} |
                        Streams: {{ number_format($movie->streams_count) }} |
                        Downloads: {{ number_format($movie->downloads_count) }}
                    </p>
                    <p class="text-xs text-slate-400">
                        Created: {{ optional($movie->created_at)->format('Y-m-d H:i') }} |
                        Published: {{ optional($movie->published_at)->format('Y-m-d H:i') ?? '-' }}
                    </p>
                </div>
            @empty
                <p class="text-sm text-slate-400">No content found.</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $movies->links() }}</div>
    </section>

    <section class="mt-4 rounded-xl border border-white/10 bg-slate-900/70 p-4">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-200">Current Published By Creator</h2>
        <div class="space-y-2">
            @forelse ($currentPublishedByCreator as $creator)
                <div class="flex items-center justify-between rounded-md border border-white/10 bg-slate-950/60 px-3 py-2">
                    <div>
                        <p class="text-sm">{{ $creator->name }}</p>
                        <p class="text-xs text-slate-400">{{ $creator->roleLabel() }}</p>
                    </div>
                    <p class="text-xs text-slate-300">{{ number_format($creator->published_count) }} published</p>
                </div>
            @empty
                <p class="text-sm text-slate-400">No creator content stats available.</p>
            @endforelse
        </div>
    </section>
</x-layouts.admin>
