<div
    x-data
    @keydown.escape.window="$store.confirm.close()"
    class="relative z-50"
>
    <div
        x-cloak
        x-show="$store.confirm.open"
        x-transition.opacity
        class="fixed inset-0 z-50 bg-brand-900/40 backdrop-blur-sm"
        @click="$store.confirm.close()"
    ></div>

    <div
        x-cloak
        x-show="$store.confirm.open"
        x-transition
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cf-confirm-title"
    >
        <div class="w-full max-w-md overflow-hidden rounded-xl border border-brand-200 bg-white shadow-elevated dark:border-white/10 dark:bg-brand-dark">
            <div class="flex gap-4 p-6">
                <span
                    class="flex size-11 shrink-0 items-center justify-center rounded-full"
                    :class="{
                        'bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-400': $store.confirm.tone === 'danger',
                        'bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400': $store.confirm.tone === 'warning',
                        'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-200': $store.confirm.tone === 'brand',
                    }"
                >
                    <i
                        class="fa-solid fa-triangle-exclamation text-lg"
                        :class="{
                            'text-red-600': $store.confirm.tone === 'danger',
                            'text-amber-600': $store.confirm.tone === 'warning',
                            'text-brand-600': $store.confirm.tone === 'brand',
                        }"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="min-w-0 flex-1">
                    <h3 id="cf-confirm-title" class="font-serif text-lg font-semibold" x-text="$store.confirm.title"></h3>
                    <p class="mt-1.5 text-sm text-brand-600 dark:text-brand-300" x-text="$store.confirm.message"></p>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-brand-200 bg-brand-50/60 px-6 py-4 sm:flex-row sm:justify-end dark:border-white/10 dark:bg-white/[0.02]">
                <button type="button" @click="$store.confirm.close()" class="cf-btn-secondary w-full sm:w-auto" x-text="$store.confirm.cancelLabel"></button>
                <button
                    type="button"
                    @click="$store.confirm.confirm()"
                    class="w-full sm:w-auto"
                    :class="$store.confirm.tone === 'danger' ? 'cf-btn-danger' : 'cf-btn-primary'"
                    x-text="$store.confirm.confirmLabel"
                ></button>
            </div>
        </div>
    </div>
</div>
