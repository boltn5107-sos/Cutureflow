@extends('layouts.app')

@section('title', 'Dépenses')
@section('header_title', 'Dépenses')
@section('header_subtitle', 'Suivi des charges de l\'atelier par période et par catégorie')

@section('content')
    @php
        $devise = config('coutureflow.currency');
        $fromDefaut = now()->startOfMonth()->toDateString();
        $toDefaut = now()->endOfMonth()->toDateString();

        $duLabel = filled($filtres['from'] ?? null)
            ? \Illuminate\Support\Carbon::parse($filtres['from'])->format('d/m/Y')
            : \Illuminate\Support\Carbon::parse($fromDefaut)->format('d/m/Y');

        $auLabel = filled($filtres['to'] ?? null)
            ? \Illuminate\Support\Carbon::parse($filtres['to'])->format('d/m/Y')
            : \Illuminate\Support\Carbon::parse($toDefaut)->format('d/m/Y');

        $solde = (float) $recettesPeriode - (float) $total;

        $filtreActif = collect($filtres)->filter(fn ($valeur) => $valeur !== null && $valeur !== '')->isNotEmpty();

        $montantsRepartition = array_map('floatval', array_values($repartition));
        $maxRepartition = $montantsRepartition === [] ? 1.0 : max(max($montantsRepartition), 1.0);

        $messageVide = $filtreActif
            ? 'Aucune dépense ne correspond aux filtres sélectionnés.'
            : "Enregistrez votre première dépense pour suivre les charges de l'atelier.";
    @endphp

    <x-page-header
        title="Dépenses"
        subtitle="Contrôlez les sorties d'argent de l'atelier et leur impact sur la trésorerie."
    >
        <x-slot:actions>
            <a href="{{ route('depenses.create') }}" class="cf-btn-primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Nouvelle dépense
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <x-stat-card
            label="Dépenses de la période"
            :value="number_format((float) $total, 0, ',', ' ').' '.$devise"
            icon="fa-solid fa-receipt"
            tone="red"
            :hint="'Du '.$duLabel.' au '.$auLabel"
        />

        <x-stat-card
            label="Recettes de la période"
            :value="number_format((float) $recettesPeriode, 0, ',', ' ').' '.$devise"
            icon="fa-solid fa-sack-dollar"
            tone="emerald"
            hint="Encaissements de la même période"
            :href="route('caisse.index')"
        />

        <x-stat-card
            label="Solde de la période"
            :value="number_format($solde, 0, ',', ' ').' '.$devise"
            :icon="$solde >= 0 ? 'fa-solid fa-arrow-trend-up' : 'fa-solid fa-arrow-trend-down'"
            :tone="$solde >= 0 ? 'emerald' : 'red'"
            hint="Recettes moins dépenses"
        />
    </div>

    <form
        method="GET"
        action="{{ route('depenses.index') }}"
        class="cf-card mt-4 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end"
    >
        <x-form.text
            name="q"
            label="Rechercher"
            placeholder="Libellé, notes"
            :value="$filtres['q'] ?? null"
        />

        <x-form.select
            name="categorie"
            label="Catégorie"
            :options="$categorieOptions"
            :value="$filtres['categorie'] ?? null"
            placeholder="Toutes les catégories"
        />

        <x-form.text
            name="from"
            label="Du"
            type="date"
            :value="$filtres['from'] ?? $fromDefaut"
        />

        <x-form.text
            name="to"
            label="Au"
            type="date"
            :value="$filtres['to'] ?? $toDefaut"
        />

        <div class="flex flex-wrap items-center gap-2">
            <button type="submit" class="cf-btn-primary">
                <i class="fa-solid fa-filter" aria-hidden="true"></i>
                Filtrer
            </button>

            <a href="{{ route('depenses.index') }}" class="cf-btn-ghost">
                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                Réinitialiser
            </a>
        </div>
    </form>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Liste des dépenses</h2>
                    <span class="text-xs text-brand-500 dark:text-brand-400">
                        <span class="font-semibold tabular-nums">{{ number_format($depenses->total(), 0, ',', ' ') }}</span>
                        dépense(s)
                    </span>
                </div>

                @if ($depenses->isEmpty())
                    <x-empty-state
                        icon="fa-solid fa-receipt"
                        title="Aucune dépense trouvée"
                        :message="$messageVide"
                        :action="route('depenses.create')"
                        action-label="Créer une dépense"
                    />
                @else
                    <div class="overflow-x-auto">
                        <table class="cf-table">
                            <caption class="sr-only">Dépenses de l'atelier sur la période sélectionnée</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Date</th>
                                    <th scope="col">Libellé</th>
                                    <th scope="col">Catégorie</th>
                                    <th scope="col" class="text-right">Montant</th>
                                    <th scope="col">Notes</th>
                                    <th scope="col">Auteur</th>
                                    <th scope="col" class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($depenses as $depense)
                                    <tr>
                                        <td data-label="Date" class="whitespace-nowrap tabular-nums">
                                            {{ $depense->date_depense?->format('d/m/Y') ?? '—' }}
                                        </td>

                                        <td data-label="Libellé" class="font-medium">{{ $depense->libelle }}</td>

                                        <td data-label="Catégorie">
                                            <span class="cf-badge bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-200">
                                                <i class="{{ $depense->categorieIcon() }} text-[0.7em]" aria-hidden="true"></i>
                                                {{ $depense->categorieLabel() }}
                                            </span>
                                        </td>

                                        <td data-label="Montant" class="text-right font-semibold tabular-nums">
                                            {{ number_format((float) $depense->montant, 0, ',', ' ') }}
                                            <span class="text-[0.65rem] font-normal text-brand-500 dark:text-brand-400">{{ $devise }}</span>
                                        </td>

                                        <td data-label="Notes" class="max-w-xs text-xs">
                                            <span class="line-clamp-2">{{ $depense->notes ?: '—' }}</span>
                                        </td>

                                        <td data-label="Auteur" class="whitespace-nowrap text-xs">
                                            {{ $depense->creator?->name ?? '—' }}
                                        </td>

                                        <td data-compact class="text-right">
                                            <a
                                                href="{{ route('depenses.edit', $depense) }}"
                                                class="cf-btn-secondary cf-btn-sm"
                                                aria-label="Modifier la dépense {{ $depense->libelle }}"
                                            >
                                                <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                                Modifier
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <x-pagination :paginator="$depenses" label="dépenses" />
                @endif
            </section>
        </div>

        <aside class="space-y-6 lg:col-span-1">
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Répartition par catégorie</h2>
                    <span class="text-xs text-brand-500 dark:text-brand-400">Période affichée</span>
                </div>

                @if ($repartition === [])
                    <x-empty-state
                        icon="fa-solid fa-chart-pie"
                        title="Aucune répartition"
                        message="Les catégories dépenses apparaîtront ici dès la première écriture de la période."
                    />
                @else
                    <ul class="divide-y divide-brand-100 dark:divide-white/5">
                        @foreach ($repartition as $libelle => $montant)
                            @php
                                $montant = (float) $montant;
                                $part = $total > 0 ? round($montant / (float) $total * 100) : 0;
                            @endphp

                            <li class="space-y-2 px-5 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="min-w-0 truncate text-sm font-medium">{{ $libelle }}</span>
                                    <span class="shrink-0 text-sm font-semibold tabular-nums">
                                        {{ number_format($montant, 0, ',', ' ') }}
                                        <span class="text-[0.65rem] font-normal text-brand-500 dark:text-brand-400">{{ $devise }}</span>
                                    </span>
                                </div>

                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-brand-100 dark:bg-white/10">
                                    <div
                                        class="h-full rounded-full bg-brand-500/80"
                                        style="width: {{ max(round($montant / $maxRepartition * 100), 2) }}%"
                                    ></div>
                                </div>

                                <p class="text-[0.65rem] text-brand-500 dark:text-brand-400">
                                    {{ $part }} % du total de la période
                                </p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Raccourcis</h2>
                </div>

                <div class="space-y-2 p-5">
                    <a href="{{ route('depenses.create') }}" class="cf-btn-primary w-full">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        Nouvelle dépense
                    </a>

                    <a href="{{ route('caisse.index') }}" class="cf-btn-secondary w-full">
                        <i class="fa-solid fa-wallet" aria-hidden="true"></i>
                        Voir la caisse
                    </a>
                </div>
            </section>
        </aside>
    </div>
@endsection
