<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin - ' . config('app.name', 'VJPrime') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-950 text-slate-100 min-h-screen">
    <div class="min-h-screen lg:grid lg:grid-cols-[260px_minmax(0,1fr)]">
        <aside class="border-b border-white/10 bg-slate-900/80 p-4 backdrop-blur lg:sticky lg:top-0 lg:h-screen lg:border-b-0 lg:border-r">
            <div class="mb-6">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Admin Panel</p>
                <a href="{{ route('admin.dashboard') }}" class="mt-2 inline-flex items-center">
                    <x-application-logo class="h-7 w-auto text-red-500 sm:h-8" />
                </a>
            </div>

            <nav class="space-y-1 text-sm">
                <a href="{{ route('admin.dashboard') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('admin.dashboard') ? 'bg-white/15 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">Dashboard</a>
                <a href="{{ route('admin.movies.index') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('admin.movies.*') ? 'bg-white/15 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">Movies</a>
                <a href="{{ route('admin.genres.index') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('admin.genres.*') ? 'bg-white/15 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">Genres</a>
                <a href="{{ route('admin.languages.index') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('admin.languages.*') ? 'bg-white/15 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">Languages</a>
                <a href="{{ route('admin.vjs.index') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('admin.vjs.*') ? 'bg-white/15 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">VJs</a>
            </nav>

            <div class="mt-5 border-t border-white/10 pt-4">
                <p class="mb-2 px-3 text-xs uppercase tracking-wide text-slate-500">Quick Add</p>
                <div class="space-y-1 text-sm">
                    <a href="{{ route('admin.movies.create') }}" class="block rounded-md px-3 py-2 text-slate-300 hover:bg-white/10 hover:text-white">Add Movie/Series</a>
                    <a href="{{ route('admin.genres.create') }}" class="block rounded-md px-3 py-2 text-slate-300 hover:bg-white/10 hover:text-white">Add Genre</a>
                    <a href="{{ route('admin.languages.create') }}" class="block rounded-md px-3 py-2 text-slate-300 hover:bg-white/10 hover:text-white">Add Language</a>
                    <a href="{{ route('admin.vjs.create') }}" class="block rounded-md px-3 py-2 text-slate-300 hover:bg-white/10 hover:text-white">Add VJ</a>
                </div>
            </div>

            <div class="mt-6 space-y-2 border-t border-white/10 pt-4 text-sm">
                <a href="{{ route('home') }}" class="block rounded-md px-3 py-2 text-slate-300 hover:bg-white/10 hover:text-white">Back To Site</a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="w-full rounded-md bg-white/10 px-3 py-2 text-left text-slate-200 hover:bg-white/20">Logout</button>
                </form>
            </div>
        </aside>

        <div class="min-w-0 px-4 py-6 sm:px-6 lg:px-8">
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
        </div>
    </div>
</body>
</html>
