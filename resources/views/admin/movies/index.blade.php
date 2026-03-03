<x-layouts.stream :title="'Admin Movies - VJPrime'">
    @include('admin.partials.nav')

    <section class="mt-5 rounded-xl border border-white/10 bg-slate-900/70 p-4">
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-lg font-semibold">Movies & Series</h1>
            <a href="{{ route('admin.movies.create') }}" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white">Add Content</a>
        </div>

        <div class="space-y-2">
            @foreach ($movies as $movie)
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
                        <form action="{{ route('admin.movies.destroy', $movie) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button class="rounded-md border border-red-500/40 px-3 py-1 text-xs text-red-300">Delete</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">{{ $movies->links() }}</div>
    </section>
</x-layouts.stream>

