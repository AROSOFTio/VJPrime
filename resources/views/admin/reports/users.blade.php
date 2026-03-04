<x-layouts.admin :title="'User Reports - VJPrime'">

    <section class="mt-5 rounded-xl border border-white/10 bg-slate-900/70 p-4">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold">User Reports</h1>
                <p class="text-xs text-slate-400">
                    {{ $periodLabel }}: {{ $from->format('Y-m-d H:i') }} to {{ $to->format('Y-m-d H:i') }}
                </p>
            </div>
            <a href="{{ route('admin.reports.export', ['section' => 'users'] + request()->query()) }}" class="rounded-md border border-violet-500/40 px-3 py-2 text-xs text-violet-200">Export CSV</a>
        </div>

        <form method="GET" action="{{ route('admin.reports.users') }}" class="mb-4 grid gap-3 rounded-lg border border-white/10 bg-slate-950/40 p-3 sm:grid-cols-2 lg:grid-cols-5">
            <select name="period" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
                @foreach ($periodOptions as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['period'] ?? 'weekly') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
            <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
            <select name="role" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
                <option value="">All Roles</option>
                @foreach ($roleOptions as $role)
                    <option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ $roleLabels[$role] ?? ucfirst(str_replace('_', ' ', $role)) }}</option>
                @endforeach
            </select>
            <select name="subscription_status" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
                <option value="">All Subscriptions</option>
                <option value="free" @selected(($filters['subscription_status'] ?? '') === 'free')>Free</option>
                <option value="premium" @selected(($filters['subscription_status'] ?? '') === 'premium')>Premium</option>
            </select>
            <select name="activity" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
                <option value="any" @selected(($filters['activity'] ?? 'any') === 'any')>Any Activity</option>
                <option value="streamed" @selected(($filters['activity'] ?? '') === 'streamed')>Streamed</option>
                <option value="downloaded" @selected(($filters['activity'] ?? '') === 'downloaded')>Downloaded</option>
                <option value="both" @selected(($filters['activity'] ?? '') === 'both')>Streamed + Downloaded</option>
            </select>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search user..." class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
            <select name="sort" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
                <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Newest</option>
                <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>Oldest</option>
                <option value="name" @selected(($filters['sort'] ?? '') === 'name')>Name</option>
                <option value="streams" @selected(($filters['sort'] ?? '') === 'streams')>Most Streams</option>
                <option value="downloads" @selected(($filters['sort'] ?? '') === 'downloads')>Most Downloads</option>
                <option value="watch" @selected(($filters['sort'] ?? '') === 'watch')>Most Watch Time</option>
            </select>
            <div class="flex gap-2">
                <button class="rounded-md bg-white/10 px-4 py-2 text-sm text-slate-100 hover:bg-white/20">Run</button>
                <a href="{{ route('admin.reports.users') }}" class="rounded-md border border-white/20 px-4 py-2 text-sm text-slate-200">Reset</a>
            </div>
        </form>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-lg border border-violet-500/30 bg-violet-500/10 p-3">
                <p class="text-xs uppercase tracking-wide text-violet-200">New Users</p>
                <p class="mt-1 text-xl font-semibold text-violet-100">{{ number_format($metrics['new_users']) }}</p>
            </div>
            <div class="rounded-lg border border-violet-500/30 bg-violet-500/10 p-3">
                <p class="text-xs uppercase tracking-wide text-violet-200">Active Users</p>
                <p class="mt-1 text-xl font-semibold text-violet-100">{{ number_format($metrics['active_users']) }}</p>
            </div>
            <div class="rounded-lg border border-sky-500/30 bg-sky-500/10 p-3">
                <p class="text-xs uppercase tracking-wide text-sky-200">Streamed Users</p>
                <p class="mt-1 text-xl font-semibold text-sky-100">{{ number_format($metrics['streamed_users']) }}</p>
            </div>
            <div class="rounded-lg border border-sky-500/30 bg-sky-500/10 p-3">
                <p class="text-xs uppercase tracking-wide text-sky-200">Downloaded Users</p>
                <p class="mt-1 text-xl font-semibold text-sky-100">{{ number_format($metrics['downloaded_users']) }}</p>
            </div>
            <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-3">
                <p class="text-xs uppercase tracking-wide text-emerald-200">Current Premium Users</p>
                <p class="mt-1 text-xl font-semibold text-emerald-100">{{ number_format($metrics['premium_users']) }}</p>
            </div>
        </div>
    </section>

    <section class="mt-4 rounded-xl border border-white/10 bg-slate-900/70 p-4">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-200">User Activity</h2>
        <div class="space-y-2">
            @forelse ($users as $user)
                <div class="rounded-md border border-white/10 bg-slate-950/60 px-3 py-2">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm">{{ $user->name }}</p>
                        <span class="rounded bg-white/10 px-1.5 py-0.5 text-[11px]">{{ $user->roleLabel() }}</span>
                    </div>
                    <p class="text-xs text-slate-300">
                        {{ $user->email ?: '-' }} |
                        {{ strtoupper($user->subscription_status) }} |
                        Streams: {{ number_format($user->streams_count ?? 0) }} |
                        Downloads: {{ number_format($user->downloads_count ?? 0) }}
                    </p>
                    <p class="text-xs text-slate-400">
                        Watch Seconds: {{ number_format((int) ($user->watch_seconds_sum ?? 0)) }} |
                        Joined: {{ optional($user->created_at)->format('Y-m-d H:i') }}
                    </p>
                </div>
            @empty
                <p class="text-sm text-slate-400">No users found for this report.</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $users->links() }}</div>
    </section>
</x-layouts.admin>
