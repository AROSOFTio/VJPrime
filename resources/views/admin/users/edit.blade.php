<x-layouts.admin :title="'Edit User - VJPrime'">

    <section class="mt-5 max-w-3xl rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <h1 class="text-lg font-semibold">Edit User</h1>
        <form action="{{ route('admin.users.update', $user) }}" method="POST" class="mt-4">
            @csrf
            @method('PUT')
            @include('admin.users._form')
        </form>
    </section>
</x-layouts.admin>
