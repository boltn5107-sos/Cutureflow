@extends('layouts.app')

@section('title', 'Tableau de bord')
@section('header_title', 'Tableau de bord')
@section('header_subtitle', $indicateurs['from']->translatedFormat('F Y'))

@section('content')
    {{--
        Le filtre de dates a été retiré : le tableau de bord reflète toujours
        le mois en cours. Les cartes affectedes par la période sont
        calculées par DashboardService::indicateurs(), qui accepte toujours
        deux bornes — la caisse et les dépenses ont conservé leur propre
        filtre « Du / Au ».
    --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <h1 class="font-serif text-2xl font-semibold tracking-tight sm:text-3xl">
                Bonjour, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}
            </h1>
            <p class="mt-1 text-sm text-brand-600 dark:text-brand-300">
                Voici l'état de {{ auth()->user()->atelier?->nom }} au {{ now()->translatedFormat('l j F Y') }}.
            </p>
        </div>
    </div>

    @if ($indicateurs['commandes_en_retard'] > 0)
        <a
            href="{{ route('commandes.index', ['retard' => 1]) }}"
            class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3.5 transition hover:bg-red-100 dark:border-red-500/30 dark:bg-red-500/10 dark:hover:bg-red-500/20"
        >
            <i class="fa-solid fa-triangle-exclamation mt-0.5 text-red-600 dark:text-red-400" aria-hidden="true"></i>
            <div>
                <p class="text-sm font-semibold text-red-800 dark:text-red-300">
                    {{ $indicateurs['commandes_en_retard'] }} commande(s) en retard de livraison
                </p>
                <p class="mt-0.5 text-xs text-red-700/80 dark:text-red-400/80">
                    Cliquez pour voir le détail et organiser la livraison.
                </p>
            </div>
            <i class="fa-solid fa-arrow-right ml-auto mt-1 text-red-500" aria-hidden="true"></i>
        </a>
    @endif

    {{--
        Relances : on rappelle par qui la commande n'a pas encore été
        transformée. Les clients déjà relancés récemment en sont exclus, et
        le compteur suit le même filtre que la page.
    --}}
    @if ($relances['total'] > 0)
        <a
            href="{{ route('relances.index') }}"
            class="mb-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3.5 transition hover:bg-amber-100 dark:border-amber-500/30 dark:bg-amber-500/10 dark:hover:bg-amber-500/20"
        >
            <i class="fa-solid fa-bell-concierge mt-0.5 text-amber-600 dark:text-amber-400" aria-hidden="true"></i>
            <div>
                <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">
                    {{ $relances['total'] }} client(s) à relancer
                </p>
                <p class="mt-0.5 text-xs text-amber-800/80 dark:text-amber-300/80">
                    Mesures prises sans commande, pièces prêtes non retirées, clients inactifs.
                </p>
            </div>
            <i class="fa-solid fa-arrow-right ml-auto mt-1 text-amber-600" aria-hidden="true"></i>
        </a>
    @endif

    {{--
        Le partage ne transmet aucune donnée client : il promeut l'application
        auprès d'autres ateliers. Le lien pointe vers la page d'accueil publique.
    --}}
    <div class="mb-6 flex flex-wrap items-center gap-3 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3.5 dark:border-white/10 dark:bg-white/5">
        <i class="fa-solid fa-scissors text-brand-500" aria-hidden="true"></i>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-brand-900 dark:text-brand-100">
                Couture+ vous a sauvé du temps ?
            </p>
            <p class="mt-0.5 text-xs text-brand-700/80 dark:text-brand-300/80">
                Partagez l'application à un autre atelier : ils recevront le lien.
            </p>
        </div>
        <x-share-button
            class="cf-btn-secondary cf-btn-sm"
            titre="Couture+ — la gestion d'atelier de couture"
            :texte="'Je utilise Couture+ pour gérer mon atelier de couture : clients, mesures, commandes et planning au même endroit. Essayez aussi :'"
            :url="route('accueil')"
        />
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card
            label="Chiffre d'affaires"
            :value="number_format($indicateurs['recettes'], 0, ',', ' ')"
            icon="fa-solid fa-chart-line"
            :hint="'Encaissements de la période'"
            tone="brand"
            :href="route('caisse.index')"
        />

        <x-stat-card
            label="Bénéfice"
            :value="number_format($indicateurs['benefice'], 0, ',', ' ')"
            icon="fa-solid fa-arrow-trend-up"
            :hint="'Recettes - dépenses ('.number_format($indicateurs['depenses'], 0, ',', ' ').')'"
            :tone="$indicateurs['benefice'] >= 0 ? 'emerald' : 'red'"
            :href="route('caisse.index')"
        />

        <x-stat-card
            label="Commandes en cours"
            :value="number_format($indicateurs['commandes_en_cours'] + $indicateurs['commandes_en_attente'], 0, ',', ' ')"
            icon="fa-solid fa-scissors"
            :hint="$indicateurs['commandes_en_attente'].' en attente, '.$indicateurs['commandes_en_cours'].' en production'"
            tone="blue"
            :href="route('commandes.index')"
        />

        <x-stat-card
            label="Commandes prêtes"
            :value="number_format($indicateurs['commandes_pretes'], 0, ',', ' ')"
            icon="fa-solid fa-box-open"
            hint="À livrer au client"
            tone="emerald"
            :href="route('commandes.index', ['statut' => 'prete'])"
        />
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card
            label="Solde à encaisser"
            :value="number_format($indicateurs['solde_total'], 0, ',', ' ')"
            icon="fa-solid fa-hourglass-half"
            hint="Sur toutes les commandes ouvertes"
            tone="amber"
            :href="route('commandes.index')"
        />

        <x-stat-card
            label="Avances encaissées"
            :value="number_format($indicateurs['avances_total'], 0, ',', ' ')"
            icon="fa-solid fa-hand-holding-dollar"
            hint="Commandes en cours"
            tone="emerald"
        />

        <x-stat-card
            label="Dépenses"
            :value="number_format($indicateurs['depenses'], 0, ',', ' ')"
            icon="fa-solid fa-receipt"
            hint="Sur la période"
            tone="red"
            :href="route('depenses.index')"
        />

        <x-stat-card
            label="Clients"
            :value="number_format($indicateurs['clients_total'], 0, ',', ' ')"
            icon="fa-solid fa-users"
            hint="Dans votre atelier"
            :href="route('clients.index')"
        />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Dernières commandes</h2>
                    <a href="{{ route('commandes.index') }}" class="cf-link text-xs">
                        Tout voir
                        <i class="fa-solid fa-arrow-right ml-1" aria-hidden="true"></i>
                    </a>
                </div>

                @if ($dernieresCommandes->isEmpty())
                    <x-empty-state
                        icon="fa-solid fa-scissors"
                        title="Aucune commande trouvée"
                        message="Créez votre première commande pour commencer."
                        :action="route('commandes.create')"
                        action-label="Créer une commande"
                    />
                @else
                    <div class="overflow-x-auto">
                            <table class="cf-table 2xl:min-w-max">
                                <thead>
                                    <tr>
                                        <th class="min-w-[100px]">Numéro</th>
                                        <th class="min-w-[120px]">Client</th>
                                        <th class="min-w-[100px]">Statut</th>
                                        <th class="text-right min-w-[100px]">Solde</th>
                                    </tr>
                                </thead>
                            <tbody>
                                @foreach ($dernieresCommandes as $commande)
                                    <tr class="hover:bg-brand-50/40 dark:hover:bg-white/[0.03]">
                                        <td data-label="Numéro" class="whitespace-nowrap">
                                            <a href="{{ route('commandes.show', $commande) }}" class="cf-link font-semibold">
                                                {{ $commande->numero }}
                                            </a>
                                            <span class="block text-xs text-brand-500">
                                                {{ $commande->date_commande->format('d/m/Y') }}
                                            </span>
                                        </td>
                                        <td data-label="Client" class="whitespace-nowrap">
                                            <span class="font-medium">{{ $commande->client?->nom ?? '—' }}</span>
                                            @if ($commande->tissu)
                                                <span class="block text-xs text-brand-500">{{ $commande->tissu }}</span>
                                            @endif
                                        </td>
                                        <td data-label="Statut" class="whitespace-nowrap">
                                            <span class="cf-badge {{ $commande->statutBadgeClass() }}">
                                                <i class="{{ $commande->getStatutEnum()->icon() }} text-[0.7em]" aria-hidden="true"></i>
                                                {{ $commande->statutLabel() }}
                                            </span>
                                            @if ($commande->estEnRetard())
                                                <span class="mt-1 block text-xs font-medium text-red-600 dark:text-red-400">
                                                    {{ $commande->joursDeRetard() }} j de retard
                                                </span>
                                            @endif
                                        </td>
                                        <td data-label="Solde" class="text-right tabular-nums whitespace-nowrap">
                                            @if ((float) $commande->solde > 0)
                                                <span class="font-semibold text-brand-accent">
                                                    {{ number_format((float) $commande->solde, 0, ',', ' ') }}
                                                </span>
                                            @else
                                                <span class="text-emerald-600 dark:text-emerald-400">
                                                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                                                    Payé
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Évolution financière</h2>
                    <span class="text-xs text-brand-500">6 derniers mois</span>
                </div>

                <div class="p-5">
                    @php
                        $maxValeur = max(collect($evolution)->flatMap(fn ($m) => [$m['recette'], $m['depense']])->max() ?: 1, 1);
                    @endphp

                    <div class="flex h-52 items-end gap-3 sm:gap-5">
                        @foreach ($evolution as $mois)
                            <div class="flex min-w-0 flex-1 flex-col items-center gap-2">
                                <div class="flex h-full w-full items-end justify-center gap-1">
                                    <div
                                        class="w-1/2 rounded-t bg-brand-500/80 transition hover:bg-brand-500"
                                        style="height: {{ max(round($mois['recette'] / $maxValeur * 100), 2) }}%"
                                        title="Recettes : {{ number_format($mois['recette'], 0, ',', ' ') }}"
                                    >
                                        <span class="sr-only">Recettes {{ number_format($mois['recette'], 0, ',', ' ') }}</span>
                                    </div>
                                    <div
                                        class="w-1/2 rounded-t bg-brand-accent/70 transition hover:bg-brand-accent"
                                        style="height: {{ max(round($mois['depense'] / $maxValeur * 100), 2) }}%"
                                        title="Dépenses : {{ number_format($mois['depense'], 0, ',', ' ') }}"
                                    >
                                        <span class="sr-only">Dépenses {{ number_format($mois['depense'], 0, ',', ' ') }}</span>
                                    </div>
                                </div>
                                <span class="truncate text-[0.65rem] text-brand-500 dark:text-brand-400">{{ $mois['label'] }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 flex items-center justify-center gap-5 border-t border-brand-100 pt-4 text-xs dark:border-white/5">
                        <span class="flex items-center gap-1.5 text-brand-600 dark:text-brand-300">
                            <span class="size-2.5 rounded-sm bg-brand-500/80"></span>
                            Recettes
                        </span>
                        <span class="flex items-center gap-1.5 text-brand-600 dark:text-brand-300">
                            <span class="size-2.5 rounded-sm bg-brand-accent/70"></span>
                            Dépenses
                        </span>
                    </div>
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">À livrer prochainement</h2>
                </div>

                @if ($prochainesLivraisons->isEmpty())
                    <x-empty-state
                        icon="fa-regular fa-calendar-check"
                        title="Aucune livraison prévue"
                        message="Planifiez vos livraisons dans le planning."
                        :action="route('planning.index')"
                        action-label="Ouvrir le planning"
                    />
                @else
                    <ul class="divide-y divide-brand-100 dark:divide-white/5">
                        @foreach ($prochainesLivraisons as $commande)
                            @php $jours = $commande->livraisonDansJours(); @endphp
                            <li>
                                <a href="{{ route('commandes.show', $commande) }}" class="flex items-center gap-3 px-5 py-3 transition hover:bg-brand-50/60 dark:hover:bg-white/[0.03]">
                                    <span class="flex size-10 shrink-0 flex-col items-center justify-center rounded-lg bg-brand-100 dark:bg-white/10">
                                        <span class="text-[0.6rem] font-semibold tracking-wide text-brand-600 uppercase dark:text-brand-300">
                                            {{ $commande->date_livraison_prevue->translatedFormat('M') }}
                                        </span>
                                        <span class="text-sm leading-none font-bold tabular-nums">
                                            {{ $commande->date_livraison_prevue->format('d') }}
                                        </span>
                                    </span>

                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold">{{ $commande->numero }}</span>
                                        <span class="block truncate text-xs text-brand-500">{{ $commande->client?->nom }}</span>
                                    </span>

                                    @if ($jours !== null && $jours < 0)
                                        <span class="cf-badge bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300">
                                            {{ abs($jours) }} j
                                        </span>
                                    @elseif ($jours === 0)
                                        <span class="cf-badge bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                                            Aujourd'hui
                                        </span>
                                    @else
                                        <span class="cf-badge bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-200">
                                            {{ $jours }} j
                                        </span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Prochains rendez-vous</h2>
                    <a href="{{ route('planning.index') }}" class="cf-link text-xs">Planning</a>
                </div>

                @if ($prochainsRendezVous->isEmpty())
                    <x-empty-state
                        icon="fa-regular fa-calendar"
                        title="Aucun rendez-vous"
                        message="Ajoutez un essayage ou une livraison."
                        :action="route('planning.create')"
                        action-label="Ajouter un événement"
                    />
                @else
                    <ul class="divide-y divide-brand-100 dark:divide-white/5">
                        @foreach ($prochainsRendezVous as $rdv)
                            <li class="flex items-center gap-3 px-5 py-3">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $rdv->typeBadgeClass() }}">
                                    <i class="{{ $rdv->typeIcon() }} text-xs" aria-hidden="true"></i>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold">{{ $rdv->titre }}</span>
                                    <span class="block truncate text-xs text-brand-500">
                                        {{ $rdv->date_debut->translatedFormat('D j M') }}{{ $rdv->heure_debut ? ' à '.$rdv->heureFormatee() : '' }}
                                    </span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Activité récente</h2>
                </div>

                @if ($activite->isEmpty())
                    <x-empty-state
                        icon="fa-solid fa-clock-rotate-left"
                        title="Aucune activité"
                        message="Vos actions recentes apparaîtront ici."
                    />
                @else
                    <ul class="divide-y divide-brand-100 dark:divide-white/5">
                        @foreach ($activite as $evenement)
                            <li>
                                <a href="{{ $evenement['url'] }}" class="flex gap-3 px-5 py-3 transition hover:bg-brand-50/60 dark:hover:bg-white/[0.03]">
                                    <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg {{ $evenement['tone'] }}">
                                        <i class="{{ $evenement['icon'] }} text-[0.7rem]" aria-hidden="true"></i>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold">{{ $evenement['titre'] }}</span>
                                        <span class="block truncate text-xs text-brand-500">{{ $evenement['detail'] }}</span>
                                        <span class="mt-0.5 block text-[0.65rem] text-brand-400">
                                            {{ $evenement['date']->diffForHumans() }}
                                        </span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>

    @if ($topClients !== [])
        <section class="cf-card mt-6 overflow-hidden">
            <div class="cf-card-header">
                <h2 class="font-serif text-lg font-semibold">Clients les plus actifs</h2>
                <a href="{{ route('clients.index') }}" class="cf-link text-xs">Tous les clients</a>
            </div>

            <div class="grid gap-px bg-brand-100 sm:grid-cols-2 lg:grid-cols-5 dark:bg-white/5">
                @foreach ($topClients as $item)
                    <a
                        href="{{ route('clients.show', $item['client']) }}"
                        class="bg-white p-4 transition hover:bg-brand-50/60 dark:bg-brand-dark dark:hover:bg-white/[0.03]"
                    >
                        <span class="flex size-10 items-center justify-center rounded-lg bg-brand-100 text-sm font-semibold text-brand-700 dark:bg-white/10 dark:text-brand-200">
                            {{ $item['client']->initiales() }}
                        </span>
                        <p class="mt-3 truncate text-sm font-semibold">{{ $item['client']->nom }}</p>
                        <p class="mt-0.5 text-xs text-brand-500">{{ $item['total'] }} commande(s)</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
@endsection
