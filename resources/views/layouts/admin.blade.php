<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1C1A17">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', 'Administration') — {{ config('app.name') }}</title>

    <link rel="manifest" href="/manifest.json">
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
    @stack('head')
</head>
<body class="min-h-screen bg-brand-50 font-sans text-brand-900 antialiased dark:bg-brand-dark dark:text-brand-50">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg focus:bg-brand-800 focus:px-4 focus:py-2 focus:text-white">
        Aller au contenu principal
    </a>

    <div class="min-h-screen lg:flex">
        @include('layouts.partials.admin-sidebar')

        <div class="flex min-w-0 flex-1 flex-col">
            @include('layouts.partials.admin-header')

            <main id="main-content" class="flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <div class="mx-auto w-full max-w-7xl">
                    <x-flash />
                    @yield('content')
                </div>
            </main>

            <footer class="border-t border-brand-200/70 px-4 py-5 text-center text-xs text-brand-600/70 sm:px-6 dark:border-white/10 dark:text-brand-300/60">
                {{ config('app.name') }} — Espace administrateur
            </footer>
        </div>
    </div>

    @include('layouts.partials.confirm-modal')

    @stack('modals')
    @stack('scripts')
</body>
</html>
