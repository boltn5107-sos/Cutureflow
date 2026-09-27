@props([
    'name' => 'Sélection',
    'label' => null,
    'options' => [],
    'value' => null,
    'placeholder' => 'Choisir…',
    'required' => false,
    'hint' => null,
    'empty' => true,
    'inputClass' => null,
])

@php
    $id = $attributes->get('id', 'select-'.\Illuminate\Support\Str::slug($name));
    $hasError = $errors->has($name);
    $current = old($name, $value);
    $labelText = $label ?? $name;
    $selectAttributes = $attributes->except(['class', 'id', 'input-class']);
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'w-full']) }}>
    <label for="{{ $id }}" class="cf-label">
        {{ $labelText }}
        @if ($required)
            <span class="text-brand-accent" aria-hidden="true">*</span>
        @endif
    </label>

    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if ($required) required @endif
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $selectAttributes->class(['cf-select', $inputClass]) }}
    >
        @if ($empty)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>

    @if ($hint && ! $hasError)
        <p class="mt-1.5 text-xs text-brand-600/80 dark:text-brand-300/80">{{ $hint }}</p>
    @endif

    @error($name)
        <p id="{{ $id }}-error" class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-red-600 dark:text-red-400">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
            {{ $message }}
        </p>
    @enderror
</div>
