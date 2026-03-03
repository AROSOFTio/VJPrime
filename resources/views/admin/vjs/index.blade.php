<x-layouts.stream :title="'Admin VJs - AroStream'">
    @include('admin.partials.nav')

    <section class="mt-5 rounded-xl border border-white/10 bg-slate-900/70 p-4">
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-lg font-semibold">VJs</h1>
            <a href="{{ route('admin.vjs.create') }}" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white">Add VJ</a>
        </div>

        <div class="space-y-2">
            @foreach ($vjs as $vj)
                <div class="flex items-center justify-between rounded-md border border-white/10 bg-slate-950/60 px-3 py-2">
                    <div>
                        <p class="text-sm font-medium">{{ $vj->name }}</p>
                        <p class="text-xs text-slate-400">{{ $vj->slug }}</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.vjs.edit', $vj) }}" class="rounded-md border border-white/20 px-3 py-1 text-xs">Edit</a>
                        <form action="{{ route('admin.vjs.destroy', $vj) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button class="rounded-md border border-red-500/40 px-3 py-1 text-xs text-red-300">Delete</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">{{ $vjs->links() }}</div>
    </section>
</x-layouts.stream>
