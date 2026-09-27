@extends('layouts.app')

@section('title', 'Clients')
@section('header_title', 'Clients')
@section('header_subtitle', number_format($clients->total(), 0, ',', ' ').' client(s) enregistré(s)')

@section('content')
    @php
        $filtresActifs = filled($filtres['q'] ?? null) || filled($filtres['etat'] ?? null);
        $etats = [
            '' => 'Tous les statuts',
            'actif' => 'Actifs',
            'inactif' => 'Inactifs',
        ];
    @endphp

    <x-page-header
        title="Clients"
        subtitle="Retrouvez la fiche, les mesures et la situation financière de chaque client."
    >
        <x-slot:actions>
            <a href="{{ route('clients.create') }}" class="cf-btn-primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Nouveau client
            </a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('clients.index') }}" class="cf-card mb-6 p-4 sm:p-5">
        @foreach (request()->except(['q', 'etat', 'page']) as $parametre => $valeur)
            @if (is_scalar($valeur))
                <input type="hidden" name="{{ $parametre }}" value="{{ $valeur }}">
            @endif
        @endforeach

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_15rem_auto] lg:items-end">
            <x-form.text
                name="q"
                label="Rechercher"
                type="search"
                :value="$filtres['q'] ?? ''"
                placeholder="Nom, téléphone, email ou adresse…"
                autocomplete="off"
            />

            <x-form.select
                name="etat"
                label="Statut du client"
                :options="$etats"
                :value="$filtres['etat'] ?? ''"
                :empty="false"
            />

            <div class="flex flex-wrap items-center gap-2">
                <button type="submit" class="cf-btn-primary">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    Filtrer
                </button>

                @if ($filtresActifs)
                    <a href="{{ route('clients.index') }}" class="cf-btn-ghost">
                        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                        Réinitialiser
                    </a>
                @endif
            </div>
        </div>

        @if ($filtresActifs)
            <p class="mt-3 flex items-center gap-1.5 text-xs text-brand-600 dark:text-brand-300">
                <i class="fa-solid fa-filter" aria-hidden="true"></i>
                Filtres actifs
                @if (filled($filtres['q'] ?? null))
                    — recherche : « {{ $filtres['q'] }} »
                @endif
                @if (filled($filtres['etat'] ?? null))
                    — statut : {{ $etats[$filtres['etat']] ?? $filtres['etat'] }}
                @endif
            </p>
        @endif
    </form>

    @if ($clients->isEmpty())
        <section class="cf-card overflow-hidden">
            @if ($filtresActifs)
                <x-empty-state
                    icon="fa-solid fa-user-slash"
                    title="Aucun client ne correspond"
                    message="Modifiez la recherche ou réinitialisez les filtres pour afficher tous vos clients."
                    :action="route('clients.index')"
                    action-label="Réinitialiser les filtres"
                />
            @else
                <x-empty-state
                    icon="fa-solid fa-users"
                    title="Aucun client enregistré"
                    message="Ajoutez votre premier client pour pouvoir créer des commandes et prendre ses mesures."
                    :action="route('clients.create')"
                    action-label="Créer un client"
                />
            @endif
        </section>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($clients as $client)
                <article class="cf-card relative flex flex-col p-5 transition hover:shadow-elevated focus-within:ring-2 focus-within:ring-brand-500/40">
                    {{--
                        Lien étendu : toute la carte ouvre la fiche. Les liens
                        téléphone / email restent au-dessus grâce à z-10.
                    --}}
                    <a
                        href="{{ route('clients.show', $client) }}"
                        class="absolute inset-0 rounded-xl"
                    >
                        <span class="sr-only">Ouvrir la fiche de {{ $client->nom }}</span>
                    </a>

                    <div class="flex items-start gap-3">
                        @if ($client->hasPhoto())
                            <img
                                src="{{ route('clients.photo', $client) }}"
                                alt="Photo de {{ $client->nom }}"
                                class="size-12 shrink-0 rounded-xl object-cover ring-1 ring-brand-200 dark:ring-white/10"
                            >
                        @else
                            <span
                                class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-sm font-semibold text-brand-700 dark:bg-white/10 dark:text-brand-200"
                                aria-hidden="true"
                            >
                                {{ $client->initiales() }}
                            </span>
                        @endif

                        <div class="min-w-0 flex-1">
                            <h2 class="truncate font-serif text-base font-semibold">
                                {{ $client->nom }}
                            </h2>

                            <span class="cf-badge mt-1.5 {{ $client->is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-stone-100 text-stone-700 dark:bg-white/10 dark:text-stone-300' }}">
                                <i class="{{ $client->is_active ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-pause' }} text-[0.7em]" aria-hidden="true"></i>
                                {{ $client->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                    </div>

                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex items-center gap-2">
                            <dt class="sr-only">Téléphone</dt>
                            <i class="fa-solid fa-phone w-4 shrink-0 text-brand-400" aria-hidden="true"></i>
                            <dd class="min-w-0">
                                <a href="tel:{{ $client->telephone }}" class="cf-link relative z-10">{{ $client->telephone }}</a>
                            </dd>
                        </div>

                        @if (filled($client->email))
                            <div class="flex items-center gap-2">
                                <dt class="sr-only">Email</dt>
                                <i class="fa-regular fa-envelope w-4 shrink-0 text-brand-400" aria-hidden="true"></i>
                                <dd class="min-w-0 truncate">
                                    <a href="mailto:{{ $client->email }}" class="cf-link relative z-10">{{ $client->email }}</a>
                                </dd>
                            </div>
                        @endif

                        <div class="flex items-start gap-2">
                            <dt class="sr-only">Adresse</dt>
                            <i class="fa-solid fa-location-dot w-4 shrink-0 text-brand-400" aria-hidden="true"></i>
                            <dd class="min-w-0 text-brand-700 dark:text-brand-200">
                                {{ filled($client->adresse) ? $client->adresse : 'Aucune adresse renseignée' }}
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-auto flex flex-wrap items-center justify-between gap-2 border-t border-brand-100 pt-3 dark:border-white/5">
                        <span class="flex items-center gap-1.5 text-xs text-brand-600 dark:text-brand-300">
                            <i class="fa-solid fa-scissors" aria-hidden="true"></i>
                            {{ $client->commandes_count }} commande(s)
                        </span>

                        <span class="text-xs font-medium text-brand-600 dark:text-brand-300 whitespace-nowrap">
                            Voir la fiche
                            <i class="fa-solid fa-arrow-right ml-1" aria-hidden="true"></i>
                        </span>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-6">
            <x-pagination :paginator="$clients" label="clients" />
        </div>
    @endif
@endsection
