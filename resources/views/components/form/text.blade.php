@props([
    'name' => 'Champ',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'required' => false,
    'autocomplete' => null,
    'placeholder' => null,
    'hint' => null,
    'inputClass' => null,
    'prefix' => null,
    'suffix' => null,
])

@php
    $id = $attributes->get('id', 'field-'.\Illuminate\Support\Str::slug($name));
    $hasError = $errors->has($name);
    $labelText = $label ?? $name;
    $inputAttributes = $attributes->except(['class', 'id', 'input-class', 'prefix', 'suffix']);
    $isFile = $type === 'file';
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'w-full']) }}>
    <label for="{{ $id }}" class="cf-label">
        {{ $labelText }}
        @if ($required)
            <span class="text-brand-accent" aria-hidden="true">*</span>
        @endif
    </label>

    <div @class(['relative' => $prefix || $suffix])>
        @if ($prefix)
            <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-sm font-medium text-brand-500">
                {{ $prefix }}
            </span>
        @endif

        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="{{ $type }}"
            @unless ($isFile) value="{{ old($name, $value) }}" @endunless
            @if ($required) required @endif
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{ $inputAttributes->class([
                'cf-input' => ! $isFile,
                'file:mr-3 file:rounded-md file:border-0 file:bg-brand-100 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-brand-800 hover:file:bg-brand-200 dark:file:bg-white/10 dark:file:text-brand-100' => $isFile,
                $prefix ? 'pl-11' : '',
                $suffix ? 'pr-11' : '',
            ]) }}
        />

        @if ($suffix)
            <span class="pointer-events-none absolute inset-y-0 right-3.5 flex items-center text-sm font-medium text-brand-500">
                {{ $suffix }}
            </span>
        @endif
    </div>

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
