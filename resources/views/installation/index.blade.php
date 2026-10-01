<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#523E1A">
    <meta name="robots" content="noindex, nofollow">

    <title>Installation — {{ config('app.name') }}</title>

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
    <main class="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-14">
        <div class="text-center">
            <x-app-logo size="size-11" />

            <h1 class="mt-5 font-serif text-3xl font-semibold tracking-tight sm:text-4xl">
                Installer Couture+
            </h1>

            <p class="mx-auto mt-3 max-w-xl text-brand-600 dark:text-brand-300">
                Cette page apparaît uniquement tant que l'application n'est pas configurée.
                Vérifiez l'environnement, renseignez la base de données et créez votre
                compte administrateur.
            </p>
        </div>

        <x-flash />

        @if ($errors->any())
            <div class="mt-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3.5 dark:border-red-500/30 dark:bg-red-500/10" role="alert">
                <i class="fa-solid fa-circle-exclamation mt-0.5 text-sm text-red-700 dark:text-red-300" aria-hidden="true"></i>
                <div class="flex-1 text-sm font-medium text-red-700 dark:text-red-300">
                    Le formulaire contient des erreurs. Corrigez les champs signalés ci-dessous.
                </div>
            </div>
        @endif

        {{-- (1) Vérifications d'environnement --}}
        <section class="cf-card mt-8 overflow-hidden">
            <div class="cf-card-header">
                <h2 class="flex items-center gap-2.5 font-serif text-lg font-semibold">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-brand-100 text-xs font-semibold text-brand-700 dark:bg-brand-500/15 dark:text-brand-200">1</span>
                    Vérifications
                </h2>

                <span class="cf-badge {{ $pret ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-300' }}">
                    <i class="{{ $pret ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark' }} text-[0.7em]" aria-hidden="true"></i>
                    {{ $pret ? 'Environnement prêt' : 'Action requise' }}
                </span>
            </div>

            <ul class="divide-y divide-brand-100 dark:divide-white/5">
                @foreach ($diagnostics as $controle)
                    <li class="flex items-start gap-3 px-5 py-3">
                        <i class="{{ $controle['ok'] ? 'fa-solid fa-circle-check text-emerald-600' : 'fa-solid fa-circle-xmark text-red-600' }} mt-0.5 text-sm" aria-hidden="true"></i>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium">{{ $controle['libelle'] }}</p>
                            <p class="text-xs text-brand-600 dark:text-brand-300">{{ $controle['detail'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>

        <form method="POST" action="{{ route('installation.store') }}">
            @csrf

            {{-- (2) Application et base de données --}}
            <section class="cf-card mt-6 overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="flex items-center gap-2.5 font-serif text-lg font-semibold">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-brand-100 text-xs font-semibold text-brand-700 dark:bg-brand-500/15 dark:text-brand-200">2</span>
                        Application et base de données
                    </h2>
                </div>

                <div class="grid gap-5 p-5 sm:grid-cols-2">
                    <x-form.text
                        name="app_nom"
                        label="Nom de l'application"
                        :value="old('app_nom', config('app.name'))"
                        placeholder="Couture+"
                        required
                    />

                    <x-form.text
                        name="app_url"
                        label="URL de l'application"
                        type="url"
                        :value="old('app_url', config('app.url'))"
                        placeholder="https://coutureflow.app"
                        required
                    />

                    <x-form.select
                        class="sm:col-span-2"
                        name="db_driver"
                        label="Type de base de données"
                        :options="['mysql' => 'MySQL / MariaDB']"
                        :value="old('db_driver', 'mysql')"
                        :empty="false"
                        required
                    />

                    <x-form.text
                        name="db_host"
                        label="Hôte"
                        :value="old('db_host', '127.0.0.1')"
                        placeholder="127.0.0.1"
                        required
                    />

                    <x-form.text
                        name="db_port"
                        label="Port"
                        type="text"
                        :value="old('db_port', '3306')"
                        placeholder="3306"
                        required
                    />

                    <x-form.text
                        name="db_database"
                        label="Nom de la base de données"
                        :value="old('db_database')"
                        placeholder="couture_flow"
                        required
                    />

                    <x-form.text
                        name="db_username"
                        label="Identifiant MySQL"
                        :value="old('db_username')"
                        placeholder="root"
                        autocomplete="off"
                        required
                    />

                    <x-form.text
                        class="sm:col-span-2"
                        name="db_password"
                        label="Mot de passe MySQL"
                        type="password"
                        autocomplete="new-password"
                        hint="Laissez vide si votre identifiant MySQL n'utilise pas de mot de passe."
                    />
                </div>

                <div class="border-t border-brand-200/70 px-5 py-4 dark:border-white/10">
                    <button type="submit" name="action" value="tester" class="cf-btn-secondary">
                        <i class="fa-solid fa-plug-circle-check" aria-hidden="true"></i>
                        Tester la connexion
                    </button>

                    @isset($test)
                        <p class="mt-3 flex items-start gap-2 text-sm {{ $test['ok'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                            <i class="{{ $test['ok'] ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-exclamation' }} mt-0.5" aria-hidden="true"></i>
                            {{ $test['message'] }}
                        </p>
                    @endisset
                </div>
            </section>

            {{-- (3) Compte administrateur --}}
            <section class="cf-card mt-6 overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="flex items-center gap-2.5 font-serif text-lg font-semibold">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-brand-100 text-xs font-semibold text-brand-700 dark:bg-brand-500/15 dark:text-brand-200">3</span>
                        Compte administrateur
                    </h2>
                </div>

                <div class="grid gap-5 p-5 sm:grid-cols-2">
                    <x-form.text
                        name="admin_nom"
                        label="Nom complet"
                        :value="old('admin_nom')"
                        placeholder="Awa Diallo"
                        autocomplete="name"
                        required
                    />

                    <x-form.text
                        name="admin_email"
                        label="Adresse email"
                        type="email"
                        :value="old('admin_email')"
                        placeholder="admin@coutureflow.app"
                        autocomplete="username"
                        required
                    />

                    <x-form.text
                        class="sm:col-span-2"
                        name="admin_telephone"
                        label="Téléphone"
                        type="tel"
                        :value="old('admin_telephone')"
                        placeholder="77 123 45 67"
                        autocomplete="tel"
                    />

                    <x-form.text
                        name="admin_password"
                        label="Mot de passe"
                        type="password"
                        autocomplete="new-password"
                        hint="8 caractères minimum."
                        required
                    />

                    <x-form.text
                        name="admin_password_confirmation"
                        label="Confirmer le mot de passe"
                        type="password"
                        autocomplete="new-password"
                        required
                    />
                </div>
            </section>

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/30 dark:bg-amber-500/10">
                <p class="flex items-start gap-2.5 text-sm text-amber-800 dark:text-amber-200">
                    <i class="fa-solid fa-lock mt-0.5" aria-hidden="true"></i>
                    Cette page devient inaccessible dès la fin de l'installation : aucun visiteur
                    ne pourra réinitialiser la base ni créer un second administrateur.
                </p>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="cf-btn-primary px-6">
                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                    Installer et créer mon compte
                </button>
            </div>
        </form>
    </main>
</body>
</html>
