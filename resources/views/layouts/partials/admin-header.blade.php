@php
    $nav = [
        ['route' => 'admin.dashboard', 'label' => 'Vue d\'ensemble', 'icon' => 'fa-solid fa-gauge-high'],
        ['route' => 'admin.utilisateurs.index', 'label' => 'Utilisateurs', 'icon' => 'fa-solid fa-users'],
        ['route' => 'admin.paiements.index', 'label' => 'Paiements', 'icon' => 'fa-solid fa-receipt'],
    ];
@endphp

<header
    class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-brand-800 bg-brand-900 px-4 text-brand-50 sm:px-6 lg:px-8"
    x-data="{ menu: false, profil: false }"
    @keydown.escape.window="menu = false; profil = false"
>
    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 lg:hidden">
        <span class="flex size-9 items-center justify-center rounded-lg bg-brand-400 text-brand-900">
            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
        </span>
        <span class="font-serif text-base font-semibold tracking-tight">Admin</span>
    </a>

    <h1 class="hidden min-w-0 flex-1 truncate font-serif text-xl font-semibold tracking-tight lg:block">
        @yield('header_title', 'Administration')
    </h1>

    <div class="ml-auto flex items-center gap-2">
        <button
            type="button"
            x-data="themeToggle"
            @click="toggle()"
            class="flex size-9 items-center justify-center rounded-lg text-brand-200 transition hover:bg-white/10"
            aria-label="Basculer le mode sombre"
        >
            <i class="fa-solid fa-moon dark:hidden" aria-hidden="true"></i>
            <i class="fa-solid fa-sun hidden dark:inline" aria-hidden="true"></i>
        </button>

        <div class="relative" @click.outside="profil = false">
            <button
                type="button"
                @click="profil = !profil"
                class="flex items-center gap-2.5 rounded-lg py-1.5 pr-2 pl-1.5 transition hover:bg-white/10"
            >
                <span class="flex size-8 items-center justify-center rounded-lg bg-brand-400 text-xs font-semibold text-brand-900">
                    {{ mb_strtoupper(mb_substr(auth()->user()?->name ?? 'AD', 0, 2)) }}
                </span>
                <span class="hidden text-sm font-medium sm:block">{{ auth()->user()?->name }}</span>
                <i class="fa-solid fa-chevron-down text-[0.6rem] text-brand-300" aria-hidden="true"></i>
            </button>

            <div
                x-cloak
                x-show="profil"
                x-transition.origin.top.right
                class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-xl border border-white/10 bg-brand-800 shadow-elevated"
            >
                <a
                    href="{{ route('dashboard') }}"
                    class="flex items-center gap-2.5 px-4 py-2.5 text-sm transition hover:bg-white/10"
                >
                    <i class="fa-solid fa-arrow-left w-4 text-brand-300" aria-hidden="true"></i>
                    Mon atelier
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm text-red-300 transition hover:bg-white/10"
                    >
                        <i class="fa-solid fa-arrow-right-from-bracket w-4" aria-hidden="true"></i>
                        Déconnexion
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

<nav
    class="border-b border-brand-800 bg-brand-900 px-4 pb-3 text-brand-200 lg:hidden"
    x-data="{ open: false }"
>
    <button
        type="button"
        @click="open = !open"
        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition hover:bg-white/10"
    >
        <span class="flex items-center gap-2.5">
            <i class="fa-solid fa-bars text-brand-300" aria-hidden="true"></i>
            Navigation
        </span>
        <i class="fa-solid fa-chevron-down text-[0.6rem] transition" :class="open && 'rotate-180'" aria-hidden="true"></i>
    </button>

    <div x-cloak x-show="open" x-transition class="mt-2 space-y-1">
        @foreach ($nav as $item)
            @php $active = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'); @endphp
            <a
                href="{{ route($item['route']) }}"
                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
                    {{ $active ? 'bg-brand-400 text-brand-900' : 'hover:bg-white/10' }}"
            >
                <i class="{{ $item['icon'] }} w-4 text-center" aria-hidden="true"></i>
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
</nav>
