{{--
    Messages de notification arrivés depuis l'ouverture de la page.

    Chaque nouvelle notification s'ouvre dans un toast en haut à droite, qui
    glisse depuis le bord : plus discret qu'un popup central, il laisse
    l'atelier continuer à travailler tout en restant visible.

    Le toast ne se referme qu'au clic (croix, « Plus tard », « Voir », touche
    « Échap »), sans minuterie de fermeture : la file en attente s'affiche
    l'une après l'autre, et rien n'est évacué tant que chaque message n'a pas
    été vu. Il est rendu même sans Alpine : sans lui, la file reste vide et
    rien ne s'affiche.
--}}
<div
    x-cloak
    x-show="$store.notifications.messages.length > 0"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-x-4"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-x-0"
    x-transition:leave-end="opacity-0 translate-x-4"
    class="fixed top-20 right-4 z-50 w-[min(24rem,calc(100vw-2rem))] overflow-hidden rounded-xl border border-brand-200 bg-white shadow-elevated dark:border-white/10 dark:bg-brand-dark"
    role="alert"
    aria-label="Notification"
    data-notifications-popup
    data-notifications-lire="{{ route('notifications.read', '__ID__') }}"
    @keydown.escape.window="$store.notifications.fermer()"
>
    <template x-if="$store.notifications.actuel">
        <div>
            <div class="flex items-start gap-3 p-4">
                <span
                    class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-lg"
                    x-bind:class="$store.notifications.actuel.ton"
                >
                    <i class="text-sm" x-bind:class="$store.notifications.actuel.icone" aria-hidden="true"></i>
                </span>

                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-semibold" x-text="$store.notifications.actuel.titre"></h3>
                    <p class="mt-0.5 text-xs leading-relaxed text-brand-600 dark:text-brand-300" x-text="$store.notifications.actuel.message"></p>
                    <p class="mt-1 text-[0.65rem] text-brand-400" x-text="$store.notifications.actuel.quand"></p>
                </div>

                <button
                    type="button"
                    class="flex size-6 shrink-0 items-center justify-center rounded-md text-brand-400 transition hover:bg-brand-100 hover:text-brand-700 dark:hover:bg-white/10 dark:hover:text-brand-100"
                    x-on:click="$store.notifications.fermer()"
                    aria-label="Fermer la notification"
                >
                    <i class="fa-solid fa-xmark text-xs" aria-hidden="true"></i>
                </button>
            </div>

            <div class="flex items-center gap-2 border-t border-brand-200 bg-brand-50/60 px-4 py-3 dark:border-white/10 dark:bg-white/[0.02]">
                <button
                    type="button"
                    class="cf-btn-secondary cf-btn-sm ml-auto"
                    x-on:click="$store.notifications.fermer()"
                >
                    Plus tard
                </button>
                <a
                    x-bind:href="$store.notifications.lienVoir"
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