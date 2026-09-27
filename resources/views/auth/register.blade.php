<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#523E1A">
    <meta name="robots" content="noindex, nofollow">

    <title>Créer mon atelier — {{ config('app.name') }}</title>

    <link rel="icon" href="/images/icons/favicon.svg" type="image/svg+xml">
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
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-brand-50 font-sans text-brand-900 antialiased dark:bg-brand-dark dark:text-brand-50">
    <header class="sticky top-0 z-30 border-b border-brand-200/70 bg-white/85 backdrop-blur-md dark:border-white/10 dark:bg-brand-dark/85">
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4 sm:px-6">
            <a href="{{ route('accueil') }}" class="flex items-center gap-2.5">
                <span class="flex size-9 items-center justify-center rounded-lg bg-brand-800 text-brand-50 dark:bg-brand-400 dark:text-brand-900">
                    <i class="fa-solid fa-scissors" aria-hidden="true"></i>
                </span>
                <span class="font-serif text-lg font-semibold tracking-tight">Couture Flow</span>
            </a>

            <div class="ml-auto flex items-center gap-2">
                <button
                    type="button"
                    x-data="themeToggle"
                    @click="toggle()"
                    class="flex size-9 items-center justify-center rounded-lg border border-brand-200 text-brand-700 transition hover:bg-brand-50 dark:border-white/10 dark:text-brand-200 dark:hover:bg-white/10"
                    aria-label="Basculer le mode sombre"
                >
                    <i class="fa-solid fa-moon dark:hidden" aria-hidden="true"></i>
                    <i class="fa-solid fa-sun hidden dark:inline" aria-hidden="true"></i>
                </button>

                <a href="{{ route('login') }}" class="cf-btn-secondary">Connexion</a>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-12">
        <div class="mx-auto max-w-2xl text-center">
            <h1 class="font-serif text-3xl font-semibold tracking-tight sm:text-4xl">Créer mon atelier</h1>
            <p class="mx-auto mt-3 max-w-lg text-brand-600 dark:text-brand-300">
                Remplissez les informations de votre atelier, réglez votre abonnement par Wave et
                transmettez votre reçu. Votre accès est ouvert dès validation.
            </p>
        </div>

        <x-flash />

        <form
            method="POST"
            action="{{ route('register') }}"
            enctype="multipart/form-data"
            class="mx-auto mt-10 max-w-3xl space-y-6"
        >
            @csrf

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="flex items-center gap-2.5 font-serif text-lg font-semibold">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-brand-100 text-xs font-semibold text-brand-700 dark:bg-brand-500/15 dark:text-brand-200">1</span>
                        Informations de l'atelier
                    </h2>
                </div>

                <div class="grid gap-5 p-5 sm:grid-cols-2">
                    <x-form.text
                        class="sm:col-span-2"
                        name="atelier_nom"
                        label="Nom de l'atelier"
                        placeholder="Atelier Couture Diallo"
                        autocomplete="organization"
                        required
                        autofocus
                    />

                    <x-form.text
                        name="nom"
                        label="Nom du responsable"
                        placeholder="Awa Diallo"
                        autocomplete="name"
                        required
                    />

                    <x-form.text
                        name="telephone"
                        label="Téléphone"
                        type="tel"
                        placeholder="77 123 45 67"
                        autocomplete="tel"
                        required
                    />

                    <x-form.text
                        class="sm:col-span-2"
                        name="email"
                        label="Adresse email"
                        type="email"
                        placeholder="atelier@exemple.com"
                        autocomplete="email"
                        hint="Elle servira à vous connecter et à recevoir les notifications."
                        required
                    />

                    <x-form.text
                        name="adresse"
                        label="Adresse"
                        placeholder="Rue 12, Dakar"
                        autocomplete="street-address"
                    />

                    <x-form.text
                        name="ville"
                        label="Ville"
                        placeholder="Dakar"
                        autocomplete="address-level2"
                    />
                </div>
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="flex items-center gap-2.5 font-serif text-lg font-semibold">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-brand-100 text-xs font-semibold text-brand-700 dark:bg-brand-500/15 dark:text-brand-200">2</span>
                        Paiement de l'abonnement
                    </h2>
                </div>

                <div class="space-y-5 p-5">
                    <div class="rounded-xl border border-brand-300 bg-brand-100/60 p-5 dark:border-brand-500/30 dark:bg-white/5">
                        <p class="text-xs font-semibold tracking-wide text-brand-700 uppercase dark:text-brand-200">
                            Étape 1 — Effectuez le paiement depuis votre application Wave
                        </p>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-lg border border-brand-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                                <p class="text-xs text-brand-600 dark:text-brand-300">Numéro Wave à payer</p>
                                <p class="mt-1.5 font-serif text-2xl font-semibold tracking-tight tabular-nums">
                                    {{ $wave['number'] }}
                                </p>
                            </div>

                            <div class="rounded-lg border border-brand-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                                <p class="text-xs text-brand-600 dark:text-brand-300">Montant</p>
                                <p class="mt-1.5 font-serif text-2xl font-semibold tracking-tight tabular-nums">
                                    {{ number_format($subscription['amount'], 0, ',', ' ') }}
                                    <span class="font-sans text-sm font-normal">{{ config('coutureflow.currency') }}</span>
                                </p>
                                <p class="mt-1 text-xs text-brand-500 dark:text-brand-400">
                                    {{ $subscription['label'] }} — {{ $subscription['months'] }} mois
                                </p>
                            </div>
                        </div>

