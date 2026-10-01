@php
    $nav = [
        ['route' => 'admin.dashboard', 'label' => 'Vue d\'ensemble', 'icon' => 'fa-solid fa-gauge-high'],
        ['route' => 'admin.utilisateurs.index', 'label' => 'Utilisateurs', 'icon' => 'fa-solid fa-users'],
        ['route' => 'admin.paiements.index', 'label' => 'Paiements à vérifier', 'icon' => 'fa-solid fa-receipt'],
    ];

    $pendingCount = \App\Models\Subscription::enAttente()->count();
@endphp

<aside
    class="sticky top-0 hidden h-screen w-72 shrink-0 border-r border-brand-800 bg-brand-900 lg:flex lg:flex-col dark:border-white/10"
    aria-label="Navigation administrateur"
>
    <div class="flex h-16 items-center gap-2.5 border-b border-white/10 px-6">
        <span class="flex size-9 items-center justify-center rounded-lg bg-brand-400 text-brand-900">
            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
        </span>
        <span class="flex flex-col leading-none">
            <span class="font-serif text-base font-semibold tracking-tight text-brand-50">Couture+</span>
            <span class="mt-0.5 text-[0.65rem] font-medium tracking-wide text-brand-300 uppercase">Administration</span>
        </span>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-6">
        @foreach ($nav as $item)
            @php $active = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'); @endphp
            <a
                href="{{ route($item['route']) }}"
                @if ($active) aria-current="page" @endif
                class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
                    {{ $active
                        ? 'bg-brand-400 text-brand-900'
                        : 'text-brand-200 hover:bg-white/10' }}"
            >
                <i class="{{ $item['icon'] }} w-4 text-center" aria-hidden="true"></i>
                <span class="flex-1">{{ $item['label'] }}</span>
                @if ($item['route'] === 'admin.paiements.index' && $pendingCount > 0)
                    <span class="rounded-full bg-brand-accent px-1.5 py-0.5 text-[0.65rem] font-semibold text-white">
                        {{ $pendingCount }}
                    </span>
                @endif
            </a>
        @endforeach
    </nav>

    <div class="border-t border-white/10 p-4">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm font-medium text-brand-200 transition hover:bg-white/10">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            Retour à l'application
        </a>
    </div>
</aside>
