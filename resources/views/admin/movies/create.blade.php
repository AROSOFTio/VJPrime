<x-layouts.stream :title="'Create Movie - AroStream'">
    @include('admin.partials.nav')

    <section class="mt-5 rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <h1 class="text-lg font-semibold">Add Movie</h1>
        <form action="{{ route('admin.movies.store') }}" method="POST" class="mt-4">
            @csrf
            @include('admin.movies._form')
        </form>
    </section>
</x-layouts.stream>
