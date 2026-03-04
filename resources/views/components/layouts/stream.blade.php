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
        <nav class="mx-auto max-w-7xl px-4 py-3">
            <div class="flex items-center justify-between md:hidden">
                <a href="{{ route('home') }}" class="inline-flex items-center rounded-md bg-white/95 px-2.5 py-1.5 shadow-sm">
                    <x-application-logo class="text-lg" />
                </a>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        data-mobile-search-toggle
                        class="inline-flex items-center rounded-md border border-white/20 bg-white/5 px-2.5 py-1.5 text-[11px] font-medium text-slate-200 hover:bg-white/10"
                    >
                        Search
                    </button>
                    <div class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/40 bg-emerald-500/10 px-2.5 py-1 text-[11px] font-medium text-emerald-200">
                        <span class="relative inline-flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-300 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
                        </span>
                        <span data-online-users-count>{{ number_format((int) ($onlineUsersCount ?? 0)) }}</span>
                    </div>
                    <button
                        type="button"
                        data-mobile-menu-open
                        aria-label="Open menu"
                        class="inline-flex items-center justify-center rounded-md border border-white/20 bg-white/5 p-2 text-slate-100 hover:bg-white/10"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                        </svg>
                    </button>
                </div>
            </div>

            <div data-mobile-search-panel class="mt-3 hidden md:hidden">
                <form action="{{ route('browse') }}" method="GET" class="flex items-center gap-2 rounded-lg border border-white/10 bg-slate-900/90 p-2">
                    <input
                        type="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search movies and series"
                        class="w-full rounded-md border border-white/10 bg-slate-950/80 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-400 focus:border-red-500 focus:outline-none"
                    >
                    <button class="rounded-md bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-500">Go</button>
                </form>
            </div>

            <div class="hidden items-center justify-between md:flex">
                <div class="flex items-center gap-6">
                    <a href="{{ route('home') }}" class="inline-flex items-center rounded-md bg-white/95 px-2.5 py-1.5 shadow-sm">
                        <x-application-logo class="text-lg sm:text-xl" />
                    </a>
                    <a href="{{ route('home') }}" class="text-sm text-slate-200 hover:text-white">Home</a>
                    <a href="{{ route('browse') }}" class="text-sm text-slate-200 hover:text-white">Browse</a>
                    @auth
                        <a href="{{ route('account.index') }}" class="text-sm text-slate-200 hover:text-white">Account</a>
                        @if (! auth()->user()->isPremium())
                            <a href="{{ route('billing.upgrade') }}" class="text-sm text-slate-200 hover:text-white">Upgrade</a>
                        @endif
                        @if (auth()->user()->canAccessAdminPanel())
                            <a href="{{ route('admin.dashboard') }}" class="text-sm text-slate-200 hover:text-white">Admin</a>
                        @endif
                    @endauth
                </div>
                <div class="flex items-center gap-3">
                    <div class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/40 bg-emerald-500/10 px-2.5 py-1 text-[11px] font-medium text-emerald-200">
                        <span class="relative inline-flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-300 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
                        </span>
                        <span data-online-users-count>{{ number_format((int) ($onlineUsersCount ?? 0)) }} online</span>
                    </div>
                    @auth
                        <span class="hidden lg:inline text-xs text-slate-300">
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
            </div>
        </nav>
    </header>

    <div data-mobile-menu class="fixed inset-0 z-50 hidden md:hidden" aria-hidden="true">
        <button
            type="button"
            data-mobile-menu-overlay
            aria-label="Close menu"
            class="absolute inset-0 bg-slate-950/55 backdrop-blur-[1px]"
        ></button>
        <aside
            data-mobile-menu-panel
            class="absolute inset-y-0 right-0 h-full w-[90vw] max-w-md translate-x-full border-l border-white/10 bg-slate-900/92 p-4 shadow-2xl backdrop-blur-md transition-transform duration-300 ease-out"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex items-center justify-between">
                <a href="{{ route('home') }}" class="inline-flex items-center rounded-md bg-white/95 px-2.5 py-1.5 shadow-sm" data-mobile-menu-close>
                    <x-application-logo class="text-lg" />
                </a>
                <button
                    type="button"
                    data-mobile-menu-close
                    aria-label="Close menu"
                    class="inline-flex items-center justify-center rounded-md border border-white/20 bg-white/5 p-2 text-slate-100 hover:bg-white/10"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 6l12 12M18 6l-12 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('browse') }}" method="GET" class="mt-4 flex items-center gap-2 rounded-lg border border-white/10 bg-slate-950/70 p-2">
                <input
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search..."
                    class="w-full rounded-md border border-white/10 bg-slate-950/80 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-400 focus:border-red-500 focus:outline-none"
                >
                <button class="rounded-md bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-500">Go</button>
            </form>

            <div class="mt-6 space-y-2">
                <a href="{{ route('home') }}" class="block rounded-md border border-white/10 bg-white/5 px-3 py-2 text-sm text-slate-100 hover:bg-white/10" data-mobile-menu-close>Home</a>
                <a href="{{ route('browse') }}" class="block rounded-md border border-white/10 bg-white/5 px-3 py-2 text-sm text-slate-100 hover:bg-white/10" data-mobile-menu-close>Browse</a>
                @auth
                    <a href="{{ route('account.index') }}" class="block rounded-md border border-white/10 bg-white/5 px-3 py-2 text-sm text-slate-100 hover:bg-white/10" data-mobile-menu-close>Account</a>
                    @if (! auth()->user()->isPremium())
                        <a href="{{ route('billing.upgrade') }}" class="block rounded-md border border-white/10 bg-white/5 px-3 py-2 text-sm text-slate-100 hover:bg-white/10" data-mobile-menu-close>Upgrade</a>
                    @endif
                    @if (auth()->user()->canAccessAdminPanel())
                        <a href="{{ route('admin.dashboard') }}" class="block rounded-md border border-white/10 bg-white/5 px-3 py-2 text-sm text-slate-100 hover:bg-white/10" data-mobile-menu-close>Admin</a>
                    @endif
                @endauth
            </div>

            <div class="mt-6 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-emerald-200">
                <p class="text-[11px] uppercase tracking-[0.2em] text-emerald-300/80">Live Online</p>
                <p class="mt-1 text-lg font-semibold">
                    <span data-online-users-count>{{ number_format((int) ($onlineUsersCount ?? 0)) }}</span>
                </p>
            </div>

            <div class="mt-6 space-y-2">
                @auth
                    <p class="text-xs text-slate-300">
                        {{ auth()->user()->profile?->display_name ?? auth()->user()->name }}
                    </p>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="w-full rounded-md bg-white/10 px-3 py-2 text-xs text-slate-100 hover:bg-white/20">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="block rounded-md bg-white/10 px-3 py-2 text-center text-xs text-slate-100 hover:bg-white/20" data-mobile-menu-close>Login</a>
                    <a href="{{ route('register') }}" class="block rounded-md bg-red-600 px-3 py-2 text-center text-xs font-semibold text-white hover:bg-red-500" data-mobile-menu-close>Register</a>
                @endauth
            </div>
        </aside>
    </div>

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
        const getVisitorId = () => {
            const key = 'vjprime-visitor-id';

            try {
                const existing = window.localStorage.getItem(key);
                if (existing) {
                    return existing;
                }
            } catch (_error) {
                // Continue with generated value.
            }

            const generated = [Date.now().toString(36), Math.random().toString(36).slice(2, 10)].join('-');

            try {
                window.localStorage.setItem(key, generated);
            } catch (_error) {
                // Ignore write failures.
            }

            return generated;
        };

        const onlineUsersNodes = Array.from(document.querySelectorAll('[data-online-users-count]'));
        if (onlineUsersNodes.length > 0) {
            const visitorId = getVisitorId();
            const refreshOnlineUsers = async () => {
                try {
                    const response = await fetch('{{ route('online-users.count') }}', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Device-Id': visitorId,
                        },
                        credentials: 'same-origin',
                    });

                    if (! response.ok) {
                        return;
                    }

                    const payload = await response.json();
                    const count = Number(payload.online_users ?? 0);
                    const textCount = Number.isFinite(count) ? count.toLocaleString() : '0';
                    onlineUsersNodes.forEach((node) => {
                        const includeSuffix = node.textContent?.toLowerCase().includes('online') ?? false;
                        node.textContent = includeSuffix ? `${textCount} online` : textCount;
                    });
                } catch (_error) {
                    // Ignore polling failures and keep last known value.
                }
            };

            refreshOnlineUsers();
            window.setInterval(refreshOnlineUsers, 30000);
        }

        const mobileSearchToggle = document.querySelector('[data-mobile-search-toggle]');
        const mobileSearchPanel = document.querySelector('[data-mobile-search-panel]');
        if (mobileSearchToggle && mobileSearchPanel) {
            mobileSearchToggle.addEventListener('click', () => {
                mobileSearchPanel.classList.toggle('hidden');
            });
        }

        const mobileMenu = document.querySelector('[data-mobile-menu]');
        const mobileMenuPanel = mobileMenu?.querySelector('[data-mobile-menu-panel]');
        const openMobileMenuButton = document.querySelector('[data-mobile-menu-open]');
        const closeMobileMenuButtons = mobileMenu?.querySelectorAll('[data-mobile-menu-close], [data-mobile-menu-overlay]') ?? [];
        let mobileMenuClosingTimeout = null;

        const closeMobileMenu = () => {
            if (! mobileMenu || ! mobileMenuPanel) {
                return;
            }

            mobileMenuPanel.classList.add('translate-x-full');

            if (mobileMenuClosingTimeout) {
                window.clearTimeout(mobileMenuClosingTimeout);
            }

            mobileMenuClosingTimeout = window.setTimeout(() => {
                mobileMenu.classList.add('hidden');
            }, 280);

            document.body.classList.remove('overflow-hidden');
        };

        const openMobileMenu = () => {
            if (! mobileMenu || ! mobileMenuPanel) {
                return;
            }

            mobileMenu.classList.remove('hidden');
            window.requestAnimationFrame(() => {
                mobileMenuPanel.classList.remove('translate-x-full');
            });
            document.body.classList.add('overflow-hidden');
        };

        if (openMobileMenuButton && mobileMenuPanel && mobileMenu) {
            openMobileMenuButton.addEventListener('click', openMobileMenu);
            closeMobileMenuButtons.forEach((button) => {
                button.addEventListener('click', closeMobileMenu);
            });

            window.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !mobileMenu.classList.contains('hidden')) {
                    closeMobileMenu();
                }
            });
        }

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
