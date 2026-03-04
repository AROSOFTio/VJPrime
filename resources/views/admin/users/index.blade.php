<x-layouts.admin :title="'Admin Users - VJPrime'">

    <section class="mt-5 rounded-xl border border-white/10 bg-slate-900/70 p-4">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-lg font-semibold">Users</h1>
            <a href="{{ route('admin.users.create') }}" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white">Add User</a>
        </div>

        <form method="GET" action="{{ route('admin.users.index') }}" class="mb-4 grid gap-3 rounded-lg border border-white/10 bg-slate-950/40 p-3 sm:grid-cols-2 lg:grid-cols-5">
            <input
                type="text"
                name="search"
                value="{{ $filters['search'] ?? '' }}"
                placeholder="Search name, email, phone..."
                class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm"
            >

            <select name="role" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
                <option value="">All Roles</option>
                <option value="admin" @selected(($filters['role'] ?? '') === 'admin')>Admin</option>
                <option value="user" @selected(($filters['role'] ?? '') === 'user')>User</option>
            </select>

            <select name="subscription_status" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
                <option value="">All Subscriptions</option>
                <option value="free" @selected(($filters['subscription_status'] ?? '') === 'free')>Free</option>
                <option value="premium" @selected(($filters['subscription_status'] ?? '') === 'premium')>Premium</option>
            </select>

            <select name="sort" class="rounded-md border border-white/10 bg-slate-900 px-3 py-2 text-sm">
                <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Newest</option>
                <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>Oldest</option>
                <option value="name" @selected(($filters['sort'] ?? '') === 'name')>Name</option>
                <option value="email" @selected(($filters['sort'] ?? '') === 'email')>Email</option>
            </select>

            <div class="flex gap-2">
                <button class="rounded-md bg-white/10 px-4 py-2 text-sm text-slate-100 hover:bg-white/20">Filter</button>
                <a href="{{ route('admin.users.index') }}" class="rounded-md border border-white/20 px-4 py-2 text-sm text-slate-200">Reset</a>
            </div>
        </form>

        <div class="space-y-2">
            @forelse ($users as $user)
                <div class="flex flex-col gap-3 rounded-md border border-white/10 bg-slate-950/60 px-3 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-medium">
                            {{ $user->name }}
                            @if (auth()->id() === $user->id)
                                <span class="rounded bg-sky-500/20 px-1.5 py-0.5 text-[10px] uppercase tracking-wide text-sky-200">You</span>
                            @endif
                        </p>
                        <p class="text-xs text-slate-400">
                            Display: {{ $user->profile?->display_name ?: '-' }} |
                            Email: {{ $user->email ?: '-' }} |
                            Phone: {{ $user->phone ?: '-' }}
                        </p>
                        <p class="mt-1 text-xs text-slate-300">
                            <span class="rounded bg-white/10 px-1.5 py-0.5">{{ strtoupper($user->role) }}</span>
                            <span class="ml-1 rounded bg-white/10 px-1.5 py-0.5">{{ strtoupper($user->subscription_status) }}</span>
                            @if ($user->subscription_expires_at)
                                <span class="ml-1 text-slate-400">Expires: {{ $user->subscription_expires_at->format('Y-m-d H:i') }}</span>
                            @endif
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('admin.users.edit', $user) }}" class="rounded-md border border-white/20 px-3 py-1 text-xs">Edit</a>
                        @if (auth()->id() !== $user->id)
                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button onclick="return confirm('Delete this user account?')" class="rounded-md border border-red-500/40 px-3 py-1 text-xs text-red-300">Delete</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="rounded-md border border-white/10 bg-slate-950/50 px-3 py-4 text-sm text-slate-300">No users found.</p>
            @endforelse
        </div>

        <div class="mt-4">{{ $users->links() }}</div>
    </section>
</x-layouts.admin>
