<x-layouts.stream :title="'VJPrime'" :wallpaper-posters="$wallpaperPosters">
    @php
        $heroSlides = ($featured->isNotEmpty() ? $featured : $trending)
            ->filter()
            ->take(8)
            ->values();
        $continueMovies = $continueWatching
            ->pluck('movie')
            ->filter()
            ->values();
        $continueProgressByMovieId = $continueWatching
            ->filter(fn ($progress) => $progress->movie && $progress->movie_id)
            ->mapWithKeys(fn ($progress) => [$progress->movie_id => (int) $progress->last_position_seconds])
            ->all();
    @endphp

    @if ($heroSlides->isNotEmpty())
        <section class="mb-6" data-hero-carousel>
            <div class="relative overflow-hidden rounded-2xl border border-white/10 bg-slate-950/70 shadow-[0_16px_50px_rgba(2,6,23,0.55)]">
                <div class="relative min-h-[320px] sm:min-h-[380px] lg:min-h-[470px]">
                    @foreach ($heroSlides as $index => $movie)
                        <article
                            data-hero-slide
                            class="absolute inset-0 transition-opacity duration-700 {{ $index === 0 ? 'opacity-100' : 'pointer-events-none opacity-0' }}"
                        >
                            <img
                                src="{{ $movie->backdrop_url ?: $movie->poster_url ?: 'https://picsum.photos/seed/VJPrime-hero/1600/900' }}"
                                alt="{{ $movie->title }}"
                                class="absolute inset-0 h-full w-full object-cover"
                            >
                            <div class="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-950/75 to-slate-950/15"></div>
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/25 to-transparent"></div>

                            <div class="relative z-10 flex h-full flex-col justify-end p-5 sm:p-8 lg:p-10">
                                <div class="max-w-2xl">
                                    <p class="text-[11px] uppercase tracking-[0.3em] text-red-400">Now Streaming</p>
                                    <h1 class="mt-2 text-2xl font-semibold leading-tight text-white sm:text-4xl">{{ $movie->title }}</h1>
                                    <p class="mt-2 line-clamp-2 text-sm text-slate-200 sm:text-base">
                                        {{ \Illuminate\Support\Str::limit($movie->description ?: 'Watch now on VJPrime.', 180) }}
                                    </p>
                                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-200/95">
                                        <span class="rounded-full border border-white/20 bg-black/20 px-2 py-1">{{ $movie->year ?: now()->year }}</span>
                                        <span class="rounded-full border border-white/20 bg-black/20 px-2 py-1">{{ ucfirst($movie->content_type ?? 'movie') }}</span>
                                        <span class="rounded-full border border-white/20 bg-black/20 px-2 py-1">{{ $movie->language?->name ?: 'Multi Language' }}</span>
                                    </div>
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <a href="{{ route('movies.show', $movie->slug) }}" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-slate-200">
                                            Details
                                        </a>
                                        @auth
                                            <a href="{{ route('player.show', $movie->slug) }}" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-500">
                                                Play
                                            </a>
                                        @else
                                            <a href="{{ route('login') }}" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-500">
                                                Login to Watch
                                            </a>
                                        @endauth
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach

                    @if ($heroSlides->count() > 1)
                        <button
                            type="button"
                            data-hero-prev
                            aria-label="Previous slide"
                            class="absolute left-3 top-1/2 z-20 -translate-y-1/2 rounded-full border border-white/35 bg-slate-900/65 p-2 text-white backdrop-blur transition hover:bg-slate-800/90"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M15 5l-7 7 7 7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            data-hero-next
                            aria-label="Next slide"
                            class="absolute right-3 top-1/2 z-20 -translate-y-1/2 rounded-full border border-white/35 bg-slate-900/65 p-2 text-white backdrop-blur transition hover:bg-slate-800/90"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                        <div class="absolute bottom-4 right-4 z-20 flex items-center gap-1.5">
                            @foreach ($heroSlides as $index => $movie)
                                <button
                                    type="button"
                                    data-hero-dot
                                    data-index="{{ $index }}"
                                    aria-label="Go to slide {{ $index + 1 }}"
                                    class="h-2 rounded-full transition-all duration-300 {{ $index === 0 ? 'w-6 bg-white' : 'w-2 bg-white/55' }}"
                                ></button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="border-t border-white/10 bg-slate-950/75 px-4 py-3">
                    <div class="flex gap-2 overflow-x-auto scrollbar-none">
                        @foreach ($heroSlides as $index => $movie)
                            <button
                                type="button"
                                data-hero-thumb
                                data-index="{{ $index }}"
                                class="group flex min-w-[9rem] items-center gap-2 rounded-md border border-white/10 bg-slate-900/65 p-1.5 text-left transition hover:border-white/30 hover:bg-slate-800/80"
                            >
                                <img src="{{ $movie->poster_url ?: 'https://picsum.photos/seed/VJPrime-hero-thumb/160/240' }}" alt="{{ $movie->title }}" class="h-12 w-9 rounded object-cover">
                                <div>
                                    <p class="line-clamp-1 text-xs font-medium text-white">{{ $movie->title }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $movie->year ?: now()->year }}</p>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="space-y-6 sm:space-y-7">
        @include('partials.home-shelf', [
            'title' => 'Trending Now',
            'movies' => $trending,
            'moreHref' => route('browse', ['sort' => 'trending']),
            'auto' => true,
        ])

        @if ($featured->isNotEmpty())
            @include('partials.home-shelf', [
                'title' => 'Featured Picks',
                'movies' => $featured,
                'moreHref' => route('browse', ['sort' => 'new']),
            ])
        @endif

        @auth
            @if ($continueMovies->isNotEmpty())
                @include('partials.home-shelf', [
                    'title' => 'Continue Watching',
                    'movies' => $continueMovies,
                    'moreHref' => route('account.index'),
                    'progressByMovieId' => $continueProgressByMovieId,
                ])
            @endif

            @if ($yourList->isNotEmpty())
                @include('partials.home-shelf', [
                    'title' => 'Your List',
                    'movies' => $yourList,
                    'moreHref' => route('account.index'),
                ])
            @endif
        @endauth

        @foreach ($genreRows as $genre)
            @include('partials.home-shelf', [
                'title' => $genre->name,
                'movies' => $genre->movies,
                'moreHref' => route('browse', ['genre' => $genre->slug]),
            ])
        @endforeach
    </section>

    <script>
        (() => {
            const heroRoot = document.querySelector('[data-hero-carousel]');

            if (heroRoot) {
                const slides = Array.from(heroRoot.querySelectorAll('[data-hero-slide]'));
                const dots = Array.from(heroRoot.querySelectorAll('[data-hero-dot]'));
                const thumbs = Array.from(heroRoot.querySelectorAll('[data-hero-thumb]'));
                let activeIndex = 0;
                let timer = null;

                const setActiveSlide = (index) => {
                    if (! slides.length) {
                        return;
                    }

                    activeIndex = (index + slides.length) % slides.length;

                    slides.forEach((slide, slideIndex) => {
                        slide.classList.toggle('opacity-100', slideIndex === activeIndex);
                        slide.classList.toggle('opacity-0', slideIndex !== activeIndex);
                        slide.classList.toggle('pointer-events-none', slideIndex !== activeIndex);
                    });

                    dots.forEach((dot, dotIndex) => {
                        dot.classList.toggle('w-6', dotIndex === activeIndex);
                        dot.classList.toggle('bg-white', dotIndex === activeIndex);
                        dot.classList.toggle('w-2', dotIndex !== activeIndex);
                        dot.classList.toggle('bg-white/55', dotIndex !== activeIndex);
                    });

                    thumbs.forEach((thumb, thumbIndex) => {
                        thumb.classList.toggle('border-white/60', thumbIndex === activeIndex);
                        thumb.classList.toggle('bg-slate-800/90', thumbIndex === activeIndex);
                    });
                };

                const stopAuto = () => {
                    if (timer !== null) {
                        window.clearInterval(timer);
                        timer = null;
                    }
                };

                const startAuto = () => {
                    if (slides.length < 2) {
                        return;
                    }

                    stopAuto();
                    timer = window.setInterval(() => {
                        setActiveSlide(activeIndex + 1);
                    }, 5600);
                };

                heroRoot.querySelector('[data-hero-prev]')?.addEventListener('click', () => {
                    setActiveSlide(activeIndex - 1);
                    startAuto();
                });

                heroRoot.querySelector('[data-hero-next]')?.addEventListener('click', () => {
                    setActiveSlide(activeIndex + 1);
                    startAuto();
                });

                dots.forEach((dot) => {
                    dot.addEventListener('click', () => {
                        setActiveSlide(Number(dot.dataset.index || 0));
                        startAuto();
                    });
                });

                thumbs.forEach((thumb) => {
                    thumb.addEventListener('click', () => {
                        setActiveSlide(Number(thumb.dataset.index || 0));
                        startAuto();
                    });
                });

                heroRoot.addEventListener('mouseenter', stopAuto);
                heroRoot.addEventListener('mouseleave', startAuto);
                heroRoot.addEventListener('touchstart', stopAuto, { passive: true });
                heroRoot.addEventListener('touchend', startAuto, { passive: true });

                setActiveSlide(0);
                startAuto();
            }

            document.querySelectorAll('[data-shelf]').forEach((shelf) => {
                const track = shelf.querySelector('[data-shelf-track]');
                const prev = shelf.querySelector('[data-shelf-prev]');
                const next = shelf.querySelector('[data-shelf-next]');

                if (! track) {
                    return;
                }

                const scrollStep = () => Math.max(260, Math.round(track.clientWidth * 0.85));

                prev?.addEventListener('click', () => {
                    track.scrollBy({ left: -scrollStep(), behavior: 'smooth' });
                });

                next?.addEventListener('click', () => {
                    track.scrollBy({ left: scrollStep(), behavior: 'smooth' });
                });

                if (track.dataset.shelfAuto !== '1') {
                    return;
                }

                let autoTimer = null;

                const stopAuto = () => {
                    if (autoTimer !== null) {
                        window.clearInterval(autoTimer);
                        autoTimer = null;
                    }
                };

                const startAuto = () => {
                    stopAuto();
                    autoTimer = window.setInterval(() => {
                        const maxScrollLeft = track.scrollWidth - track.clientWidth;

                        if (maxScrollLeft <= 0) {
                            return;
                        }

                        if (track.scrollLeft >= maxScrollLeft - 24) {
                            track.scrollTo({ left: 0, behavior: 'smooth' });
                            return;
                        }

                        track.scrollBy({ left: Math.max(180, Math.round(track.clientWidth * 0.45)), behavior: 'smooth' });
                    }, 4200);
                };

                track.addEventListener('mouseenter', stopAuto);
                track.addEventListener('mouseleave', startAuto);
                track.addEventListener('touchstart', stopAuto, { passive: true });
                track.addEventListener('touchend', startAuto, { passive: true });

                startAuto();
            });
        })();
    </script>
</x-layouts.stream>
