@extends('layouts.app')

@section('title', 'Commandes')
@section('header_title', 'Commandes')
@section('header_subtitle', $totalEnRetard > 0
    ? $totalEnRetard.' commande(s) en retard de livraison'
    : 'Toutes les commandes de votre atelier')

@section('content')
    @php
        $devise = config('coutureflow.currency');
        $statutActif = (string) ($filtres['statut'] ?? '');
        $retardActif = ! empty($filtres['retard']);
        $filtreActif = collect($filtres)->filter(fn ($valeur) => $valeur !== null && $valeur !== '')->isNotEmpty();
        $baseFiltres = array_filter([
            'q' => $filtres['q'] ?? null,
            'client_id' => $filtres['client_id'] ?? null,
        ], fn ($valeur) => $valeur !== null && $valeur !== '');
        $clientsOptions = $clients->mapWithKeys(fn ($client) => [$client->id => $client->nom]);
    @endphp

    <x-page-header
        title="Commandes"
        :subtitle="'Gestion des commandes, de la prise en charge à la livraison'"
    >
        <x-slot:actions>
            <a href="{{ route('commandes.create') }}" class="cf-btn-primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Nouvelle commande
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-5 flex flex-wrap items-center gap-2" role="group" aria-label="Filtrer par statut">
        @php
            $classesPill = 'inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-medium transition';
            $pillActif = 'border-brand-800 bg-brand-800 text-brand-50 dark:border-brand-400 dark:bg-brand-400 dark:text-brand-900';
            $pillInactif = 'border-brand-200 bg-white text-brand-700 hover:bg-brand-50 dark:border-white/10 dark:bg-white/5 dark:text-brand-200';
        @endphp

        <a
            href="{{ route('commandes.index', $baseFiltres) }}"
            class="{{ $classesPill }} {{ ($statutActif === '' && ! $retardActif) ? $pillActif : $pillInactif }}"
            @if ($statutActif === '' && ! $retardActif) aria-current="page" @endif
        >
            Toutes
        </a>

        @foreach ($statutOptions as $valeur => $libelle)
            <a
                href="{{ route('commandes.index', $baseFiltres + ['statut' => $valeur]) }}"
                class="{{ $classesPill }} {{ ($statutActif === (string) $valeur && ! $retardActif) ? $pillActif : $pillInactif }}"
                @if ($statutActif === (string) $valeur && ! $retardActif) aria-current="page" @endif
            >
                <i class="{{ \App\Enums\CommandeStatut::from($valeur)->icon() }}" aria-hidden="true"></i>
                {{ $libelle }}
                <span class="rounded-full bg-black/5 px-1.5 py-0.5 text-[0.65rem] tabular-nums dark:bg-white/10">
                    {{ $compteurs[$valeur] ?? 0 }}
                </span>
            </a>
        @endforeach

        @if ($totalEnRetard > 0)
            <a
                href="{{ route('commandes.index', ['retard' => 1]) }}"
                class="{{ $classesPill }} {{ $retardActif
                    ? 'border-red-600 bg-red-600 text-white'
                    : 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300' }}"
                @if ($retardActif) aria-current="page" @endif
            >
                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                En retard
                <span class="rounded-full bg-black/5 px-1.5 py-0.5 text-[0.65rem] tabular-nums dark:bg-white/10">
                    {{ $totalEnRetard }}
                </span>
            </a>
        @endif
    </div>

    <form
        method="GET"
        action="{{ route('commandes.index') }}"
        class="cf-card mb-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end"
    >
        @if ($retardActif)
            <input type="hidden" name="retard" value="1">
        @endif

        <x-form.text
            name="q"
            label="Rechercher"
            placeholder="Numéro, client, tissu, type"
            :value="$filtres['q'] ?? null"
            input-class="!w-auto"
        />

        <x-form.select
            name="statut"
            label="Statut"
            :options="$statutOptions"
            :value="$filtres['statut'] ?? null"
            placeholder="Tous les statuts"
            input-class="!w-auto"
        />

        <x-form.select
            name="client_id"
            label="Client"
            :options="$clientsOptions"
            :value="$filtres['client_id'] ?? null"
            placeholder="Tous les clients"
            input-class="!w-auto"
        />

        <div class="flex flex-wrap items-center gap-2">
            <button type="submit" class="cf-btn-primary">
                <i class="fa-solid fa-filter" aria-hidden="true"></i>
                Filtrer
            </button>

            @if ($filtreActif)
                <a href="{{ route('commandes.index') }}" class="cf-btn-ghost">
                    <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                    Réinitialiser
                </a>
            @endif
        </div>
    </form>

    <section class="cf-card overflow-hidden">
        <div class="cf-card-header">
            <h2 class="font-serif text-lg font-semibold">Liste des commandes</h2>
            <span class="text-xs text-brand-500 dark:text-brand-400">
                <span class="font-semibold tabular-nums">{{ number_format($commandes->total(), 0, ',', ' ') }}</span>
                commande(s)
            </span>
        </div>

        @if ($commandes->isEmpty())
            <x-empty-state
                icon="fa-solid fa-scissors"
                title="Aucune commande trouvée"
                :message="$filtreActif
                    ? 'Aucune commande ne correspond aux filtres sélectionnés.'
                    : 'Créez votre première commande pour démarrer le suivi de votre atelier.'"
                :action="route('commandes.create')"
                action-label="Créer une commande"
            />
        @else
            <div class="overflow-x-auto">
                <table class="cf-table sm:min-w-64">
                    <caption class="sr-only">Liste des commandes de l'atelier</caption>
