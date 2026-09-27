@props([
    'name' => 'Fichier',
    'label' => null,
    'accept' => 'image/*',
    'maxKb' => 4096,
    'required' => false,
    'hint' => null,
    'placeholder' => 'Cliquez pour choisir un fichier',
    'multiple' => false,
    'preview' => 'file',
])

@php
    $id = $attributes->get('id', 'file-'.\Illuminate\Support\Str::slug($name));
    $hasError = $errors->has($name);
    $alpine = $preview === 'multi' ? 'multiPhotoPreview' : 'filePreview';
    $labelText = $label ?? $name;
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'w-full']) }} x-data="{{ $alpine }}">
    <label for="{{ $id }}" class="cf-label">
        {{ $labelText }}
        @if ($required)
            <span class="text-brand-accent" aria-hidden="true">*</span>
        @endif
    </label>

    <label for="{{ $id }}" class="cf-dropzone cursor-pointer">
        <span class="flex size-11 items-center justify-center rounded-full bg-brand-100 text-brand-600 dark:bg-white/10 dark:text-brand-200">
            <i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>
        </span>

        <span class="mt-3 text-sm font-medium text-brand-800 dark:text-brand-100">{{ $placeholder }}</span>

        @if ($hint)
            <span class="mt-1 text-xs text-brand-500 dark:text-brand-400">{{ $hint }}</span>
        @endif

        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="file"
            @if ($multiple) multiple @endif
            @if ($required) required @endif
            accept="{{ $accept }}"
            data-max-kb="{{ $maxKb }}"
            @if ($preview === 'file') @change="handle($event)" @else @change="handle($event)" @endif
            class="sr-only"
            @if ($hasError) aria-invalid="true" @endif
        />
    </label>

    @if ($preview === 'file')
        <div x-cloak x-show="fileName" class="mt-3 flex items-center gap-3 rounded-lg border border-brand-200 bg-white px-3.5 py-2.5 dark:border-white/10 dark:bg-white/5">
            <i class="fa-solid fa-file-lines text-brand-500" aria-hidden="true"></i>
            <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-medium" x-text="fileName"></span>
                <span class="block text-xs text-brand-500" x-text="fileSize"></span>
            </span>
        </div>
    @else
        <div x-cloak x-show="previews.length" class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
            <template x-for="(item, index) in previews" :key="item.url">
                <div class="group relative overflow-hidden rounded-lg border border-brand-200 dark:border-white/10">
                    <img :src="item.url" :alt="item.name" class="aspect-square w-full object-cover">
                    <span class="block truncate px-2 py-1.5 text-[0.65rem] text-brand-600 dark:text-brand-300" x-text="item.name"></span>
                </div>
            </template>
        </div>
    @endif

    <p x-cloak x-show="error" x-text="error" class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400"></p>

    @error($name)
        <p class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-red-600 dark:text-red-400">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
            {{ $message }}
        </p>
    @enderror
</div>
