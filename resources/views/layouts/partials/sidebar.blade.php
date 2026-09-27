@php
    $nav = [
        [
            'label' => 'Pilotage',
            'items' => [
                ['route' => 'dashboard', 'label' => 'Tableau de bord', 'icon' => 'fa-solid fa-gauge-high'],
            ],
        ],
        [
            'label' => 'Atelier',
            'items' => [
                ['route' => 'clients.index', 'label' => 'Clients', 'icon' => 'fa-solid fa-users'],
                ['route' => 'commandes.index', 'label' => 'Commandes', 'icon' => 'fa-solid fa-scissors'],
                ['route' => 'catalogue.index', 'label' => 'Catalogue', 'icon' => 'fa-solid fa-book-open'],
            ],
        ],
        [
            'label' => 'Gestion',
            'items' => [
                ['route' => 'caisse.index', 'label' => 'Caisse', 'icon' => 'fa-solid fa-wallet'],
                ['route' => 'depenses.index', 'label' => 'Dépenses', 'icon' => 'fa-solid fa-receipt'],
                ['route' => 'planning.index', 'label' => 'Planning', 'icon' => 'fa-regular fa-calendar'],
                ['route' => 'notifications.index', 'label' => 'Notifications', 'icon' => 'fa-regular fa-bell'],
            ],
        ],
    ];

    $unreadCount = auth()->user()?->unreadNotifications()->count() ?? 0;
@endphp

<aside
    x-data="{}"
    class="fixed inset-y-0 left-0 z-40 w-72 shrink-0 border-r border-brand-200/70 bg-white transition-transform duration-300 dark:border-white/10 dark:bg-brand-dark"
    :class="$store.sidebar.open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    aria-label="Navigation principale"
>
    <div class="flex h-16 items-center gap-2.5 border-b border-brand-200/70 px-6 dark:border-white/10">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
            <span class="flex size-9 items-center justify-center rounded-lg bg-brand-800 text-brand-50 dark:bg-brand-400 dark:text-brand-900">
                <i class="fa-solid fa-scissors" aria-hidden="true"></i>
            </span>
            <span class="flex flex-col leading-none">
                <span class="font-serif text-base font-semibold tracking-tight">Couture Flow</span>
                <span class="mt-0.5 truncate text-[0.65rem] font-medium tracking-wide text-brand-500 uppercase">
                    {{ auth()->user()?->displayName() ?? 'Mon atelier' }}
                </span>
            </span>
        </a>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-6">
        @foreach ($nav as $group)
            <div>
                <p class="px-3 text-[0.65rem] font-semibold tracking-widest text-brand-400 uppercase">
                    {{ $group['label'] }}
                </p>
                <ul class="mt-2 space-y-0.5">
                    @foreach ($group['items'] as $item)
                        @php
                            $active = request()->routeIs($item['route'])
                                || request()->routeIs($item['route'].'.*');
                        @endphp
                        <li>
                            <a
                                href="{{ route($item['route']) }}"
                                @if ($active) aria-current="page" @endif
                                @click="$store.sidebar.open = false"
                                class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
                                    {{ $active
                                        ? 'bg-brand-800 text-brand-50 dark:bg-brand-400 dark:text-brand-900'
                                        : 'text-brand-700 hover:bg-brand-50 dark:text-brand-200 dark:hover:bg-white/5' }}"
                            >
                                <i class="{{ $item['icon'] }} w-4 text-center" aria-hidden="true"></i>
                                <span class="flex-1">{{ $item['label'] }}</span>
                                @if ($item['route'] === 'notifications.index' && $unreadCount > 0)
                                    <span class="rounded-full bg-brand-accent px-1.5 py-0.5 text-[0.65rem] font-semibold text-white">
                                        {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                                    </span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    <div class="border-t border-brand-200/70 p-4 dark:border-white/10">
        @if (auth()->user()?->atelier?->valid_until ?? null)
            @php
                $expire = auth()->user()->atelier->valid_until ?? null;
                $bientot = $expire && $expire->diffInDays(now()) <= 7;
            @endphp
            <div class="rounded-lg border border-brand-200 bg-brand-50 p-3 dark:border-white/10 dark:bg-white/5">
                <p class="flex items-center gap-1.5 text-[0.7rem] font-semibold text-brand-700 dark:text-brand-200">
                    <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
                    Abonnement
                </p>
                <p class="mt-1 text-xs text-brand-600 dark:text-brand-300">
                    Valide jusqu'au
                    <span class="font-semibold {{ $bientot ? 'text-brand-accent' : '' }}">{{ $expire->format('d/m/Y') }}</span>
                </p>
            </div>
        @endif
    </div>
</aside>
