@extends('layouts.app')

@section('title', 'Paiement du '.$paiement->date_paiement?->format('d/m/Y'))
@section('header_title', 'Détail du paiement')
@section('header_subtitle', $paiement->typeLabel().' — '.$paiement->methodeLabel().' — '.$paiement->date_paiement?->translatedFormat('D j M Y'))

@section('content')
    @php
        $devise = config('coutureflow.currency');
        $libelleDate = $paiement->date_paiement?->format('d/m/Y') ?? '—';
    @endphp

    <x-page-header
        :title="'Paiement du '.$libelleDate"
        :subtitle="$paiement->typeLabel().' — '.$paiement->methodeLabel()"
        :back="route('caisse.index')"
    >
        <x-slot:actions>
            <a href="{{ route('caisse.paiements.edit', $paiement) }}" class="cf-btn-secondary">
                <i class="fa-solid fa-pen" aria-hidden="true"></i>
                Modifier
            </a>

            <a href="{{ route('caisse.index') }}" class="cf-btn-ghost">
                <i class="fa-solid fa-wallet" aria-hidden="true"></i>
                Retour à la caisse
            </a>

            <x-delete-form
                :action="route('caisse.paiements.destroy', $paiement)"
                title="Supprimer le paiement"
                message="Ce paiement sera définitivement supprimé de la caisse. Continuer ?"
                label="Supprimer"
                class="cf-btn-danger"
            />
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Reçu de paiement</h2>
                    <span class="cf-badge {{ $paiement->typeBadgeClass() }}">{{ $paiement->typeLabel() }}</span>
                </div>

                <div class="p-5">
                    <div class="rounded-xl border border-brand-200/70 bg-brand-50/50 p-5 text-center dark:border-white/10 dark:bg-white/[0.03]">
                        <p class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Montant</p>
                        <p class="mt-2 font-serif text-4xl font-semibold tracking-tight tabular-nums">
                            {{ number_format((float) $paiement->montant, 0, ',', ' ') }}
                            <span class="text-xl text-brand-600 dark:text-brand-300">{{ $devise }}</span>
                        </p>
                        <p class="mt-3 inline-flex items-center gap-1.5 text-sm text-brand-700 dark:text-brand-200">
                            <i class="{{ $paiement->methodeIcon() }}" aria-hidden="true"></i>
                            {{ $paiement->methodeLabel() }}
                        </p>
                    </div>
                </div>

                <div class="cf-divider">
                    <dl class="grid gap-4 p-5 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Date du paiement</dt>
                            <dd class="mt-0.5 text-sm font-medium">{{ $paiement->date_paiement?->format('d/m/Y') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Référence</dt>
                            <dd class="mt-0.5 text-sm font-medium">{{ $paiement->reference ?: '—' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Description</dt>
                            <dd class="mt-0.5 text-sm whitespace-pre-line">{{ $paiement->description ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Enregistré le</dt>
                            <dd class="mt-0.5 text-sm font-medium">
                                {{ $paiement->created_at?->format('d/m/Y') ?? '—' }}
                                @if ($paiement->created_at)
                                    <span class="block text-xs font-normal text-brand-500 dark:text-brand-400">
                                        {{ $paiement->created_at->diffForHumans() }}
                                    </span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Saisi par</dt>
                            <dd class="mt-0.5 text-sm font-medium">{{ $paiement->creator?->name ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Rattachement</h2>
                </div>

                @if ($paiement->commande)
                    <div class="space-y-4 p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Commande</p>
                                <a href="{{ route('commandes.show', $paiement->commande) }}" class="cf-link font-semibold">
                                    {{ $paiement->commande->numero }}
                                </a>
                                <p class="mt-0.5 text-xs text-brand-500 dark:text-brand-400">
                                    {{ $paiement->commande->type ?: 'Commande' }}
                                </p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Solde restant</p>
                                <p class="mt-0.5 font-serif text-lg font-semibold text-brand-accent tabular-nums">
                                    {{ number_format((float) $paiement->commande->solde, 0, ',', ' ') }} {{ $devise }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('commandes.show', $paiement->commande) }}" class="cf-btn-secondary cf-btn-sm">
                                <i class="fa-solid fa-scissors" aria-hidden="true"></i>
                                Ouvrir la commande
                            </a>
                            <a href="{{ route('caisse.paiements.create', ['commande_id' => $paiement->commande->id]) }}" class="cf-btn-accent cf-btn-sm">
                                <i class="fa-solid fa-sack-dollar" aria-hidden="true"></i>
                                Encaisser un solde
                            </a>
                        </div>
                    </div>
                @else
                    <x-empty-state
                        icon="fa-solid fa-receipt"
                        title="Aucune commande rattachée"
                        message="Ce paiement est une recette diverse ou un remboursement."
                    />
                @endif

                <div class="cf-divider">
                    <div class="space-y-3 p-5">
                        <p class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Client</p>

                        @if ($paiement->client)
                            <div class="flex items-center gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700 dark:bg-white/10 dark:text-brand-200">
                                    {{ $paiement->client->initiales() }}
                                </span>
                                <div class="min-w-0">
                                    <a href="{{ route('clients.show', $paiement->client) }}" class="cf-link block truncate font-semibold">
                                        {{ $paiement->client->nom }}
                                    </a>
                                    @if (filled($paiement->client->telephone))
                                        <a href="tel:{{ $paiement->client->telephone }}" class="block truncate text-xs text-brand-500 dark:text-brand-400">
                                            <i class="fa-solid fa-phone" aria-hidden="true"></i>
                                            {{ $paiement->client->telephone }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-brand-500 dark:text-brand-400">Aucun client rattaché à ce paiement.</p>
                        @endif
                    </div>
                </div>
            </section>
        </div>

        <aside class="space-y-6 lg:col-span-1">
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Résumé</h2>
                </div>

                <div class="space-y-3 p-5">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-brand-600 dark:text-brand-300">Type</span>
                        <span class="cf-badge {{ $paiement->typeBadgeClass() }}">{{ $paiement->typeLabel() }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-brand-600 dark:text-brand-300">Méthode</span>
                        <span class="flex items-center gap-1.5 text-sm font-medium">
                            <i class="{{ $paiement->methodeIcon() }} w-4 text-center text-brand-400" aria-hidden="true"></i>
                            {{ $paiement->methodeLabel() }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-brand-600 dark:text-brand-300">Date</span>
                        <span class="text-sm font-medium">{{ $paiement->date_paiement?->format('d/m/Y') ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-brand-600 dark:text-brand-300">Montant</span>
                        <span class="text-sm font-semibold tabular-nums">
                            {{ number_format((float) $paiement->montant, 0, ',', ' ') }} {{ $devise }}
                        </span>
                    </div>
                </div>
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Actions</h2>
                </div>

                <div class="space-y-2 p-5">
                    <a href="{{ route('caisse.paiements.edit', $paiement) }}" class="cf-btn-primary w-full">
                        <i class="fa-solid fa-pen" aria-hidden="true"></i>
                        Modifier le paiement
                    </a>

                    <a href="{{ route('caisse.index') }}" class="cf-btn-secondary w-full">
                        <i class="fa-solid fa-wallet" aria-hidden="true"></i>
                        Retour à la caisse
                    </a>

                    @if ($paiement->commande)
                        <a href="{{ route('commandes.show', $paiement->commande) }}" class="cf-btn-secondary w-full">
                            <i class="fa-solid fa-scissors" aria-hidden="true"></i>
                            Voir la commande
                        </a>
                    @endif

                    @if ($paiement->client)
                        <a href="{{ route('clients.show', $paiement->client) }}" class="cf-btn-secondary w-full">
                            <i class="fa-solid fa-user" aria-hidden="true"></i>
                            Fiche client
                        </a>
                    @endif
                </div>
            </section>
        </aside>
    </div>
@endsection
