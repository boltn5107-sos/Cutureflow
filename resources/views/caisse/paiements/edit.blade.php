@extends('layouts.app')

@section('title', 'Modifier le paiement')
@section('header_title', 'Modifier le paiement')
@section('header_subtitle', $paiement->typeLabel().' — '.number_format((float) $paiement->montant, 0, ',', ' ').' '.config('coutureflow.currency'))

@section('content')
    @php
        $devise = config('coutureflow.currency');
        $aujourdhui = now()->toDateString();

        $commandesOptions = $commandes->mapWithKeys(
            fn ($commande) => [
                $commande->id => $commande->numero.' — '.($commande->client?->nom ?? 'Client inconnu')
                    .' — solde '.number_format((float) $commande->solde, 0, ',', ' ').' '.$devise,
            ]
        );

        $clientsOptions = $clients->mapWithKeys(fn ($client) => [$client->id => $client->nom]);
    @endphp

    <x-page-header
        title="Modifier le paiement"
        subtitle="Mettez à jour le montant, la méthode ou le rattachement de ce paiement."
        :back="route('caisse.paiements.show', $paiement)"
    >
        <x-slot:actions>
            <a href="{{ route('caisse.paiements.show', $paiement) }}" class="cf-btn-secondary">
                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                Voir le reçu
            </a>
        </x-slot:actions>
    </x-page-header>

    <form
        method="POST"
        action="{{ route('caisse.paiements.update', $paiement) }}"
        class="grid gap-6 lg:grid-cols-3"
    >
        @csrf
        @method('PUT')

        <div class="space-y-6 lg:col-span-2">
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Rattachement</h2>
                </div>

                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <x-form.select
                        name="commande_id"
                        label="Commande"
                        :options="$commandesOptions"
                        :value="$paiement->commande_id"
                        placeholder="Aucune commande"
                        hint="Obligatoire pour une avance ou un paiement de solde."
                    />

                    <x-form.select
                        name="client_id"
                        label="Client"
                        :options="$clientsOptions"
                        :value="$paiement->client_id"
                        placeholder="Aucun client"
                        hint="À renseigner pour une recette diverse ou un remboursement."
                    />
                </div>
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Montant et modalités</h2>
                </div>

                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <x-form.select
                        name="type"
                        label="Type de paiement"
                        :options="$typeOptions"
                        :value="$paiement->type?->value"
                        :empty="false"
                        required
                    />

                    <x-form.select
                        name="methode"
                        label="Méthode de paiement"
                        :options="$methodeOptions"
                        :value="$paiement->methode?->value"
                        :empty="false"
                        required
                    />

                    <x-form.text
                        name="montant"
                        label="Montant encaissé"
                        type="number"
                        min="1"
                        step="1"
                        required
                        autofocus
                        :value="$paiement->montant"
                        :suffix="$devise"
                        hint="Ne peut pas dépasser le solde restant pour un paiement de solde."
                    />

                    <x-form.text
                        name="date_paiement"
                        label="Date du paiement"
                        type="date"
                        required
                        :value="$paiement->date_paiement?->format('Y-m-d')"
                        :max="$aujourdhui"
                    />
                </div>
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Précisions</h2>
                </div>

                <div class="grid gap-4 p-5">
                    <x-form.text
                        name="reference"
                        label="Référence"
                        maxlength="80"
                        placeholder="N° de transaction, de reçu…"
                        :value="$paiement->reference"
                    />

                    <x-form.textarea
                        name="description"
                        label="Description"
                        :rows="2"
                        placeholder="Objet de l'encaissement ou du remboursement."
                        :value="$paiement->description"
                        hint="1 000 caractères maximum."
                    />
                </div>
            </section>

            <div class="flex flex-wrap items-center gap-2">
                <button type="submit" class="cf-btn-primary">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                    Enregistrer
                </button>

                <a href="{{ route('caisse.paiements.show', $paiement) }}" class="cf-btn-secondary">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    Annuler
                </a>

                <x-delete-form
                    :action="route('caisse.paiements.destroy', $paiement)"
                    title="Supprimer le paiement"
                    message="Ce paiement sera définitivement supprimé de la caisse. Continuer ?"
                    label="Supprimer"
                    class="cf-btn-danger cf-btn-sm"
                />
            </div>
        </div>

        <aside class="space-y-6 lg:col-span-1">
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">État actuel</h2>
                </div>

                <div class="space-y-3 p-5">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-brand-600 dark:text-brand-300">Montant</span>
                        <span class="font-serif text-xl font-semibold tabular-nums">
                            {{ number_format((float) $paiement->montant, 0, ',', ' ') }} {{ $devise }}
                        </span>
                    </div>

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

                    @if ($paiement->commande)
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm text-brand-600 dark:text-brand-300">Commande</span>
                            <a href="{{ route('commandes.show', $paiement->commande) }}" class="cf-link">
                                {{ $paiement->commande->numero }}
                            </a>
                        </div>
                    @endif

                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-brand-600 dark:text-brand-300">Enregistré le</span>
                        <span class="text-sm font-medium">{{ $paiement->created_at?->format('d/m/Y') ?? '—' }}</span>
                    </div>
                </div>
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Raccourcis</h2>
                </div>

                <div class="space-y-2 p-5">
                    <a href="{{ route('caisse.index') }}" class="cf-btn-secondary w-full">
                        <i class="fa-solid fa-wallet" aria-hidden="true"></i>
                        Retour à la caisse
                    </a>

                    <a href="{{ route('caisse.paiements.create') }}" class="cf-btn-accent w-full">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        Nouveau paiement
                    </a>

                    @if ($paiement->commande)
                        <a href="{{ route('commandes.show', $paiement->commande) }}" class="cf-btn-secondary w-full">
                            <i class="fa-solid fa-scissors" aria-hidden="true"></i>
                            Voir la commande
                        </a>
                    @endif
                </div>
            </section>
        </aside>
    </form>
@endsection
