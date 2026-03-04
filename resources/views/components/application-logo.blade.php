@props([
    'theme' => 'light',
    'tagline' => false,
])

@php
    $primeColor = $theme === 'dark' ? 'text-white' : 'text-[#0d0b10]';
    $taglineColor = $theme === 'dark' ? 'text-slate-300/90' : 'text-zinc-500';
@endphp

<span
    role="img"
    aria-label="VJPrime logo"
    {{ $attributes->merge(['class' => 'inline-flex flex-col items-center align-middle leading-none']) }}
>
    <span class="relative inline-flex items-end font-black italic tracking-[-0.05em]">
        <span class="bg-gradient-to-b from-red-500 to-red-700 bg-clip-text text-transparent">VJ</span><span class="{{ $primeColor }}">Prime</span>
        <span class="pointer-events-none absolute left-[2.23em] top-[0.34em] inline-flex h-[0.72em] w-[0.72em] items-center justify-center rounded-full bg-gradient-to-b from-red-500 to-red-700 shadow-[0_0_0_0.06em_rgba(255,255,255,0.75)]">
            <span class="ml-[0.06em] h-0 w-0 border-y-[0.16em] border-y-transparent border-l-[0.24em] border-l-white"></span>
        </span>
    </span>

    @if ($tagline)
        <span class="mt-2 text-[0.28em] font-semibold uppercase tracking-[0.2em] {{ $taglineColor }}">
            #1 Home for Translated Movies
        </span>
    @endif
</span>
