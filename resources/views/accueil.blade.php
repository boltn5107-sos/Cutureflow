<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#523E1A">
    <meta name="description" content="Couture+ — le logiciel de gestion pensé pour les ateliers de couture : clients, mesures, commandes, caisse et planning.">
    <meta name="robots" content="noindex, follow">

    <title>{{ config('app.name') }} — Gestion d'atelier de couture</title>

    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/images/icons/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/images/icons/icon-192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <script>
        (function () {
            try {
                var stored = localStorage.getItem('cf-theme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (stored === 'dark' || (!stored && prefersDark)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}

            // Mêmes règles que le layout application : la bannière
            // d'installation reste visible sur chaque page tant que rien ne
            // l'a masquée pour la session en cours (mode autonome ou écarte
            // explicite).
            try {
                if (window.matchMedia('(display-mode: standalone)').matches
                    || sessionStorage.getItem('cf-install-propose') === '1') {
                    document.documentElement.classList.add('cf-installe');
                }
            } catch (e) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    x-data
    class="min-h-screen bg-brand-50 font-sans text-brand-900 antialiased dark:bg-brand-dark dark:text-brand-50"
>
    <header class="sticky top-0 z-30 border-b border-brand-200/70 bg-white/85 backdrop-blur-md dark:border-white/10 dark:bg-brand-dark/85">
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4 sm:px-6">
            <a href="{{ route('accueil') }}" class="flex items-center gap-2.5">
                <x-app-logo />
                <span class="font-serif text-lg font-semibold tracking-tight">Couture+</span>
            </a>

            <nav class="ml-auto hidden items-center gap-7 text-sm font-medium text-brand-700 md:flex dark:text-brand-200">
                <a href="#fonctionnalites" class="transition hover:text-brand-900 dark:hover:text-brand-50">Fonctionnalités</a>
                <a href="#abonnement" class="transition hover:text-brand-900 dark:hover:text-brand-50">Abonnement</a>
            </nav>

          <div class="ml-auto flex items-center gap-2 md:ml-0">
    @auth
        <a href="{{ route(auth()->user()->isAdmin() ? 'admin.dashboard' : 'dashboard') }}" class="cf-btn-primary">
            <i class="fa-solid fa-arrow-right-to-bracket" aria-hidden="true"></i>
            <span class="hidden sm:inline">Mon espace</span>
        </a>
    @else
        <a href="{{ route('login') }}" class="cf-btn-secondary">
            Connexion
        </a>
        <a href="{{ route('register') }}" class="cf-btn-primary">
            <span class="hidden xs:inline">Créer mon atelier</span>
            <span class="xs:hidden">Créer</span>
        </a>
    @endauth
</div>
        </div>
    </header>

    <main>
        <section class="relative overflow-hidden border-b border-brand-200/70 dark:border-white/10">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(60%_60%_at_50%_0%,rgba(169,135,64,0.14),transparent)]" aria-hidden="true"></div>

            <div class="relative mx-auto max-w-6xl px-4 py-20 sm:px-6 sm:py-28">
                <div class="mx-auto max-w-2xl text-center">
                    <span class="cf-badge mx-auto mb-6 bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-200">
                        <i class="fa-solid fa-scissors text-[0.7em]" aria-hidden="true"></i>
                        Pensé par et pour les ateliers de couture
                    </span>

                    <h1 class="font-serif text-4xl leading-tight font-semibold tracking-tight text-balance sm:text-5xl lg:text-6xl">
                        Tout votre atelier,<br>
                        <span class="text-brand-600 dark:text-brand-400">au même endroit.</span>
                    </h1>

                    <p class="mx-auto mt-6 max-w-xl text-base leading-relaxed text-brand-600 text-pretty sm:text-lg dark:text-brand-300">
                        Clients, mesures, commandes, encaissements et planning. Couture+ remplace le carnet
                        papier et les fichiers dispersés par une gestion simple et rapide, qui reste
                        utilisable avec une connexion lente.
                    </p>

                    <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a href="{{ route('register') }}" class="cf-btn-primary w-full px-6 py-3 sm:w-auto">
                            <i class="fa-solid fa-rocket" aria-hidden="true"></i>
                            Démarrer mon atelier
                        </a>
                        <a href="{{ route('login') }}" class="cf-btn-secondary w-full px-6 py-3 sm:w-auto">
                            <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                            J'ai déjà un compte
                        </a>
                    </div>

                    <p class="mt-5 text-xs text-brand-500 dark:text-brand-400">
                        Abonnement mensuel à {{ number_format(config('coutureflow.subscription.amount'), 0, ',', ' ') }} {{ config('coutureflow.currency') }} — sans engagement.
                    </p>
                </div>
            </div>
        </section>

        <section id="fonctionnalites" class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="font-serif text-3xl font-semibold tracking-tight sm:text-4xl">Une gestion complète</h2>
                <p class="mt-3 text-brand-600 dark:text-brand-300">
                    Chaque module a été conçu avec la réalité d'un atelier : peu de temps, beaucoup de petites pièces.
                </p>
            </div>

            <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['fa-solid fa-users', 'Fiche clients', 'Coordonnées, notes, historique de commandes, règlements et fiche de mesures complète.'],
                    ['fa-solid fa-ruler-combined', 'Mesures', 'Toutes les mesures courantes, plus vos propres mesures personnalisées, conservées dans le temps.'],
                    ['fa-solid fa-scissors', 'Commandes', 'Numérotation automatique, prix, avance, solde calculé et suivi des retards de livraison.'],
                    ['fa-solid fa-wallet', 'Caisse', 'Avances, soldes, recettes, dépenses : recettes, dépenses et bénéfice en un coup d\'oeil.'],
                    ['fa-regular fa-calendar', 'Planning', 'Rendez-vous, essayages et livraisons dans un calendrier simple et lisible.'],
                    ['fa-solid fa-book-open', 'Catalogue', 'Vos modèles avec photos, catégories et prix indicatifs, réutilisables à chaque commande.'],
                ] as [$icon, $titre, $texte])
                    <article class="cf-card p-6">
                        <span class="flex size-11 items-center justify-center rounded-xl bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-200">
                            <i class="{{ $icon }}" aria-hidden="true"></i>
                        </span>
                        <h3 class="mt-4 font-serif text-lg font-semibold">{{ $titre }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-brand-600 dark:text-brand-300">{{ $texte }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section id="abonnement" class="border-y border-brand-200/70 bg-white dark:border-white/10 dark:bg-white/[0.02]">
            <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
                <div class="grid items-center gap-12 lg:grid-cols-2">
                    <div>
                        <h2 class="font-serif text-3xl font-semibold tracking-tight sm:text-4xl">Un abonnement simple</h2>
                        <p class="mt-4 text-brand-600 dark:text-brand-300">
                            Vous payez chaque mois par Wave. Vous déposez votre reçu, notre équipe le vérifie
                            et votre accès est ouvert. Aucun prélèvement automatique, aucune surprise.
                        </p>

                        <ul class="mt-8 space-y-3">
                            @foreach ([
                                'Accès immédiat après validation de votre paiement',
                                'Toutes les fonctionnalités, sans limite d\'atelier',
                                'Vos données vous appartiennent exclusivement',
                                'Fonctionne sur téléphone, tablette et ordinateur',
                            ] as $avantage)
                                <li class="flex items-start gap-3 text-sm text-brand-700 dark:text-brand-200">
                                    <i class="fa-solid fa-circle-check mt-0.5 text-brand-500" aria-hidden="true"></i>
                                    {{ $avantage }}
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="cf-card overflow-hidden">
                        <div class="border-b border-brand-200/70 bg-brand-800 px-6 py-5 text-brand-50 dark:border-white/10">
                            <p class="text-xs font-medium tracking-wide text-brand-200 uppercase">Abonnement mensuel</p>
                            <p class="mt-2 font-serif text-4xl font-semibold tabular-nums">
                                {{ number_format(config('coutureflow.subscription.amount'), 0, ',', ' ') }}
                                <span class="font-sans text-base font-normal">{{ config('coutureflow.currency') }}</span>
                            </p>
                        </div>

                        <div class="space-y-4 p-6">
                            <div class="flex items-center gap-3">
                                <span class="flex size-9 items-center justify-center rounded-lg bg-brand-100 text-brand-700 dark:bg-white/10 dark:text-brand-200">
                                    <i class="fa-solid fa-mobile-screen" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <p class="text-xs text-brand-500 dark:text-brand-400">Numéro Wave</p>
                                    <p class="font-semibold tabular-nums">{{ config('coutureflow.wave.number') }}</p>
                                </div>
                            </div>

                            <a href="{{ route('register') }}" class="cf-btn-primary w-full">
                                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                Commencer maintenant
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="px-4 py-8 text-center text-xs text-brand-500 dark:text-brand-400">
        © {{ now()->year }} {{ config('app.name') }} — Gestion d'atelier de couture
    </footer>

    @include('layouts.partials.installation-prompt')
    @stack('modals')
</body>
</html>
