@extends('layouts.app')

@section('title', $client->nom)
@section('header_title', $client->nom)
@section('header_subtitle', $client->is_active ? 'Client actif' : 'Client inactif')

@section('content')
    @php
        $devise = config('coutureflow.currency');
        $commandesRecentes = $client->commandes;
    @endphp

   <x-page-header
    :title="$client->nom"
    :subtitle="$client->telephone"
    :back="route('clients.index')"
>
    <x-slot:actions>
        <div class="grid grid-cols-2 gap-2 w-full sm:flex sm:flex-wrap sm:justify-end sm:w-auto">
            <a href="{{ route('commandes.create', ['client_id' => $client->id]) }}" class="cf-btn-primary cf-btn-sm w-full">
                <i class="fa-solid fa-scissors" aria-hidden="true"></i>
                Nouvelle commande
            </a>

            <a href="{{ route('mesures.index', $client) }}" class="cf-btn-secondary cf-btn-sm w-full">
                <i class="fa-solid fa-ruler" aria-hidden="true"></i>
                Prendre des mesures
            </a>

            <a href="{{ route('clients.edit', $client) }}" class="cf-btn-secondary cf-btn-sm w-full">
                <i class="fa-solid fa-pen" aria-hidden="true"></i>
                Modifier
            </a>

            <x-delete-form
                :action="route('clients.destroy', $client)"
                title="Supprimer le client"
                :message="'La fiche de '.$client->nom.' sera définitivement supprimée. Continuer ?'"
                label="Supprimer"
                class="cf-btn-danger cf-btn-sm w-full"
            />
        </div>
    </x-slot:actions>