<thead>
<tr>
<th scope="col" class="sm:text-xs sm:px-2 sm:py-1">Numéro</th>
<th scope="col" class="sm:text-xs sm:px-2 sm:py-1">Client / Modèle</th>
<th scope="col" class="sm:text-xs sm:px-2 sm:py-1">Tissu</th>
<th scope="col" class="sm:text-xs sm:px-2 sm:py-1">Statut</th>
<th scope="col" class="sm:text-xs sm:px-2 sm:py-1">Commande</th>
<th scope="col" class="sm:text-xs sm:px-2 sm:py-1">Livraison prévue</th>
<th scope="col" class="text-right sm:text-xs sm:px-2 sm:py-1">Financier</th>
<th scope="col" class="text-right sm:text-xs sm:px-2 sm:py-1">Action</th>
</tr>
</thead>
                    <tbody>
                            @foreach ($commandes as $commande)
                                @php $enRetard = $commande->estEnRetard(); @endphp

                                <tr>
                                    <td class="sm:px-2 sm:py-1.5">
                                        <a href="{{ route('commandes.show', $commande) }}" class="cf-link font-semibold text-sm">
                                            {{ $commande->numero }}
                                        </a>
                                    </td>

                                    <td class="sm:px-2 sm:py-1.5">
                                        @if ($commande->client)
                                            <a href="{{ route('clients.show', $commande->client) }}" class="font-medium hover:underline text-sm">
                                                {{ $commande->client->nom }}
                                            </a>
                                        @else
                                            <span class="font-medium text-sm">—</span>
                                        @endif

                                        @if ($commande->modele)
                                            <a href="{{ route('modeles.show', $commande->modele) }}" class="block text-xs text-brand-500 hover:underline">
                                                {{ $commande->modele->nom }}
                                            </a>
                                        @endif
                                    </td>

                                    <td class="text-xs sm:px-2 sm:py-1.5">{{ $commande->tissu ?: '—' }}</td>

                                    <td class="sm:px-2 sm:py-1.5">
                                        <span class="cf-badge {{ $commande->statutBadgeClass() }}">
                                            <i class="{{ $commande->getStatutEnum()->icon() }} text-[0.7em]" aria-hidden="true"></i>
                                            {{ $commande->statutLabel() }}
                                        </span>

                                        @if ($enRetard)
                                            <span class="mt-1 block text-xs font-medium text-red-600 dark:text-red-400">
                                                {{ $commande->joursDeRetard() }} j de retard
                                            </span>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap tabular-nums sm:px-2 sm:py-1.5 text-sm">
                                        {{ $commande->date_commande?->format('d/m/Y') ?? '—' }}
                                    </td>

                                    <td class="whitespace-nowrap tabular-nums {{ $enRetard ? 'font-semibold text-red-600 dark:text-red-400' : '' }} sm:px-2 sm:py-1.5 text-sm">
                                        {{ $commande->date_livraison_prevue?->format('d/m/Y') ?? '—' }}
                                    </td>

                                    <td class="text-right tabular-nums sm:px-2 sm:py-1.5">
                                        <span class="block font-semibold text-sm">
                                            {{ number_format((float) $commande->prix_total, 0, ',', ' ') }}
                                            <span class="text-[0.65rem] font-normal text-brand-500 dark:text-brand-400">{{ $devise }}</span>
                                        </span>
                                        <span class="block text-xs text-brand-500 dark:text-brand-400">
                                            Avance : {{ number_format((float) $commande->avance, 0, ',', ' ') }} {{ $devise }}
                                        </span>

                                        @if ($commande->estSoldePaye())
                                            <span class="mt-0.5 inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                                <i class="fa-solid fa-check" aria-hidden="true"></i>
                                                Payé
                                            </span>
                                        @else
                                            <span class="mt-0.5 block text-xs font-semibold text-brand-accent">
                                                Solde : {{ number_format((float) $commande->solde, 0, ',', ' ') }} {{ $devise }}
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-right sm:px-2 sm:py-1.5">
                                        <a
                                            href="{{ route('commandes.edit', $commande) }}"
                                            class="cf-btn-secondary cf-btn-sm"
                                            aria-label="Modifier la commande {{ $commande->numero }}"
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

            <x-pagination :paginator="$commandes" label="commandes" />
        @endif
    </section>
@endsection
