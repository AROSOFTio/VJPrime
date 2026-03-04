<x-layouts.admin :title="'Admin Dashboard - VJPrime'">

    <section class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Movies / Episodes</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($movieCount) }}</p>
        </div>
        <div class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Published Content</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($publishedMovieCount) }}</p>
        </div>
        <div class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Customers</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($userCount) }}</p>
        </div>
        <div class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Admins</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($adminCount) }}</p>
        </div>
        <div class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Content Team</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($contentTeamCount) }}</p>
        </div>
        <div class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Finance Managers</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($financeManagerCount) }}</p>
        </div>
    </section>

    <section class="mt-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4">
        <p class="text-xs uppercase tracking-wide text-emerald-200">Users Online (Last 5 Minutes)</p>
        <p class="mt-1 text-3xl font-semibold text-emerald-100">{{ number_format($onlineUsers) }}</p>
    </section>

    <section class="mt-4 flex flex-wrap gap-2 text-sm">
        @can('manage-content')
            <a href="{{ route('admin.movies.index') }}" class="rounded-md border border-white/20 px-3 py-2 text-slate-200 hover:bg-white/10">Manage Content</a>
        @endcan
        @can('manage-users')
            <a href="{{ route('admin.users.index') }}" class="rounded-md border border-white/20 px-3 py-2 text-slate-200 hover:bg-white/10">Manage Users</a>
        @endcan
        @can('view-reports')
            <a href="{{ route('admin.reports.index') }}" class="rounded-md border border-white/20 px-3 py-2 text-slate-200 hover:bg-white/10">Open Reports</a>
        @endcan
    </section>
</x-layouts.admin>
