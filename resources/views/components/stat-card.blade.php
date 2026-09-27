@props([
    'label' => 'Statistique',
    'value' => '—',
    'icon' => 'fa-solid fa-chart-simple',
    'hint' => null,
    'tone' => 'brand',
    'href' => null,
])

@php
    $tones = [
        'brand' => 'bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-200',
        'emerald' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
        'amber' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
        'red' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300',
        'blue' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300',
    ];

    $toneClass = $tones[$tone] ?? $tones['brand'];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'block rounded-xl border border-brand-200/70 bg-white p-5 shadow-card transition dark:border-white/10 dark:bg-brand-dark'.($href ? ' hover:-translate-y-0.5 hover:shadow-elevated' : '')]) }}
>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">{{ $label }}</p>
            <p class="mt-2 font-serif text-2xl font-semibold tracking-tight tabular-nums sm:text-3xl">{{ $value }}</p>
            @if ($hint)
                <p class="mt-1.5 truncate text-xs text-brand-500 dark:text-brand-400">{{ $hint }}</p>
            @endif
        </div>

        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl {{ $toneClass }}">
            <i class="{{ $icon }} text-base" aria-hidden="true"></i>
        </span>
    </div>
</{{ $tag }}>