<p class="text-xs font-semibold tracking-wide text-brand-700 uppercase dark:text-brand-200">
                            <i class="fa-solid fa-circle-info mt-0.5" aria-hidden="true"></i>
                            Versez au numéro <strong class="font-semibold">{{ $wave['number'] }}</strong>
                            au nom de <strong class="font-semibold">{{ $wave['name'] }}</strong>,
                            puis procedez à l'étape 2.
                        </p>
                    </div>

                    <x-form.file
                        name="preuve"
                        label="Étape 2 — Capture d'écran du reçu Wave"
                        accept="image/jpeg,image/png,image/webp,application/pdf"
                        :max-kb="$maxKb"
                        hint="Formats acceptés : JPG, PNG, WEBP ou PDF — {{ round($maxKb / 1024) }} Mo maximum."
                        required
                    />

                    <p class="text-xs text-brand-600/80 dark:text-brand-300/80">
                        Nous ne contactons pas votre numéro : l'administrateur vérifie uniquement votre
                        capture d'écran. La date de paiement est enregistrée automatiquement.
                    </p>
                </div>
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="flex items-center gap-2.5 font-serif text-lg font-semibold">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-brand-100 text-xs font-semibold text-brand-700 dark:bg-brand-500/15 dark:text-brand-200">3</span>
                        Sécurité du compte
                    </h2>
                </div>

                <div class="grid gap-5 p-5 sm:grid-cols-2">
                    <x-form.text
                        name="password"
                        label="Mot de passe"
                        type="password"
                        autocomplete="new-password"
                        hint="8 caractères minimum."
                        required
                    />

                    <x-form.text
                        name="password_confirmation"
                        label="Confirmer le mot de passe"
                        type="password"
                        autocomplete="new-password"
                        required
                    />

                    <div class="sm:col-span-2">
                        <label class="flex cursor-pointer items-start gap-2.5 text-sm text-brand-700 dark:text-brand-200">
                            <input
                                type="checkbox"
                                name="conditions"
                                value="1"
                                required
                                class="mt-0.5 size-4 rounded border-brand-300 text-brand-700 focus:ring-brand-500 dark:border-white/20 dark:bg-white/5"
                            >
                            <span>
                                Je certifie que les informations fournies sont exactes et j'accepte que mes
                                données soient utilisées pour la gestion de mon atelier.
                            </span>
                        </label>
                        @error('conditions')
                            <p class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-red-600 dark:text-red-400">
                                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </section>

            <div class="rounded-xl border border-brand-200 bg-white p-4 dark:border-white/10 dark:bg-brand-dark">
                <p class="flex items-start gap-2.5 text-sm text-brand-700 dark:text-brand-200">
                    <i class="fa-solid fa-shield-halved mt-0.5 text-brand-500" aria-hidden="true"></i>
                    Votre preuve de paiement est stockée de manière sécurisée et n'est visible que par vous et
                    l'administrateur chargé de la vérification.
                </p>
            </div>

            {{--
                Bouton d'envoi : le libellé était « Envoyer mon inscription », trop
                long et trop large sur un écran de téléphone. « Créer mon compte »
                dit la même chose en occupant moins de place. Le px-6 n'est
                appliqué qu'à partir de sm : sur mobile, le bouton garde la
                largeur de la colonne mais sans l'élargissement supplémentaire.
            --}}
            <div class="flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end sm:gap-3">
                <a href="{{ route('accueil') }}" class="cf-btn-secondary justify-center">Annuler</a>
                <button type="submit" class="cf-btn-primary w-full justify-center sm:w-auto sm:px-6">
                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                    Créer mon compte
                </button>
            </div>
        </form>
    </main>

    <footer class="px-4 py-8 text-center text-xs text-brand-500 dark:text-brand-400">
        © {{ now()->year }} {{ config('app.name') }}
    </footer>
</body>
</html>
