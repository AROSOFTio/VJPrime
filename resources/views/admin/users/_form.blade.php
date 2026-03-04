@php
    $isEdit = isset($user);
    $profile = $isEdit ? ($user->profile ?? null) : null;
    $subscriptionExpiresAt = old('subscription_expires_at', ($isEdit && $user->subscription_expires_at) ? $user->subscription_expires_at->format('Y-m-d\TH:i') : null);
@endphp

@if ($errors->any())
    <div class="mb-3 rounded-md border border-red-500/40 bg-red-500/10 px-3 py-2 text-xs text-red-100">
        <ul class="space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid gap-3 sm:grid-cols-2">
    <div>
        <label class="mb-1 block text-xs text-slate-300">Name *</label>
        <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
    </div>

    <div>
        <label class="mb-1 block text-xs text-slate-300">Display name</label>
        <input type="text" name="display_name" value="{{ old('display_name', $profile->display_name ?? '') }}" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
    </div>

    <div>
        <label class="mb-1 block text-xs text-slate-300">Email</label>
        <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
    </div>

    <div>
        <label class="mb-1 block text-xs text-slate-300">Phone (+256#########)</label>
        <input type="text" name="phone" value="{{ old('phone', $user->phone ?? '') }}" placeholder="+2567XXXXXXXX" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
    </div>

    <div>
        <label class="mb-1 block text-xs text-slate-300">Role *</label>
        <select name="role" required class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
            <option value="user" @selected(old('role', $user->role ?? 'user') === 'user')>Viewer / Customer</option>
            <option value="admin" @selected(old('role', $user->role ?? 'user') === 'admin')>Admin</option>
            <option value="content_manager" @selected(old('role', $user->role ?? 'user') === 'content_manager')>Content Manager</option>
            <option value="contributor" @selected(old('role', $user->role ?? 'user') === 'contributor')>Contributor</option>
            <option value="finance_manager" @selected(old('role', $user->role ?? 'user') === 'finance_manager')>Finance Manager</option>
        </select>
    </div>

    <div>
        <label class="mb-1 block text-xs text-slate-300">Subscription *</label>
        <select name="subscription_status" required class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
            <option value="free" @selected(old('subscription_status', $user->subscription_status ?? 'free') === 'free')>Free</option>
            <option value="premium" @selected(old('subscription_status', $user->subscription_status ?? 'free') === 'premium')>Premium</option>
        </select>
    </div>

    <div class="sm:col-span-2">
        <label class="mb-1 block text-xs text-slate-300">Subscription expires at (optional)</label>
        <input type="datetime-local" name="subscription_expires_at" value="{{ $subscriptionExpiresAt }}" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
        <p class="mt-1 text-xs text-slate-400">Leave empty for no expiry. Ignored when subscription is set to Free.</p>
    </div>

    <div>
        <label class="mb-1 block text-xs text-slate-300">Password {{ $isEdit ? '(leave blank to keep current)' : '*' }}</label>
        <input type="password" name="password" @required(! $isEdit) class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
    </div>

    <div>
        <label class="mb-1 block text-xs text-slate-300">Confirm password {{ $isEdit ? '' : '*' }}</label>
        <input type="password" name="password_confirmation" @required(! $isEdit) class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
    </div>
</div>

<button type="submit" class="mt-4 rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white">
    {{ $isEdit ? 'Update User' : 'Create User' }}
</button>
