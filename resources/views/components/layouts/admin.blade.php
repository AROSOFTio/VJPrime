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
                <a href="{{ route('admin.dashboard') }}" class="mt-2 inline-flex items-center rounded-md bg-white/95 px-2.5 py-1.5 shadow-sm">
                    <x-application-logo class="text-lg sm:text-xl" />
                </a>
            </div>

            @php
                $contentOpen = request()->routeIs('admin.movies.*') || request()->routeIs('admin.genres.*') || request()->routeIs('admin.languages.*') || request()->routeIs('admin.vjs.*');
                $usersOpen = request()->routeIs('admin.users.*');
                $reportsOpen = request()->routeIs('admin.reports.*');
            @endphp

            <nav class="space-y-2 text-sm">
                <a href="{{ route('admin.dashboard') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('admin.dashboard') ? 'bg-white/15 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">Dashboard</a>

                @can('manage-content')
                    <details class="rounded-md border border-white/10 bg-slate-950/40" {{ $contentOpen ? 'open' : '' }}>
                        <summary class="cursor-pointer list-none rounded-md px-3 py-2 text-slate-200 hover:bg-white/10">
                            <span class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M4 7h16M4 12h16M4 17h10" stroke-width="2" stroke-linecap="round" />
                                </svg>
                                Content
                            </span>
                        </summary>
                        <div class="space-y-1 px-2 pb-2">
                            <a href="{{ route('admin.movies.index') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('admin.movies.*') ? 'bg-white/15 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">Movies / Series</a>
                            <a href="{{ route('admin.movies.create') }}" class="block rounded-md px-3 py-2 text-slate-300 hover:bg-white/10 hover:text-white">Create Content</a>
                            <a href="{{ route('admin.genres.index') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('admin.genres.*') ? 'bg-white/15 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">Genres</a>
                            <a href="{{ route('admin.languages.index') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('admin.languages.*') ? 'bg-white/15 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">Languages</a>
                            <a href="{{ route('admin.vjs.index') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('admin.vjs.*') ? 'bg-white/15 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">VJs</a>
                        </div>
                    </details>
                @endcan

                @can('manage-users')
                    <details class="rounded-md border border-white/10 bg-slate-950/40" {{ $usersOpen ? 'open' : '' }}>
                        <summary class="cursor-pointer list-none rounded-md px-3 py-2 text-slate-200 hover:bg-white/10">
                            <span class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <circle cx="9" cy="7" r="4" stroke-width="2" />
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                Users
                            </span>
                        </summary>
                        <div class="space-y-1 px-2 pb-2">
                            <a href="{{ route('admin.users.index') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('admin.users.index') ? 'bg-white/15 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">View Users</a>
                            <a href="{{ route('admin.users.create') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('admin.users.create') ? 'bg-white/15 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">Create User</a>
                        </div>
                    </details>
                @endcan

                @can('view-reports')
                    <details class="rounded-md border border-white/10 bg-slate-950/40" {{ $reportsOpen ? 'open' : '' }}>
                        <summary class="cursor-pointer list-none rounded-md px-3 py-2 text-slate-200 hover:bg-white/10">
                            <span class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M3 3v18h18" stroke-width="2" stroke-linecap="round" />
                                    <path d="M7 15l4-4 3 3 5-6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                Reports
                            </span>
                        </summary>
                        <div class="space-y-1 px-2 pb-2">
                            <a href="{{ route('admin.reports.revenue') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('admin.reports.revenue') ? 'bg-white/15 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">Revenue</a>
                            <a href="{{ route('admin.reports.content') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('admin.reports.content') ? 'bg-white/15 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">Content</a>
                            <a href="{{ route('admin.reports.users') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('admin.reports.users') ? 'bg-white/15 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">Users</a>
                        </div>
                    </details>
                @endcan
            </nav>

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
