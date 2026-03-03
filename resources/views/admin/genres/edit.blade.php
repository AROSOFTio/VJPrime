<x-layouts.stream :title="'Edit Genre - AroStream'">
    @include('admin.partials.nav')

    <section class="mt-5 max-w-xl rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <h1 class="text-lg font-semibold">Edit Genre</h1>
        <form action="{{ route('admin.genres.update', $genre) }}" method="POST" class="mt-4 space-y-3">
            @csrf
            @method('PUT')
            <input type="text" name="name" value="{{ old('name', $genre->name) }}" placeholder="Name" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
            <input type="text" name="slug" value="{{ old('slug', $genre->slug) }}" placeholder="Slug" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
            <button class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white">Update</button>
        </form>
    </section>
</x-layouts.stream>
