@props(['variant' => 'red-black'])

@php
    $palette = match ($variant) {
        'red-green' => [
            'left' => 'from-red-600 via-red-500 to-red-400',
            'right' => 'from-emerald-500 via-emerald-400 to-emerald-300',
            'dot' => 'bg-emerald-400 shadow-[0_0_20px_rgba(16,185,129,0.65)]',
        ],
        'green-black' => [
            'left' => 'from-emerald-600 via-emerald-500 to-emerald-400',
            'right' => 'from-zinc-900 via-zinc-700 to-zinc-500 dark:from-zinc-100 dark:via-zinc-200 dark:to-zinc-300',
            'dot' => 'bg-red-500 shadow-[0_0_20px_rgba(239,68,68,0.65)]',
        ],
        default => [
            'left' => 'from-red-600 via-red-500 to-red-400',
            'right' => 'from-zinc-900 via-zinc-700 to-zinc-500 dark:from-zinc-100 dark:via-zinc-200 dark:to-zinc-300',
            'dot' => 'bg-red-500 shadow-[0_0_20px_rgba(239,68,68,0.65)]',
        ],
    };
@endphp

<span
    role="img"
    aria-label="VJPrime logo"
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 align-middle font-black uppercase tracking-[0.14em] leading-none']) }}
>
    <span class="bg-gradient-to-r {{ $palette['left'] }} bg-clip-text text-transparent">VJ</span>
    <span class="bg-gradient-to-r {{ $palette['right'] }} bg-clip-text text-transparent">Prime</span>
    <span class="h-1.5 w-1.5 rounded-full {{ $palette['dot'] }}"></span>
</span>
