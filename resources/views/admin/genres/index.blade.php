<x-layouts.admin :title="'Admin Genres - VJPrime'">

    <section class="mt-5 rounded-xl border border-white/10 bg-slate-900/70 p-4">
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-lg font-semibold">Genres</h1>
            <a href="{{ route('admin.genres.create') }}" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white">Add Genre</a>
        </div>

        <div class="space-y-2">
            @foreach ($genres as $genre)
                <div class="flex items-center justify-between rounded-md border border-white/10 bg-slate-950/60 px-3 py-2">
                    <div>
                        <p class="text-sm font-medium">{{ $genre->name }}</p>
                        <p class="text-xs text-slate-400">{{ $genre->slug }}</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.genres.edit', $genre) }}" class="rounded-md border border-white/20 px-3 py-1 text-xs">Edit</a>
                        <form action="{{ route('admin.genres.destroy', $genre) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button class="rounded-md border border-red-500/40 px-3 py-1 text-xs text-red-300">Delete</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">{{ $genres->links() }}</div>
    </section>
</x-layouts.admin>


