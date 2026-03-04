<x-layouts.admin :title="'Edit Movie - VJPrime'">

    <section class="mt-5 rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <h1 class="text-lg font-semibold">Edit Movie</h1>
        <form action="{{ route('admin.movies.update', $movie) }}" method="POST" enctype="multipart/form-data" class="mt-4">
            @csrf
            @method('PUT')
            @include('admin.movies._form')
        </form>
    </section>
</x-layouts.admin>


