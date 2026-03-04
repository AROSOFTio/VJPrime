<x-layouts.admin :title="'Reports - VJPrime'">

    <section class="mt-5 rounded-xl border border-white/10 bg-slate-900/70 p-4">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold">Reports & Analytics</h1>
                <p class="text-xs text-slate-400">
                    Active range: {{ $periodLabel }} ({{ $from->format('Y-m-d H:i') }} to {{ $to->format('Y-m-d H:i') }})
                </p>
            </div>

            <form method="GET" action="{{ route('admin.reports.index') }}" class="flex items-center gap-2">
                <select name="period" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
                    @foreach ($periodOptions as $key => $label)
                        <option value="{{ $key }}" @selected($period === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="rounded-md bg-white/10 px-4 py-2 text-sm text-slate-100 hover:bg-white/20">Apply</button>
            </form>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-3">
                <p class="text-xs uppercase tracking-wide text-emerald-200">Sales Amount</p>
                <p class="mt-1 text-2xl font-semibold text-emerald-100">{{ number_format($metrics['sales_amount'], 2) }}</p>
            </div>
            <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-3">
                <p class="text-xs uppercase tracking-wide text-emerald-200">Paid Sales</p>
                <p class="mt-1 text-2xl font-semibold text-emerald-100">{{ number_format($metrics['sales_count']) }}</p>
            </div>
            <div class="rounded-lg border border-sky-500/30 bg-sky-500/10 p-3">
                <p class="text-xs uppercase tracking-wide text-sky-200">Streams</p>
                <p class="mt-1 text-2xl font-semibold text-sky-100">{{ number_format($metrics['streams_count']) }}</p>
            </div>
            <div class="rounded-lg border border-sky-500/30 bg-sky-500/10 p-3">
                <p class="text-xs uppercase tracking-wide text-sky-200">Downloads</p>
                <p class="mt-1 text-2xl font-semibold text-sky-100">{{ number_format($metrics['downloads_count']) }}</p>
            </div>
            <div class="rounded-lg border border-violet-500/30 bg-violet-500/10 p-3">
                <p class="text-xs uppercase tracking-wide text-violet-200">Watch Hours</p>
                <p class="mt-1 text-2xl font-semibold text-violet-100">{{ number_format($metrics['watch_seconds'] / 3600, 1) }}</p>
            </div>
            <div class="rounded-lg border border-violet-500/30 bg-violet-500/10 p-3">
                <p class="text-xs uppercase tracking-wide text-violet-200">Active Stream Users</p>
                <p class="mt-1 text-2xl font-semibold text-violet-100">{{ number_format($metrics['active_stream_users']) }}</p>
            </div>
            <div class="rounded-lg border border-amber-500/30 bg-amber-500/10 p-3">
                <p class="text-xs uppercase tracking-wide text-amber-200">Published Movies</p>
                <p class="mt-1 text-2xl font-semibold text-amber-100">{{ number_format($metrics['published_movies_count']) }}</p>
            </div>
            <div class="rounded-lg border border-amber-500/30 bg-amber-500/10 p-3">
                <p class="text-xs uppercase tracking-wide text-amber-200">Published Series Episodes</p>
                <p class="mt-1 text-2xl font-semibold text-amber-100">{{ number_format($metrics['published_series_count']) }}</p>
            </div>
        </div>
    </section>

    <section class="mt-4 rounded-xl border border-white/10 bg-slate-900/70 p-4">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-200">Periodic Reports</h2>
            <div class="flex flex-wrap gap-2 text-xs">
                <a href="{{ route('admin.reports.export', ['kind' => 'sales', 'period' => $period]) }}" class="rounded-md border border-emerald-500/40 px-3 py-1 text-emerald-200">Export Sales CSV</a>
                <a href="{{ route('admin.reports.export', ['kind' => 'downloads', 'period' => $period]) }}" class="rounded-md border border-sky-500/40 px-3 py-1 text-sky-200">Export Downloads CSV</a>
                <a href="{{ route('admin.reports.export', ['kind' => 'usage', 'period' => $period]) }}" class="rounded-md border border-violet-500/40 px-3 py-1 text-violet-200">Export Usage CSV</a>
                <a href="{{ route('admin.reports.export', ['kind' => 'content', 'period' => $period]) }}" class="rounded-md border border-amber-500/40 px-3 py-1 text-amber-200">Export Content CSV</a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="text-xs uppercase tracking-wide text-slate-400">
                    <tr>
                        <th class="px-2 py-2">Period</th>
                        <th class="px-2 py-2">Range</th>
                        <th class="px-2 py-2">Sales Amount</th>
                        <th class="px-2 py-2">Paid Sales</th>
                        <th class="px-2 py-2">Downloads</th>
                        <th class="px-2 py-2">Streams</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($periodicSnapshots as $snapshot)
                        <tr class="border-t border-white/10 text-slate-200">
                            <td class="px-2 py-2">{{ $snapshot['label'] }}</td>
                            <td class="px-2 py-2 text-xs text-slate-400">
                                {{ $snapshot['from']->format('Y-m-d') }} to {{ $snapshot['to']->format('Y-m-d') }}
                            </td>
                            <td class="px-2 py-2">{{ number_format($snapshot['sales_amount'], 2) }}</td>
                            <td class="px-2 py-2">{{ number_format($snapshot['sales_count']) }}</td>
                            <td class="px-2 py-2">{{ number_format($snapshot['downloads_count']) }}</td>
                            <td class="px-2 py-2">{{ number_format($snapshot['streams_count']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-4 grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-200">Most Streamed</h2>
            <div class="space-y-2">
                @forelse ($topStreamed as $movie)
                    <div class="flex items-center justify-between rounded-md border border-white/10 bg-slate-950/60 px-3 py-2">
                        <p class="text-sm">{{ $movie->title }}</p>
                        <p class="text-xs text-slate-300">{{ number_format($movie->streams_count) }} streams</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No streaming records in this range.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-200">Most Downloaded</h2>
            <div class="space-y-2">
                @forelse ($topDownloaded as $movie)
                    <div class="flex items-center justify-between rounded-md border border-white/10 bg-slate-950/60 px-3 py-2">
                        <p class="text-sm">{{ $movie->title }}</p>
                        <p class="text-xs text-slate-300">{{ number_format($movie->downloads_count) }} downloads</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No download records in this range.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="mt-4 grid gap-4 xl:grid-cols-2">
        <div class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-200">Current Published Content (By Creator)</h2>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[600px] text-left text-sm">
                    <thead class="text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-2 py-2">Title</th>
                            <th class="px-2 py-2">Type</th>
                            <th class="px-2 py-2">Creator</th>
                            <th class="px-2 py-2">Published</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($latestPublished as $movie)
                            <tr class="border-t border-white/10 text-slate-200">
                                <td class="px-2 py-2">{{ $movie->title }}</td>
                                <td class="px-2 py-2">{{ ucfirst($movie->content_type ?? 'movie') }}</td>
                                <td class="px-2 py-2">{{ $movie->creator?->name ?? 'Unknown' }}</td>
                                <td class="px-2 py-2 text-xs text-slate-400">{{ optional($movie->published_at)->format('Y-m-d H:i') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-2 py-3 text-sm text-slate-400">No published content found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-200">Top Content Creators</h2>
            <div class="space-y-2">
                @forelse ($publishedByCreator as $creator)
                    <div class="flex items-center justify-between rounded-md border border-white/10 bg-slate-950/60 px-3 py-2">
                        <div>
                            <p class="text-sm">{{ $creator->name }}</p>
                            <p class="text-xs text-slate-400">{{ $creator->roleLabel() }}</p>
                        </div>
                        <p class="text-xs text-slate-300">{{ number_format($creator->published_count) }} published</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No creator publishing data yet.</p>
                @endforelse
            </div>
        </div>
    </section>
</x-layouts.admin>
