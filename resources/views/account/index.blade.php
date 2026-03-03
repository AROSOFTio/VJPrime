<x-layouts.stream :title="'Account - AroStream'" :wallpaper-posters="[auth()->user()->profile?->avatar_url]">
    <section class="grid gap-5 lg:grid-cols-[1fr,320px]">
        <div class="space-y-4 rounded-xl border border-white/10 bg-slate-900/70 p-5">
            <h1 class="text-2xl font-semibold">Account</h1>
            <p class="text-sm text-slate-300">Manage your profile and monitor free watch quota.</p>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-md border border-white/10 bg-slate-950/60 p-3">
                    <p class="text-xs text-slate-400">Name</p>
                    <p class="text-sm font-medium">{{ $user->profile?->display_name ?? $user->name }}</p>
                </div>
                <div class="rounded-md border border-white/10 bg-slate-950/60 p-3">
                    <p class="text-xs text-slate-400">Subscription</p>
                    <p class="text-sm font-medium">{{ ucfirst($user->subscription_status) }}</p>
                </div>
                <div class="rounded-md border border-white/10 bg-slate-950/60 p-3">
                    <p class="text-xs text-slate-400">Favorites</p>
                    <p class="text-sm font-medium">{{ $favoritesCount }}</p>
                </div>
                <div class="rounded-md border border-white/10 bg-slate-950/60 p-3">
                    <p class="text-xs text-slate-400">Downloads (24h)</p>
                    <p class="text-sm font-medium">{{ $downloadsCount }}</p>
                </div>
            </div>

            <a href="{{ route('profile.edit') }}" class="inline-flex rounded-md border border-white/20 px-4 py-2 text-sm">Edit Profile</a>
        </div>

        <aside class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
            <h2 class="text-lg font-semibold">Free Minutes Used Today</h2>
            @if ($user->isPremium())
                <p class="mt-2 text-sm text-emerald-300">Premium account: unlimited streaming.</p>
            @else
                @php
                    $usedPct = $limit > 0 ? min(($used / $limit) * 100, 100) : 0;
                @endphp
                <p class="mt-2 text-sm text-slate-300">{{ floor($used / 60) }} / {{ floor($limit / 60) }} minutes</p>
                <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-white/10">
                    <div class="h-full bg-red-500" style="width: {{ $usedPct }}%"></div>
                </div>
                <p class="mt-3 text-xs text-slate-400">
                    Reset time:
                    {{ $resetAt ? $resetAt->timezone(config('app.timezone'))->toDayDateTimeString() : 'N/A' }}
                </p>
                <a href="{{ route('billing.upgrade') }}" class="mt-4 block w-full rounded-md bg-red-600 px-4 py-2 text-center text-sm font-semibold text-white">Upgrade to Premium</a>
            @endif
        </aside>
    </section>
</x-layouts.stream>
