{{--
    Rappel d'installation de l'application.

    La bannière est visible PAR DÉFAUT, dès le rendu du HTML. Elle ne dépend
    d'aucun script pour apparaître : en local comme en ligne, après une
    installation puis une désinstallation, elle revient à chaque ouverture.

    Seule la CSS peut la masquer, via la classe « cf-installe » posée sur
    <html> dans deux cas :
      - l'application tourne en mode autonome « standalone » (réellement
        installée) — le script du <head> la pose avant tout rendu ;
      - l'utilisateur l'a écartée (« Plus tard », installation refusée ou
        menée à bien) — le store la pose pour la session en cours, et le
        script du <head> la rejoue à chaque page tant que l'onglet vit.

    Sur iOS Safari le navigateur ne propose aucune installation : le bouton
    du rappel ouvre les étapes « Ajouter à l'écran d'accueil ».
--}}
<div
    class="installation-prompt fixed right-4 bottom-4 z-50 w-[min(24rem,calc(100vw-2rem))] overflow-hidden rounded-xl border border-brand-200 bg-white shadow-elevated dark:border-white/10 dark:bg-brand-dark"
    role="region"
    aria-label="Installer l'application"
    data-installation-prompt
>
    <div class="flex items-start gap-3 p-4">
        <span class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-brand-100 dark:bg-white/10">
            <img
                src="/images/icons/favicon.svg"
                alt="Logo {{ config('app.name') }}"
                class="h-7 w-auto object-contain"
                width="82"
                height="61"
            >
        </span>

        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold">Installer Couture+</p>
            <p class="mt-0.5 text-xs text-brand-600 dark:text-brand-300">
                <template x-if="$store.installation.estIOS">
                    <span>Ajoutez l'application à votre écran d'accueil pour l'ouvrir en plein écran, même hors connexion.</span>
                </template>
                <template x-if="! $store.installation.estIOS">
                    <span>Installez l'application pour l'ouvrir en plein écran, même hors connexion.</span>
                </template>
            </p>
        </div>

        <button
            type="button"
            class="flex size-6 shrink-0 items-center justify-center rounded-md text-brand-400 transition hover:bg-brand-100 hover:text-brand-700 dark:hover:bg-white/10 dark:hover:text-brand-100"
            x-on:click="$store.installation.fermer()"
            aria-label="Fermer le rappel"
        >
            <i class="fa-solid fa-xmark text-xs" aria-hidden="true"></i>
        </button>
    </div>

    <div class="flex items-center justify-end gap-2 border-t border-brand-100 px-4 py-3 dark:border-white/10">
        <button
            type="button"
            class="cf-btn-secondary cf-btn-sm"
            x-on:click="$store.installation.fermer()"
        >
            Plus tard
        </button>
        <button
            type="button"
            class="cf-btn-primary cf-btn-sm"
            x-on:click="$store.installation.installer()"
        >
            <span x-show="$store.installation.prompt">Installer</span>
            <span x-cloak x-show="! $store.installation.prompt">Comment installer</span>
        </button>
    </div>
</div>

@push('modals')
    {{-- Étapes iOS : « Installer » n'a rien à proposer sans « beforeinstallprompt ». --}}
    <div
        x-cloak
        x-show="$store.installation.etapes"
        x-transition.opacity
        class="fixed inset-0 z-50 bg-brand-900/40 backdrop-blur-sm"
        @click="$store.installation.fermerEtapes()"
    ></div>

    <div
        x-cloak
        x-show="$store.installation.etapes"
        x-transition
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-label="Installer l'application sur iPhone ou iPad"
        data-installation-etapes
        @keydown.escape.window="$store.installation.fermerEtapes()"
    >
        <div class="w-full max-w-sm overflow-hidden rounded-xl border border-brand-200 bg-white shadow-elevated dark:border-white/10 dark:bg-brand-dark">
            <div class="px-6 pt-6">
                <h3 class="font-serif text-lg font-semibold">Installer Couture+</h3>
                <p class="mt-1.5 text-sm text-brand-600 dark:text-brand-300">
                    Ajoutez l'application à votre écran d'accueil : elle s'ouvrira comme une app à part entière.
                </p>
            </div>

            <ol class="space-y-4 px-6 py-5">
                <template x-if="$store.installation.estIOS">
                    <div class="space-y-4">
                        <li class="flex items-center gap-3 text-sm text-brand-700 dark:text-brand-200">
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-700 dark:bg-white/10 dark:text-brand-200">1</span>
                            <span>
                                Touchez
                                <span class="inline-flex items-center gap-1.5 font-medium">
                                    <i class="fa-solid fa-share-nodes text-brand-400" aria-hidden="true"></i>
                                    Partager
                                </span>
                                dans Safari.
                            </span>
                        </li>
                        <li class="flex items-center gap-3 text-sm text-brand-700 dark:text-brand-200">
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-700 dark:bg-white/10 dark:text-brand-200">2</span>
                            <span>Choisissez
                                <span class="inline-flex items-center gap-1.5 font-medium">
                                    <i class="fa-solid fa-house-circle-check text-brand-400" aria-hidden="true"></i>
                                    Sur l'écran d'accueil
                                </span>.
                            </span>
                        </li>
                        <li class="flex items-center gap-3 text-sm text-brand-700 dark:text-brand-200">
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-700 dark:bg-white/10 dark:text-brand-200">3</span>
                            <span>Touchez
                                <span class="inline-flex items-center gap-1.5 font-medium">
                                    <i class="fa-solid fa-plus text-brand-400" aria-hidden="true"></i>
                                    Ajouter
                                </span>
                                pour confirmer.
                            </span>
                        </li>
                    </div>
                </template>

                <template x-if="! $store.installation.estIOS">
                    <div class="space-y-4">
                        <li class="flex items-center gap-3 text-sm text-brand-700 dark:text-brand-200">
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-700 dark:bg-white/10 dark:text-brand-200">1</span>
                            <span>
                                Ouvrez le menu
                                <span class="inline-flex items-center gap-1.5 font-medium">
                                    <i class="fa-solid fa-ellipsis-vertical text-brand-400" aria-hidden="true"></i>
                                    du navigateur
                                </span>.
                            </span>
                        </li>
                        <li class="flex items-center gap-3 text-sm text-brand-700 dark:text-brand-200">
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-700 dark:bg-white/10 dark:text-brand-200">2</span>
                            <span>Choisissez
                                <span class="inline-flex items-center gap-1.5 font-medium">
                                    <i class="fa-solid fa-mobile-screen-button text-brand-400" aria-hidden="true"></i>
                                    Installer l'application
                                </span>
                                ou « Ajouter à l'écran d'accueil ».
                            </span>
                        </li>
                        <li class="flex items-center gap-3 text-sm text-brand-700 dark:text-brand-200">
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-700 dark:bg-white/10 dark:text-brand-200">3</span>
                            <span>Confirmez avec
                                <span class="inline-flex items-center gap-1.5 font-medium">
                                    <i class="fa-solid fa-plus text-brand-400" aria-hidden="true"></i>
                                    Installer
                                </span>.
                            </span>
                        </li>
                    </div>
                </template>
            </ol>

            <div class="flex flex-col-reverse gap-2 border-t border-brand-200 bg-brand-50/60 px-6 py-4 sm:flex-row sm:justify-end dark:border-white/10 dark:bg-white/[0.02]">
                <button
                    type="button"
                    class="cf-btn-secondary w-full sm:w-auto"
                    @click="$store.installation.fermerEtapes()"
                >
                    Fermer
                </button>
            </div>
        </div>
    </div>
@endpush