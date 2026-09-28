{{--
    Notifications arrivées depuis l'ouverture de la page.

    Le son ne suffit pas : un atelier travaille souvent en commutant entre
    d'autres onglets, et l'onglet.background ne montre aucune pastille. Ces
    bannières posent l'information à l'écran, se referment seules et
    disparaissent à la main.

    Elles sont rendues même sans Alpine : sans lui, la liste reste vide et
    rien ne s'affiche.
--}}
<div
    x-cloak
    class="pointer-events-none fixed top-20 right-4 z-50 flex w-[min(22rem,calc(100vw-2rem))] flex-col gap-2"
    aria-live="polite"
    role="status"
>
    <template x-for="toast in $store.notifications.toasts" :key="toast.id">
        <div
            class="pointer-events-auto flex items-start gap-3 rounded-xl border border-brand-200 bg-white p-3 shadow-elevated dark:border-white/10 dark:bg-brand-dark"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-2 opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            <span
                class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg"
                x-bind:class="toast.ton"
            >
                <i class="text-xs" x-bind:class="toast.icone" aria-hidden="true"></i>
            </span>

            <a x-bind:href="toast.url" class="min-w-0 flex-1">
                <span class="block truncate text-sm font-semibold" x-text="toast.titre"></span>
                <span class="mt-0.5 block text-xs text-brand-600 dark:text-brand-300" x-text="toast.message"></span>
                <span class="mt-1 block text-[0.65rem] text-brand-400" x-text="toast.quand"></span>
            </a>

            <button
                type="button"
                class="flex size-6 shrink-0 items-center justify-center rounded-md text-brand-400 transition hover:bg-brand-100 hover:text-brand-700 dark:hover:bg-white/10 dark:hover:text-brand-100"
                x-on:click="$store.notifications.fermer(toast.id)"
                aria-label="Fermer la notification"
            >
                <i class="fa-solid fa-xmark text-xs" aria-hidden="true"></i>
            </button>
        </div>
    </template>
</div>
