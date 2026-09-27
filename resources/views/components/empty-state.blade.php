@props([
    'icon' => 'fa-regular fa-folder-open',
    'title' => 'Aucun résultat',
    'message' => null,
    'action' => null,
    'actionLabel' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-14 text-center']) }}>
    <span class="flex size-14 items-center justify-center rounded-full bg-brand-100 text-xl text-brand-500 dark:bg-white/5 dark:text-brand-300">
        <i class="{{ $icon }}" aria-hidden="true"></i>
    </span>

    <h3 class="mt-4 font-serif text-lg font-semibold">{{ $title }}</h3>

    @if ($message)
        <p class="mt-1.5 max-w-sm text-sm text-brand-600 dark:text-brand-300">{{ $message }}</p>
    @endif

    @if ($action && $actionLabel)
        <a href="{{ $action }}" class="cf-btn-primary mt-6">
            <i class="fa-solid fa-plus" aria-hidden="true"></i>
            {{ $actionLabel }}
        </a>
    @endif
</div>
