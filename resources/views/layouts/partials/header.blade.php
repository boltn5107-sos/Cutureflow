<header
    class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-brand-200/70 bg-white/85 px-4 backdrop-blur-md sm:px-6 lg:px-8 dark:border-white/10 dark:bg-brand-dark/85"
    x-data="{ menu: false, profil: false, notifs: false }"
    data-notifications="{{ route('notifications.etat') }}"
    data-dernier-id="{{ $dernierNotificationId }}"
    data-non-lues="{{ $unreadCount }}"
    data-notifications-push="{{ route('notifications.push.subscribe') }}"
    @keydown.escape.window="$store.sidebar.open = false; menu = false; profil = false; notifs = false"
>
    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 lg:hidden">
        <x-app-logo />
        <span class="font-serif text-base font-semibold tracking-tight">Couture+</span>
    </a>

    <div class="hidden min-w-0 flex-1 items-center lg:flex">
        <div class="min-w-0">
            <h1 class="truncate font-serif text-xl font-semibold tracking-tight">@yield('header_title', 'Tableau de bord')</h1>
            @hasSection('header_subtitle')
                <p class="mt-0.5 truncate text-xs text-brand-600 dark:text-brand-300">@yield('header_subtitle')</p>
            @endif
        </div>
    </div>

    <button
        type="button"
        class="ml-auto flex size-9 items-center justify-center rounded-lg text-brand-700 transition hover:bg-brand-100/70 dark:text-brand-200 dark:hover:bg-white/10 lg:hidden"
        aria-label="Menu principal"
        x-on:click="$store.sidebar.open = !$store.sidebar.open"
    >
        <i class="fa-solid fa-bars text-sm" aria-hidden="true"></i>
    </button>

    <div class="ml-auto flex items-center gap-1.5 sm:gap-2">
        <button
            type="button"
            x-data="themeToggle"
            @click="toggle()"
            class="flex size-9 items-center justify-center rounded-lg text-brand-700 transition hover:bg-brand-100/70 dark:text-brand-200 dark:hover:bg-white/10"
            aria-label="Basculer le mode sombre"
        >
            <i class="fa-solid fa-moon dark:hidden" aria-hidden="true"></i>
            <i class="fa-solid fa-sun hidden dark:inline" aria-hidden="true"></i>
        </button>

        {{--
            Son des notifications. Le point ambre signale que le navigateur
            n'autorise pas encore le son : il n'est débloqué qu'après une
            interaction, et le bouton ci-dessous en est une.
        --}}
        <button
            type="button"
            x-on:click="$store.notifications.basculerSon()"
            class="relative flex size-9 items-center justify-center rounded-lg text-brand-700 transition hover:bg-brand-100/70 dark:text-brand-200 dark:hover:bg-white/10"
            x-bind:aria-label="$store.notifications.sonActif ? 'Couper le son des notifications' : 'Activer le son des notifications'"
            x-bind:title="$store.notifications.sonActif
                ? ($store.notifications.sonPret ? 'Son des notifications activé' : 'Cliquez pour autoriser le son des notifications')
                : 'Son des notifications coupé'"
        >
            <i class="fa-solid fa-volume-high" x-show="$store.notifications.sonActif" aria-hidden="true"></i>
            <i class="fa-solid fa-volume-xmark" x-show="! $store.notifications.sonActif" aria-hidden="true"></i>

            <span
                x-cloak
                x-show="$store.notifications.sonActif && ! $store.notifications.sonPret"
                class="absolute top-1.5 right-1.5 size-2 rounded-full bg-amber-500 ring-2 ring-white dark:ring-brand-dark"
            ></span>
        </button>

        <div class="relative" @click.outside="notifs = false">
            <button
                type="button"
                @click="notifs = !notifs; profil = false"
                class="relative flex size-9 items-center justify-center rounded-lg text-brand-700 transition hover:bg-brand-100/70 dark:text-brand-200 dark:hover:bg-white/10"
                aria-label="Notifications"
            >
                <i class="fa-regular fa-bell" aria-hidden="true"></i>
                <span
                    x-cloak
                    x-show="$store.notifications.nonLues > 0"
                    class="absolute top-1.5 right-1.5 size-2 rounded-full bg-brand-accent ring-2 ring-white dark:ring-brand-dark"
                ></span>
            </button>

            <div
                x-cloak
                x-show="notifs"
                x-transition.origin.top.right
                class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-xl border border-brand-200 bg-white shadow-elevated dark:border-white/10 dark:bg-brand-dark"
            >
                <div class="flex items-center justify-between border-b border-brand-200 px-4 py-3 dark:border-white/10">
                    <p class="text-sm font-semibold">Notifications</p>
                    <a
                        href="{{ route('notifications.read-all') }}"
                        x-show="$store.notifications.nonLues > 0"
                        x-cloak
                        class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300"
                    >
                        Tout marquer comme lu
                    </a>
                </div>

                <div class="max-h-80 overflow-y-auto">
                    @forelse (auth()->user()?->notifications()->latest()->limit(6)->get() ?? [] as $item)
                        {{--
                            Le clic passe par notifications.read : sans cela la
                            notification resterait non lue, et la pastille
                            compterait encore ce que l'on vient d'ouvrir.
                        --}}
                        <a
                            href="{{ route('notifications.read', $item->id) }}"
                            class="flex gap-3 border-b border-brand-100 px-4 py-3 transition last:border-b-0 hover:bg-brand-50 dark:border-white/5 dark:hover:bg-white/5 {{ $item->read_at ? 'opacity-60' : '' }}"
                        >
                            <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg {{ $item->data['tone'] ?? 'bg-brand-100 text-brand-700 dark:bg-white/10 dark:text-brand-200' }}">
                                <i class="{{ $item->data['icon'] ?? 'fa-regular fa-bell' }} text-xs" aria-hidden="true"></i>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium">{{ $item->data['title'] ?? 'Notification' }}</span>
                                <span class="mt-0.5 block text-xs text-brand-600 dark:text-brand-300">{{ $item->data['message'] ?? '' }}</span>
                                <span class="mt-1 block text-[0.65rem] text-brand-400">{{ $item->created_at->diffForHumans() }}</span>
                            </span>
                        </a>
                    @empty
                        <p class="px-4 py-8 text-center text-sm text-brand-500">
                            Aucune notification pour le moment.
                        </p>
                    @endforelse
                </div>

                <a
                    href="{{ route('notifications.index') }}"
                    class="block border-t border-brand-200 px-4 py-2.5 text-center text-xs font-semibold text-brand-600 hover:bg-brand-50 dark:border-white/10 dark:text-brand-300 dark:hover:bg-white/5"
                >
                    Voir toutes les notifications
                </a>
            </div>
        </div>

        <div class="relative" @click.outside="profil = false">
            <button
                type="button"
                @click="profil = !profil; notifs = false"
                class="flex items-center gap-2.5 rounded-lg py-1.5 pr-2 pl-1.5 transition hover:bg-brand-100/70 dark:hover:bg-white/10"
            >
                <span class="flex size-8 items-center justify-center rounded-lg bg-brand-800 text-xs font-semibold text-brand-50 dark:bg-brand-400 dark:text-brand-900">
                    {{ mb_strtoupper(mb_substr(auth()->user()?->name ?? 'CF', 0, 2)) }}
                </span>
                <span class="hidden min-w-0 text-left sm:block">
                    <span class="block max-w-[9rem] truncate text-sm font-semibold">{{ auth()->user()?->name }}</span>
                    <span class="block text-[0.65rem] text-brand-500">{{ auth()->user()?->atelier?->nom ?? 'Atelier' }}</span>
                </span>
                <i class="fa-solid fa-chevron-down text-[0.6rem] text-brand-400" aria-hidden="true"></i>
            </button>

            <div
                x-cloak
                x-show="profil"
                x-transition.origin.top.right
                class="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-xl border border-brand-200 bg-white shadow-elevated dark:border-white/10 dark:bg-brand-dark"
            >
                <div class="border-b border-brand-200 px-4 py-3 dark:border-white/10">
                    <p class="truncate text-sm font-semibold">{{ auth()->user()?->atelier?->nom }}</p>
                    <p class="truncate text-xs text-brand-500">{{ auth()->user()?->email }}</p>
                </div>

                <div class="p-1.5">
                    <a
                        href="{{ route('profil.edit') }}"
                        class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition hover:bg-brand-50 dark:hover:bg-white/5"
                    >
                        <i class="fa-regular fa-user w-4 text-brand-400" aria-hidden="true"></i>
                        Mon profil
                    </a>
                    <a
                        href="{{ route('abonnement.index') }}"
                        class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition hover:bg-brand-50 dark:hover:bg-white/5"
                    >
                        <i class="fa-solid fa-credit-card w-4 text-brand-400" aria-hidden="true"></i>
                        Mon abonnement
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10"
                        >
                            <i class="fa-solid fa-arrow-right-from-bracket w-4" aria-hidden="true"></i>
                            Déconnexion
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
