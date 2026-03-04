<x-layouts.admin :title="'Create Language - VJPrime'">

    <section class="mt-5 max-w-xl rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <h1 class="text-lg font-semibold">Add Language</h1>
        <form action="{{ route('admin.languages.store') }}" method="POST" class="mt-4 space-y-3">
            @csrf
            <input type="text" name="name" value="{{ old('name') }}" placeholder="Name" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
            <input type="text" name="code" value="{{ old('code') }}" placeholder="Code (e.g. teo)" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
            <button class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white">Save</button>
        </form>
    </section>
</x-layouts.admin>


