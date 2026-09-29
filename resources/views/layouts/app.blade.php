<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#523E1A" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#1C1A17" media="(prefers-color-scheme: dark)">
    <meta name="description" content="@yield('meta_description', 'Couture Flow — gestion d\'atelier de couture : clients, mesures, commandes, caisse et planning.')">

    <title>@yield('title', 'Tableau de bord') — {{ config('app.name') }}</title>

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
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-brand-50 font-sans text-brand-900 antialiased dark:bg-brand-dark dark:text-brand-50">
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('sidebar', { open: false });
        });
    </script>
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg focus:bg-brand-800 focus:px-4 focus:py-2 focus:text-white">
        Aller au contenu principal
    </a>

    <div class="min-h-screen flex">
        @include('layouts.partials.sidebar')

        {{--
            La barre latérale est en position fixe dès lg : sans ce décalage,
            elle passerait sous le contenu. La largeur w-72 du <aside> est donc
            reprise ici. Sous lg, elle est hors écran (-translate-x-full) et
            aucun décalage n'est nécessaire.
        --}}
        <div class="flex min-w-0 flex-1 flex-col lg:pl-72">
            @include('layouts.partials.header')

            <main id="main-content" class="flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <div class="mx-auto w-full max-w-7xl">
                    <x-flash />
                    @yield('content')
                </div>
            </main>

            @include('layouts.partials.footer')
        </div>
    </div>

    <!-- Mobile sidebar overlay -->
    <div
        x-cloak
        x-show="$store.sidebar.open"
        @click="$store.sidebar.open = false"
        class="fixed inset-0 z-40 bg-black/50 dark:bg-black/70 lg:hidden"
    ></div>

    @include('layouts.partials.confirm-modal')

    @include('layouts.partials.notification-popup')

    @include('layouts.partials.installation-prompt')

    @stack('modals')
    @stack('scripts')
</body>
</html>
