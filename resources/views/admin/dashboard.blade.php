<x-layouts.stream :title="'Admin Dashboard - AroStream'">
    @include('admin.partials.nav')

    <section class="mt-5 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Movies</p>
            <p class="mt-1 text-2xl font-semibold">{{ $movieCount }}</p>
        </div>
        <div class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Published</p>
            <p class="mt-1 text-2xl font-semibold">{{ $publishedMovieCount }}</p>
        </div>
        <div class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Users</p>
            <p class="mt-1 text-2xl font-semibold">{{ $userCount }}</p>
        </div>
    </section>
</x-layouts.stream>
