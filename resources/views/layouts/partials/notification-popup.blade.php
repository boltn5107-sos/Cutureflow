{{--
    Messages de notification arrivés depuis l'ouverture de la page.

    Chaque nouvelle notification s'ouvre dans un popup central, à la place
    des anciennes bannières fugaces en coin : un atelier occupé ne doit pas
    rater un message parce qu'il a mis quelques secondes à tourner la tête.

    Le popup ne se referme qu'au clic (fond, bouton, touche « Échap »), sans
    minuterie de fermeture : la file en attente s'affiche l'une après l'autre,
    et rien n'est évacué tant que chaque message n'a pas été vu. Il est rendu
    même sans Alpine : sans lui, la file reste vide et rien ne s'affiche.
--}}
<div
    x-cloak
    x-show="$store.notifications.messages.length > 0"
    x-transition.opacity
    class="fixed inset-0 z-50 bg-brand-900/40 backdrop-blur-sm"
    @click="$store.notifications.fermer()"
></div>

<div
    x-cloak
    x-show="$store.notifications.messages.length > 0"
    x-transition
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-label="Notification"
    data-notifications-popup
    @keydown.escape.window="$store.notifications.fermer()"
>
    <template x-if="$store.notifications.actuel">
        <div class="w-full max-w-md overflow-hidden rounded-xl border border-brand-200 bg-white shadow-elevated dark:border-white/10 dark:bg-brand-dark">
            <div class="flex gap-4 p-6">
                <span
                    class="mt-0.5 flex size-11 shrink-0 items-center justify-center rounded-lg"
                    x-bind:class="$store.notifications.actuel.ton"
                >
                    <i class="text-base" x-bind:class="$store.notifications.actuel.icone" aria-hidden="true"></i>
                </span>

                <div class="min-w-0 flex-1">
                    <h3 class="font-serif text-lg font-semibold" x-text="$store.notifications.actuel.titre"></h3>
                    <p class="mt-1.5 text-sm text-brand-600 dark:text-brand-300" x-text="$store.notifications.actuel.message"></p>
                    <p class="mt-2 text-[0.65rem] text-brand-400" x-text="$store.notifications.actuel.quand"></p>
                </div>

                <button
                    type="button"
                    class="flex size-6 shrink-0 items-center justify-center rounded-md text-brand-400 transition hover:bg-brand-100 hover:text-brand-700 dark:hover:bg-white/10 dark:hover:text-brand-100"
                    x-on:click="$store.notifications.fermer()"
                    aria-label="Fermer la notification"
                >
                    <i class="fa-solid fa-xmark text-sm" aria-hidden="true"></i>
                </button>
            </div>

            <div class="flex items-center gap-2 border-t border-brand-200 bg-brand-50/60 px-6 py-4 dark:border-white/10 dark:bg-white/[0.02]">
                <button
                    type="button"
                    class="cf-btn-secondary cf-btn-sm ml-auto"
                    x-on:click="$store.notifications.fermer()"
                >
                    Plus tard
                </button>
                <a
                    x-bind:href="$store.notifications.actuel.url"
                    class="cf-btn-primary cf-btn-sm"
                    x-on:click="$store.notifications.fermer()"
                >
                    Voir
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </template>
</div>