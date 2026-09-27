@props(['status', 'icon' => null])

@if ($status)
    <span {{ $attributes->merge(['class' => 'cf-badge '.$status->badgeClass()]) }}>
        <i class="{{ $icon ?? $status->icon() }} text-[0.7em]" aria-hidden="true"></i>
        {{ $status->label() }}
    </span>
@endif
