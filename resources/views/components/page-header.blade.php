@props([
    'title' => null,
    'subtitle' => null,
    'back' => null,
])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-wrap items-start justify-between gap-4']) }}>
    <div class="min-w-0">
        @if ($back)
            <a
                href="{{ $back }}"
                class="mb-2 inline-flex items-center gap-1.5 text-xs font-medium text-brand-600 transition hover:text-brand-800 dark:text-brand-300 dark:hover:text-brand-100"
            >
                <i class="fa-solid fa-arrow-left text-[0.7rem]" aria-hidden="true"></i>
                Retour
            </a>
        @endif

        <h1 class="font-serif text-2xl font-semibold tracking-tight sm:text-3xl">{{ $title ?? $slot ?? '' }}</h1>

        @if ($subtitle)
            <p class="mt-1.5 text-sm text-brand-600 dark:text-brand-300">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