</x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- (1) Identité --}}
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Identité</h2>
                    <span class="cf-badge {{ $client->is_active
                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300'
                        : 'bg-stone-100 text-stone-700 dark:bg-white/10 dark:text-stone-300' }}">
                        <i class="{{ $client->is_active ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-pause' }} text-[0.7em]" aria-hidden="true"></i>
                        {{ $client->is_active ? 'Actif' : 'Inactif' }}
                    </span>
                </div>

                <div class="space-y-5 p-5 sm:p-6">
                    <div class="flex items-center gap-4">
                        @if ($client->hasPhoto())
                            <img
                                src="{{ route('clients.photo', $client) }}"
                                alt="Photo de {{ $client->nom }}"
                                class="size-20 shrink-0 rounded-2xl object-cover"
                            >
                        @else
                            <span
                                class="flex size-20 shrink-0 items-center justify-center rounded-2xl bg-brand-100 font-serif text-xl font-semibold text-brand-700 dark:bg-white/10 dark:text-brand-200"
                                aria-hidden="true"
                            >
                                {{ $client->initiales() }}
                            </span>
                        @endif

                        <div class="min-w-0">
                            <p class="font-serif text-xl font-semibold">{{ $client->nom }}</p>
                            <p class="mt-0.5 truncate text-sm text-brand-600 dark:text-brand-300">
                                {{ $client->adresse ?: 'Aucune adresse renseignée' }}
                            </p>
                        </div>
                    </div>

                    <div class="cf-divider"></div>

                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Téléphone</dt>
                            <dd class="mt-0.5">
                                <a href="tel:{{ $client->telephone }}" class="cf-link flex items-center gap-2">
                                    <i class="fa-solid fa-phone w-4 text-brand-400" aria-hidden="true"></i>
                                    {{ $client->telephone }}
                                </a>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Email</dt>
                            <dd class="mt-0.5">
                                @if (filled($client->email))
                                    <a href="mailto:{{ $client->email }}" class="cf-link flex min-w-0 items-center gap-2">
                                        <i class="fa-regular fa-envelope w-4 shrink-0 text-brand-400" aria-hidden="true"></i>
                                        <span class="truncate">{{ $client->email }}</span>
                                    </a>
                                @else
                                    <span class="flex items-center gap-2 text-sm text-brand-400">
                                        <i class="fa-regular fa-envelope w-4" aria-hidden="true"></i>
                                        Aucun email
                                    </span>
                                @endif
                            </dd>
                        </div>

                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Adresse</dt>
                            <dd class="mt-0.5 flex items-start gap-2 text-sm font-medium">
                                <i class="fa-solid fa-location-dot mt-0.5 w-4 shrink-0 text-brand-400" aria-hidden="true"></i>
                                {{ $client->adresse ?: 'Aucune adresse renseignée' }}
                            </dd>
                        </div>

                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Notes internes</dt>
                            <dd class="mt-0.5 text-sm whitespace-pre-line">{{ $client->notes ?: 'Aucune note.' }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            {{-- (2) Finances --}}
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Financier</h2>
                    <span class="text-xs text-brand-500 dark:text-brand-400">
                        {{ $client->commandes->count() }} commande(s) récente(s)
                    </span>
                </div>

                <div class="grid gap-4 p-5 sm:grid-cols-3 sm:p-6">
                    <div class="rounded-lg bg-brand-50/60 p-4 dark:bg-white/5">
                        <p class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Total commandé</p>
                        <p class="mt-1 font-serif text-xl font-semibold tabular-nums">
                            {{ number_format($client->totalCommande(), 0, ',', ' ') }} <span class="text-sm font-normal">{{ $devise }}</span>
                        </p>
                    </div>

                    <div class="rounded-lg bg-brand-50/60 p-4 dark:bg-white/5">
                        <p class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Total payé</p>
                        <p class="mt-1 font-serif text-xl font-semibold text-emerald-600 tabular-nums dark:text-emerald-400">
                            {{ number_format($client->totalPaye(), 0, ',', ' ') }} <span class="text-sm font-normal">{{ $devise }}</span>
                        </p>
                    </div>

                    <div class="rounded-lg bg-brand-50/60 p-4 dark:bg-white/5">
                        <p class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Total dû</p>
                        <p class="mt-1 font-serif text-xl font-semibold tabular-nums {{ $client->totalDu() > 0 ? 'text-brand-accent' : 'text-emerald-600 dark:text-emerald-400' }}">
                            {{ number_format($client->totalDu(), 0, ',', ' ') }} <span class="text-sm font-normal">{{ $devise }}</span>
                        </p>
                    </div>
                </div>
            </section>

            {{-- (3) Résumé des mesures — une carte par relevé, dans l'ordre de son histoire --}}
            <x-mesures-resume
                :releves="$relevesMesures"
                :total="$relevesMesures->sum(fn ($releve) => $releve->count())"
                :client="$client"
            />

            {{-- (4) Commandes récentes --}}
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Commandes récentes</h2>
                    <a href="{{ route('commandes.index', ['client_id' => $client->id]) }}" class="cf-link text-xs">
                        Toutes les commandes
                        <i class="fa-solid fa-arrow-right ml-1" aria-hidden="true"></i>
                    </a>
                </div>

                @if ($commandesRecentes->isEmpty())
                    <x-empty-state
                        icon="fa-solid fa-scissors"
                        title="Aucune commande"
                        message="Créez une commande pour démarrer le suivi de la réalisation."
                        :action="route('commandes.create', ['client_id' => $client->id])"
                        action-label="Créer une commande"
                    />
                @else
                    <ul class="divide-y divide-brand-100 dark:divide-white/5">
                        @foreach ($commandesRecentes as $commande)
                            @php $enRetard = $commande->estEnRetard(); @endphp

                            <li class="flex flex-wrap items-center gap-3 px-5 py-4">
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('commandes.show', $commande) }}" class="cf-link font-semibold">
                                        {{ $commande->numero }}
                                    </a>
                                    <p class="mt-0.5 truncate text-xs text-brand-600 dark:text-brand-300">
                                        {{ $commande->client?->nom ?? $client->nom }}
                                        @if (filled($commande->tissu))
                                            — {{ $commande->tissu }}
                                        @endif
                                    </p>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="cf-badge {{ $commande->statutBadgeClass() }}">
                                        <i class="{{ $commande->getStatutEnum()->icon() }} text-[0.7em]" aria-hidden="true"></i>
                                        {{ $commande->statutLabel() }}
                                    </span>

                                    @if ($enRetard)
                                        <span class="cf-badge bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-300">
                                            <i class="fa-solid fa-triangle-exclamation text-[0.7em]" aria-hidden="true"></i>
                                            {{ $commande->joursDeRetard() }} j de retard
                                        </span>
                                    @endif

                                    <span class="whitespace-nowrap text-xs text-brand-500 tabular-nums dark:text-brand-400">
                                        Livraison : {{ $commande->date_livraison_prevue?->format('d/m/Y') ?? '—' }}
                                    </span>

                                    @if ($commande->estSoldePaye())
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                            <i class="fa-solid fa-check" aria-hidden="true"></i>
                                            Payé
                                        </span>
                                    @else
                                        <span class="whitespace-nowrap text-xs font-semibold text-brand-accent tabular-nums">
                                            Solde : {{ number_format((float) $commande->solde, 0, ',', ' ') }} {{ $devise }}
                                        </span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <aside class="space-y-6 lg:col-span-1">
            {{-- (5) Paiements récents --}}
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Paiements récents</h2>
                    <a href="{{ route('caisse.index') }}" class="cf-link text-xs">Caisse</a>
                </div>

                @if ($paiements->isEmpty())
                    <x-empty-state
                        icon="fa-solid fa-receipt"
                        title="Aucun paiement"
                        message="Les avances et soldes encaissés pour ce client apparaîtront ici."
                        :action="route('caisse.paiements.create')"
                        action-label="Enregistrer un paiement"
                    />
                @else
                    <ul class="divide-y divide-brand-100 dark:divide-white/5">
                        @foreach ($paiements as $paiement)
                            <li class="px-5 py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <span class="cf-badge {{ $paiement->typeBadgeClass() }}">
                                            {{ $paiement->typeLabel() }}
                                        </span>
                                        <p class="mt-1 flex items-center gap-1.5 text-xs text-brand-600 dark:text-brand-300">
                                            <i class="{{ $paiement->methodeIcon() }} w-4 text-center text-brand-400" aria-hidden="true"></i>
                                            {{ $paiement->methodeLabel() }}
                                        </p>
                                    </div>

                                    <div class="shrink-0 text-right">
                                        <p class="font-semibold tabular-nums">
                                            {{ number_format((float) $paiement->montant, 0, ',', ' ') }} {{ $devise }}
                                        </p>
                                        <p class="text-xs text-brand-500 tabular-nums dark:text-brand-400">
                                            {{ $paiement->date_paiement?->format('d/m/Y') ?? '—' }}
                                        </p>
                                    </div>
                                </div>

                                @if (filled($paiement->commande))
                                    <a
                                        href="{{ route('commandes.show', $paiement->commande) }}"
                                        class="cf-link mt-1.5 block text-xs"
                                    >
                                        <i class="fa-solid fa-scissors" aria-hidden="true"></i>
                                        {{ $paiement->commande->numero }}
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- (6) Rendez-vous --}}
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Rendez-vous</h2>
                    <a href="{{ route('planning.index') }}" class="cf-link text-xs">Planning</a>
                </div>

                @if ($rendezVous->isEmpty())
                    <x-empty-state
                        icon="fa-regular fa-calendar"
                        title="Aucun rendez-vous"
                        message="Planifiez un essayage ou une livraison depuis le planning de l'atelier."
                        :action="route('planning.index')"
                        action-label="Ouvrir le planning"
                    />
                @else
                    <ul class="divide-y divide-brand-100 dark:divide-white/5">
                        @foreach ($rendezVous as $rdv)
                            <li class="flex items-center gap-3 px-5 py-3">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $rdv->typeBadgeClass() }}">
                                    <i class="{{ $rdv->typeIcon() }} text-xs" aria-hidden="true"></i>
                                </span>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold">{{ $rdv->titre }}</p>
                                    <p class="truncate text-xs text-brand-500 dark:text-brand-400">
                                        {{ $rdv->typeLabel() }} — {{ $rdv->date_debut?->translatedFormat('d/m/Y') ?? '—' }} à {{ $rdv->heureFormatee() }}
                                    </p>
                                    @if (filled($rdv->lieu))
                                        <p class="truncate text-xs text-brand-500 dark:text-brand-400">
                                            <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                                            {{ $rdv->lieu }}
                                        </p>
                                    @endif
                                </div>

                                @if ($rdv->estAujourdhui())
                                    <span class="cf-badge bg-brand-100 text-brand-800 dark:bg-brand-500/20 dark:text-brand-200">
                                        Aujourd'hui
                                    </span>
                                @elseif ($rdv->estPasse())
                                    <span class="cf-badge bg-stone-100 text-stone-600 dark:bg-white/10 dark:text-stone-400">
                                        Passé
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </aside>
    </div>
@endsection
