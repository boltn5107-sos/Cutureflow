<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1C1A17">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', 'Accès') — {{ config('app.name') }}</title>

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
    <div class="flex min-h-screen flex-col">
        <header class="flex items-center justify-between gap-4 px-4 py-4 sm:px-6">
            <a href="{{ route('accueil') }}" class="flex items-center gap-2.5">
                <span class="flex size-9 items-center justify-center rounded-lg bg-brand-800 text-brand-50 dark:bg-brand-400 dark:text-brand-900">
                    <i class="fa-solid fa-scissors" aria-hidden="true"></i>
                </span>
                <span class="font-serif text-lg font-semibold tracking-tight">Couture Flow</span>
            </a>

            <button
                type="button"
                x-data="themeToggle"
                @click="toggle()"
                class="flex size-9 items-center justify-center rounded-lg border border-brand-200 bg-white text-brand-700 transition hover:bg-brand-50 dark:border-white/10 dark:bg-white/5 dark:text-brand-200 dark:hover:bg-white/10"
                aria-label="Basculer le mode sombre"
            >
                <i class="fa-solid fa-moon dark:hidden" aria-hidden="true"></i>
                <i class="fa-solid fa-sun hidden dark:inline" aria-hidden="true"></i>
            </button>
        </header>

        <main class="flex flex-1 items-center justify-center px-4 py-8 sm:px-6">
            <div class="w-full max-w-md">
                <x-flash />
                @yield('content')
            </div>
        </main>

        <footer class="px-4 py-5 text-center text-xs text-brand-600/70 dark:text-brand-300/60">
            © {{ now()->year }} {{ config('app.name') }}
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
