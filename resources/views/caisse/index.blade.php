@extends('layouts.app')

@section('title', 'Caisse')
@section('header_title', 'Caisse')
@section('header_subtitle', 'Recettes, dépenses et encaissements de l\'atelier')

@section('content')
    @php
        $devise = config('coutureflow.currency');
        $fromDefaut = $indicateurs['from']->toDateString();
        $toDefaut = $indicateurs['to']->toDateString();

        $maxEvolution = max(collect($evolution)->flatMap(fn ($mois) => [$mois['recette'], $mois['depense']])->max() ?: 1, 1);
        $maxMethode = max(collect($repartitionMethodes)->max('total') ?: 1, 1);
        $maxType = max(collect($repartitionTypes)->max('total') ?: 1, 1);
        $aEncaisser = $commandesOuvertes->filter(fn ($commande) => (float) $commande->solde > 0)->values();

        $filtreActif = collect($filtres)->filter(fn ($valeur) => $valeur !== null && $valeur !== '')->isNotEmpty();

        $messageVide = $filtreActif
            ? 'Aucun paiement ne correspond aux filtres sélectionnés.'
            : "Enregistrez votre premier encaissement pour suivre la caisse de l'atelier.";
    @endphp

    <x-page-header
        title="Caisse"
        subtitle="Suivi des encaissements et des dépenses de votre atelier."
    >
        <x-slot:actions>
            <a href="{{ route('caisse.paiements.create') }}" class="cf-btn-accent">
                <i class="fa-solid fa-sack-dollar" aria-hidden="true"></i>
                Enregistrer un paiement
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card
            label="Encaissements"
            :value="number_format((float) $indicateurs['recettes'], 0, ',', ' ').' '.$devise"
            icon="fa-solid fa-sack-dollar"
            :hint="'Du '.$indicateurs['from']->format('d/m/Y').' au '.$indicateurs['to']->format('d/m/Y')"
            tone="emerald"
        />

        <x-stat-card
            label="Dépenses"
            :value="number_format((float) $indicateurs['depenses'], 0, ',', ' ').' '.$devise"
            icon="fa-solid fa-receipt"
            hint="Sur la période sélectionnée"
            tone="red"
            :href="route('depenses.index')"
        />

        <x-stat-card
            label="Bénéfice"
            :value="number_format((float) $indicateurs['benefice'], 0, ',', ' ').' '.$devise"
            icon="fa-solid fa-arrow-trend-up"
            :hint="'Recettes - dépenses'"
            :tone="$indicateurs['benefice'] >= 0 ? 'emerald' : 'red'"
        />

        <x-stat-card
            label="Solde à encaisser"
            :value="number_format((float) $indicateurs['solde_total'], 0, ',', ' ').' '.$devise"
            icon="fa-solid fa-hourglass-half"
            :hint="'Sur '.$indicateurs['commandes_en_cours'] + $indicateurs['commandes_en_attente'] + $indicateurs['commandes_pretes'].' commande(s) ouverte(s)'"
            tone="amber"
            :href="route('commandes.index')"
        />
    </div>

    <form
        method="GET"
        action="{{ route('caisse.index') }}"
        class="cf-card mt-4 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end"
    >
        <x-form.text
            name="from"
            label="Du"
            type="date"
            :value="$filtres['from'] ?? $fromDefaut"
            input-class="!w-auto"
        />

        <x-form.text
            name="to"
            label="Au"
            type="date"
            :value="$filtres['to'] ?? $toDefaut"
            input-class="!w-auto"
        />

        @foreach (['q', 'type', 'methode', 'commande_id'] as $cle)
            @if (! empty($filtres[$cle]))
                <input type="hidden" name="{{ $cle }}" value="{{ $filtres[$cle] }}">
            @endif
        @endforeach

        <div class="flex flex-wrap items-center gap-2">
            <button type="submit" class="cf-btn-primary">
                <i class="fa-solid fa-filter" aria-hidden="true"></i>
                Filtrer
            </button>

            @if ($filtreActif)
                <a href="{{ route('caisse.index') }}" class="cf-btn-ghost">
                    <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                    Réinitialiser
                </a>
            @endif
        </div>
    </form>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Évolution financière</h2>
                    <span class="text-xs text-brand-500 dark:text-brand-400">6 derniers mois</span>
                </div>

                <div class="p-5">
                    <div class="flex h-52 items-end gap-3 sm:gap-5">
                        @foreach ($evolution as $mois)
                            <div class="flex min-w-0 flex-1 flex-col items-center gap-2">
                                <div class="flex h-full w-full items-end justify-center gap-1">
                                    <div
                                        class="w-1/2 rounded-t bg-brand-500/80 transition hover:bg-brand-500"
                                        style="height: {{ max(round($mois['recette'] / $maxEvolution * 100), 2) }}%"
                                        title="Recettes : {{ number_format((float) $mois['recette'], 0, ',', ' ') }} {{ $devise }}"
                                    >
                                        <span class="sr-only">
                                            Recettes {{ number_format((float) $mois['recette'], 0, ',', ' ') }} {{ $devise }}
                                        </span>
                                    </div>
                                    <div
                                        class="w-1/2 rounded-t bg-brand-accent/70 transition hover:bg-brand-accent"
                                        style="height: {{ max(round($mois['depense'] / $maxEvolution * 100), 2) }}%"
                                        title="Dépenses : {{ number_format((float) $mois['depense'], 0, ',', ' ') }} {{ $devise }}"
                                    >
                                        <span class="sr-only">
                                            Dépenses {{ number_format((float) $mois['depense'], 0, ',', ' ') }} {{ $devise }}
                                        </span>
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

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Paiements</h2>
                    <span class="text-xs text-brand-500 dark:text-brand-400">
                        <span class="font-semibold tabular-nums">{{ number_format($paiements->total(), 0, ',', ' ') }}</span>
                        paiement(s)
                    </span>
                </div>

                <form
                    method="GET"
                    action="{{ route('caisse.index') }}"
                    class="grid gap-3 border-b border-brand-200/70 p-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end dark:border-white/10"
                >
                    @foreach (['from', 'to'] as $cle)
                        <input
                            type="hidden"
                            name="{{ $cle }}"
                            value="{{ $filtres[$cle] ?? ($cle === 'from' ? $fromDefaut : $toDefaut) }}"
                        >
                    @endforeach

                    @if (! empty($filtres['commande_id']))
                        <input type="hidden" name="commande_id" value="{{ $filtres['commande_id'] }}">
                    @endif

                    <x-form.text
                        name="q"
                        label="Rechercher"
                        placeholder="Référence, description, commande, client"
                        :value="$filtres['q'] ?? null"
                        input-class="!w-auto"
                    />

                    <x-form.select
                        name="type"
                        label="Type"
                        :options="$typeOptions"
                        :value="$filtres['type'] ?? null"
                        placeholder="Tous les types"
                        input-class="!w-auto"
                    />

                    <x-form.select
                        name="methode"
                        label="Méthode"
                        :options="$methodeOptions"
                        :value="$filtres['methode'] ?? null"
                        placeholder="Toutes les méthodes"
                        input-class="!w-auto"
                    />

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="cf-btn-primary">
                            <i class="fa-solid fa-filter" aria-hidden="true"></i>
                            Filtrer
                        </button>

                        @if ($filtreActif)
                            <a href="{{ route('caisse.index') }}" class="cf-btn-ghost">
                                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                Réinitialiser
                            </a>
                        @endif
                    </div>
                </form>

                @if ($paiements->isEmpty())
                    <x-empty-state
                        icon="fa-solid fa-receipt"
                        title="Aucun paiement trouvé"
                        :message="$messageVide"
                        :action="route('caisse.paiements.create')"
                        action-label="Enregistrer un paiement"
                    />
                @else
                    <div class="overflow-x-auto">
                        <table class="cf-table">
                            <caption class="sr-only">Paiements enregistrés dans la caisse</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Date</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Commande</th>
                                    <th scope="col">Client</th>
                                    <th scope="col">Méthode</th>
                                    <th scope="col" class="text-right">Montant</th>
                                    <th scope="col">Description</th>
                                    <th scope="col" class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($paiements as $paiement)
                                    @php $nomClient = $paiement->client?->nom ?? $paiement->commande?->client?->nom; @endphp

                                    <tr>
                                        <td class="whitespace-nowrap tabular-nums">
                                            {{ $paiement->date_paiement?->format('d/m/Y') ?? '—' }}
                                        </td>
                                        <td>
                                            <span class="cf-badge {{ $paiement->typeBadgeClass() }}">
                                                {{ $paiement->typeLabel() }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($paiement->commande)
                                                <a href="{{ route('commandes.show', $paiement->commande) }}" class="cf-link font-semibold">
                                                    {{ $paiement->commande->numero }}
                                                </a>
                                            @else
                                                <span class="text-brand-400">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($paiement->client)
                                                <a href="{{ route('clients.show', $paiement->client) }}" class="hover:underline">
                                                    {{ $paiement->client->nom }}
                                                </a>
                                            @elseif ($nomClient)
                                                <span>{{ $nomClient }}</span>
                                            @else
                                                <span class="text-brand-400">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="flex items-center gap-1.5 whitespace-nowrap">
                                                <i class="{{ $paiement->methodeIcon() }} w-4 text-center text-brand-400" aria-hidden="true"></i>
                                                {{ $paiement->methodeLabel() }}
                                            </span>
                                        </td>
                                        <td class="text-right font-semibold tabular-nums">
                                            {{ number_format((float) $paiement->montant, 0, ',', ' ') }} {{ $devise }}
                                        </td>
                                        <td class="max-w-xs text-xs">
                                            @if ($paiement->reference)
                                                <span class="block font-medium">{{ $paiement->reference }}</span>
                                            @endif
                                            <span class="line-clamp-2">{{ $paiement->description ?: '—' }}</span>
                                        </td>
                                        <td class="text-right">
                                            <a
                                                href="{{ route('caisse.paiements.show', $paiement) }}"
                                                class="cf-btn-secondary cf-btn-sm"
                                                aria-label="Voir le paiement du {{ $paiement->date_paiement?->format('d/m/Y') }}"
                                            >
                                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                                Voir
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <x-pagination :paginator="$paiements" label="paiements" />
                @endif
            </section>
        </div>

        <aside class="space-y-6 lg:col-span-1">
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Par méthode</h2>
                </div>

                @if ($repartitionMethodes === [])
                    <x-empty-state
                        icon="fa-solid fa-money-bill-wave"
                        title="Aucune méthode"
                        message="Les méthodes de paiement apparaîtront ici après le premier encaissement."
                    />
                @else
                    <ul class="divide-y divide-brand-100 dark:divide-white/5">
                        @foreach ($repartitionMethodes as $methode)
                            <li class="space-y-2 px-5 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="flex min-w-0 items-center gap-2 text-sm font-medium">
                                        <i class="{{ $methode['icon'] }} w-4 shrink-0 text-center text-brand-400" aria-hidden="true"></i>
                                        <span class="truncate">{{ $methode['label'] }}</span>
                                    </span>
                                    <span class="shrink-0 text-sm font-semibold tabular-nums">
                                        {{ number_format((float) $methode['total'], 0, ',', ' ') }} {{ $devise }}
                                    </span>
                                </div>
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-brand-100 dark:bg-white/10">
                                    <div
                                        class="h-full rounded-full bg-brand-500/80"
                                        style="width: {{ max(round((float) $methode['total'] / $maxMethode * 100), 2) }}%"
                                    ></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Par type</h2>
                </div>

                @if ($repartitionTypes === [])
                    <x-empty-state
                        icon="fa-solid fa-tags"
                        title="Aucun type"
                        message="La répartition des recettes et remboursements apparaîtra ici."
                    />
                @else
                    <ul class="divide-y divide-brand-100 dark:divide-white/5">
                        @foreach ($repartitionTypes as $type)
                            @php $enumType = \App\Enums\PaiementType::tryFrom((string) $type['type']); @endphp

                            <li class="space-y-2 px-5 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="cf-badge {{ $enumType?->badgeClass() ?? 'bg-stone-100 text-stone-700 dark:bg-white/10 dark:text-stone-300' }}">
                                        {{ $type['label'] }}
                                    </span>
                                    <span class="shrink-0 text-sm font-semibold tabular-nums">
                                        {{ number_format((float) $type['total'], 0, ',', ' ') }} {{ $devise }}
                                    </span>
                                </div>
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-brand-100 dark:bg-white/10">
                                    <div
                                        class="h-full rounded-full bg-brand-accent/70"
                                        style="width: {{ max(round((float) $type['total'] / $maxType * 100), 2) }}%"
                                    ></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">À encaisser</h2>
                    <a href="{{ route('commandes.index') }}" class="cf-link text-xs">Toutes les commandes</a>
                </div>

                @if ($aEncaisser->isEmpty())
                    <x-empty-state
                        icon="fa-solid fa-circle-check"
                        title="Aucun solde restant"
                        message="Toutes les commandes ouvertes sont soldées."
                    />
                @else
                    <ul class="divide-y divide-brand-100 dark:divide-white/5">
                        @foreach ($aEncaisser as $commande)
                            <li class="space-y-2 px-5 py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <a href="{{ route('commandes.show', $commande) }}" class="min-w-0">
                                        <span class="block truncate text-sm font-semibold">{{ $commande->numero }}</span>
                                        <span class="block truncate text-xs text-brand-500 dark:text-brand-400">
                                            {{ $commande->client?->nom ?? 'Client inconnu' }}
                                        </span>
                                    </a>
                                    <span class="shrink-0 text-sm font-semibold text-brand-accent tabular-nums">
                                        {{ number_format((float) $commande->solde, 0, ',', ' ') }} {{ $devise }}
                                    </span>
                                </div>

                                <a
                                    href="{{ route('caisse.paiements.create', ['commande_id' => $commande->id]) }}"
                                    class="cf-btn-accent cf-btn-sm w-full"
                                    aria-label="Encaisser le solde de la commande {{ $commande->numero }}"
                                >
                                    <i class="fa-solid fa-sack-dollar" aria-hidden="true"></i>
                                    Encaisser
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </aside>
    </div>
@endsection
