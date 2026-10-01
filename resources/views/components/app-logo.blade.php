@props([
    'size' => 'size-9',
])

<span {!! $attributes->merge(['class' => 'flex shrink-0 items-center justify-center overflow-hidden rounded-lg border border-brand-200/70 bg-white shadow-sm dark:border-white/10 dark:bg-brand-800 ' . $size]) !!}>
    <img
        src="/images/icons/favicon.svg"
        alt="Logo Couture+"
        class="h-4 w-auto object-contain"
        width="82"
        height="61"
    >
</span>