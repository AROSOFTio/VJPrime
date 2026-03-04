<x-layouts.admin :title="'Create User - VJPrime'">

    <section class="mt-5 max-w-3xl rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <h1 class="text-lg font-semibold">Add User</h1>
        <form action="{{ route('admin.users.store') }}" method="POST" class="mt-4">
            @csrf
            @include('admin.users._form')
        </form>
    </section>
</x-layouts.admin>
