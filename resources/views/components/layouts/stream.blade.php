<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'VJPrime') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-950 text-slate-100 min-h-screen">
    @php
        $wallpapers = collect($wallpaperPosters ?? [])->filter()->take(8)->values();
    @endphp

    <div class="fixed inset-0 -z-20 overflow-hidden">
        @foreach ($wallpapers as $index => $poster)
            <div
                class="absolute inset-0 bg-cover bg-center wallpaper-slide"
                style="background-image: url('{{ $poster }}'); animation-delay: {{ $index * 4 }}s;"
            ></div>
        @endforeach
        <div class="absolute inset-0 bg-slate-950/75 backdrop-blur-[2px]"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-slate-950/80 via-slate-950/60 to-slate-950"></div>
    </div>

    <header class="sticky top-0 z-40 border-b border-white/10 bg-slate-950/70 backdrop-blur">
        <nav class="mx-auto max-w-7xl px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="{{ route('home') }}" class="inline-flex items-center">
                    <x-application-logo class="h-7 w-auto text-red-500 sm:h-8" />
                </a>
                <a href="{{ route('home') }}" class="text-sm text-slate-200 hover:text-white">Home</a>
                <a href="{{ route('browse') }}" class="text-sm text-slate-200 hover:text-white">Browse</a>
                @auth
                    <a href="{{ route('account.index') }}" class="text-sm text-slate-200 hover:text-white">Account</a>
                    @if (! auth()->user()->isPremium())
                        <a href="{{ route('billing.upgrade') }}" class="text-sm text-slate-200 hover:text-white">Upgrade</a>
                    @endif
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="text-sm text-slate-200 hover:text-white">Admin</a>
                    @endif
                @endauth
            </div>
            <div class="flex items-center gap-3">
                @auth
                    <span class="hidden sm:inline text-xs text-slate-300">
                        {{ auth()->user()->profile?->display_name ?? auth()->user()->name }}
                    </span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="rounded-md bg-white/10 px-3 py-1.5 text-xs hover:bg-white/20">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-md bg-white/10 px-3 py-1.5 text-xs hover:bg-white/20">Login</a>
                    <a href="{{ route('register') }}" class="rounded-md bg-red-600 px-3 py-1.5 text-xs hover:bg-red-500">Register</a>
                @endauth
            </div>
        </nav>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-6 sm:py-8">
        @if (session('status'))
            <div class="mb-4 rounded-md border border-emerald-500/40 bg-emerald-500/10 px-4 py-2 text-sm text-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-md border border-red-500/40 bg-red-500/10 px-4 py-2 text-sm text-red-200">
                {{ session('error') }}
            </div>
        @endif

        {{ $slot }}
    </main>

    <script>
        document.querySelectorAll('[data-preview]').forEach((video) => {
            const activate = () => {
                if (!video.dataset.loaded && video.dataset.src) {
                    video.src = video.dataset.src;
                    video.dataset.loaded = '1';
                }
                video.play().catch(() => {});
            };

            const deactivate = () => {
                video.pause();
                video.currentTime = 0;
            };

            video.closest('.preview-card')?.addEventListener('mouseenter', activate);
            video.closest('.preview-card')?.addEventListener('mouseleave', deactivate);
            video.closest('.preview-card')?.addEventListener('touchstart', activate, { passive: true });
            video.closest('.preview-card')?.addEventListener('touchend', deactivate, { passive: true });
        });
    </script>
</body>
</html>
