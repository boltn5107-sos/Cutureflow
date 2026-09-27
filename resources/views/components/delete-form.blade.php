@props([
    'action',
    'method' => 'DELETE',
    'title' => 'Confirmer la suppression',
    'message' => 'Cette action est définitive.',
    'label' => 'Supprimer',
    'icon' => 'fa-solid fa-trash',
    'class' => 'cf-btn-danger',
])

<div {{ $attributes->merge(['class' => 'inline-flex']) }} x-data>
    <form method="POST" action="{{ $action }}" x-ref="form" class="hidden">
        @csrf
        @method($method)
    </form>

    <button
        type="button"
        class="{{ $class }}"
        @click="$store.confirm.ask({
            title: @js($title),
            message: @js($message),
            confirmLabel: @js($label),
            tone: 'danger',
            onConfirm: () => $refs.form.submit(),
        })"
    >
        <i class="{{ $icon }}" aria-hidden="true"></i>
        {{ $slot->isEmpty() ? $label : $slot }}
    </button>
</div>
