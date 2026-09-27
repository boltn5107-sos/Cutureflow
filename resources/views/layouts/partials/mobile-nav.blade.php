@php
    $items = [
        ['route' => 'dashboard', 'label' => 'Accueil', 'icon' => 'fa-solid fa-gauge-high'],
        ['route' => 'clients.index', 'label' => 'Clients', 'icon' => 'fa-solid fa-users'],
        ['route' => 'commandes.index', 'label' => 'Commandes', 'icon' => 'fa-solid fa-scissors'],
        ['route' => 'caisse.index', 'label' => 'Caisse', 'icon' => 'fa-solid fa-wallet'],
        ['route' => 'planning.index', 'label' => 'Planning', 'icon' => 'fa-regular fa-calendar'],
    ];
@endphp

<nav
    class="fixed inset-x-0 bottom-0 z-40 border-t border-brand-200/70 bg-white/95 backdrop-blur-md lg:hidden dark:border-white/10 dark:bg-brand-dark/95"
    x-data="{ open: false }"
    aria-label="Navigation mobile"
>
    <div class="mx-auto grid max-w-lg grid-cols-5">
        @foreach ($items as $item)
            @php $active = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'); @endphp
            <a
                href="{{ route($item['route']) }}"
                @if ($active) aria-current="page" @endif
                class="flex flex-col items-center gap-1 px-1 py-2.5 text-[0.65rem] font-medium transition
                    {{ $active ? 'text-brand-800 dark:text-brand-200' : 'text-brand-500 dark:text-brand-400' }}"
            >
                <span class="flex h-7 w-11 items-center justify-center rounded-lg transition {{ $active ? 'bg-brand-100 dark:bg-white/10' : '' }}">
                    <i class="{{ $item['icon'] }} text-sm" aria-hidden="true"></i>
                </span>
                {{ $item['label'] }}
            </a>
        @endforeach

        <button
            type="button"
            @click="open = !open"
            class="flex flex-col items-center gap-1 px-1 py-2.5 text-[0.65rem] font-medium text-brand-500 transition dark:text-brand-400"
        >
            <span class="flex h-7 w-11 items-center justify-center rounded-lg">
                <i class="fa-solid fa-ellipsis text-sm" aria-hidden="true"></i>
            </span>
            Plus
        </button>
    </div>

    <div
        x-cloak
        x-show="open"
        x-transition
        class="border-t border-brand-200/70 bg-white px-4 pt-2 pb-4 dark:border-white/10 dark:bg-brand-dark"
    >
        <div class="grid grid-cols-3 gap-1.5">
            <a href="{{ route('catalogue.index') }}" class="flex flex-col items-center gap-1.5 rounded-lg border border-brand-200 px-2 py-3 text-[0.7rem] font-medium text-brand-700 dark:border-white/10 dark:text-brand-200">
                <i class="fa-solid fa-book-open text-brand-400" aria-hidden="true"></i>
                Catalogue
            </a>
            <a href="{{ route('depenses.index') }}" class="flex flex-col items-center gap-1.5 rounded-lg border border-brand-200 px-2 py-3 text-[0.7rem] font-medium text-brand-700 dark:border-white/10 dark:text-brand-200">
                <i class="fa-solid fa-receipt text-brand-400" aria-hidden="true"></i>
                Dépenses
            </a>
            <a href="{{ route('notifications.index') }}" class="relative flex flex-col items-center gap-1.5 rounded-lg border border-brand-200 px-2 py-3 text-[0.7rem] font-medium text-brand-700 dark:border-white/10 dark:text-brand-200">
                <i class="fa-regular fa-bell text-brand-400" aria-hidden="true"></i>
                Notifications
                @if (($unreadCount ?? 0) > 0)
                    <span class="absolute top-1.5 right-2 size-2 rounded-full bg-brand-accent"></span>
                @endif
            </a>
            <a href="{{ route('abonnement.index') }}" class="flex flex-col items-center gap-1.5 rounded-lg border border-brand-200 px-2 py-3 text-[0.7rem] font-medium text-brand-700 dark:border-white/10 dark:text-brand-200">
                <i class="fa-solid fa-credit-card text-brand-400" aria-hidden="true"></i>
                Abonnement
            </a>
            <a href="{{ route('profil.edit') }}" class="flex flex-col items-center gap-1.5 rounded-lg border border-brand-200 px-2 py-3 text-[0.7rem] font-medium text-brand-700 dark:border-white/10 dark:text-brand-200">
                <i class="fa-regular fa-user text-brand-400" aria-hidden="true"></i>
                Profil
            </a>
            @if (auth()->user()?->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="flex flex-col items-center gap-1.5 rounded-lg border border-brand-200 px-2 py-3 text-[0.7rem] font-medium text-brand-700 dark:border-white/10 dark:text-brand-200">
                    <i class="fa-solid fa-shield-halved text-brand-400" aria-hidden="true"></i>
                    Admin
                </a>
            @endif
        </div>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="cf-btn-secondary w-full text-red-600 dark:text-red-400">
                <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
                Déconnexion
            </button>
        </form>
    </div>
</nav>
