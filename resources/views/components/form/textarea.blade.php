@props([
    'name' => 'Message',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'rows' => 3,
    'required' => false,
    'hint' => null,
    'inputClass' => null,
])

@php
    $id = $attributes->get('id', 'textarea-'.\Illuminate\Support\Str::slug($name));
    $hasError = $errors->has($name);
    $labelText = $label ?? $name;
    $textareaAttributes = $attributes->except(['class', 'id', 'input-class']);
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'w-full']) }}>
    <label for="{{ $id }}" class="cf-label">
        {{ $labelText }}
        @if ($required)
            <span class="text-brand-accent" aria-hidden="true">*</span>
        @endif
    </label>

    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($required) required @endif
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $textareaAttributes->class(['cf-input', 'resize-y', $inputClass]) }}
    >{{ old($name, $value) }}</textarea>

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
