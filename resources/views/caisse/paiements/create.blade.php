@extends('layouts.app')

@section('title', 'Nouveau paiement')
@section('header_title', 'Enregistrer un paiement')
@section('header_subtitle', "Ajouter un encaissement dans la caisse de l'atelier")

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
        title="Enregistrer un paiement"
        subtitle="Renseignez le montant encaissé, la méthode et la commande concernée."
        :back="route('caisse.index')"
    />

    <form
        method="POST"
        action="{{ route('caisse.paiements.store') }}"
        class="grid gap-6 lg:grid-cols-3"
    >
        @csrf

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
                        :value="$commandePreselectionnee"
                        placeholder="Aucune commande"
                        hint="Obligatoire pour une avance ou un paiement de solde."
                    />

                    <x-form.select
                        name="client_id"
                        label="Client"
                        :options="$clientsOptions"
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
                        :value="'recette'"
                        :empty="false"
                        required
                    />

                    <x-form.select
                        name="methode"
                        label="Méthode de paiement"
                        :options="$methodeOptions"
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
                        :suffix="$devise"
                        hint="Ne peut pas dépasser le solde restant pour un paiement de solde."
                    />

                    <x-form.text
                        name="date_paiement"
                        label="Date du paiement"
                        type="date"
                        required
                        :value="$aujourdhui"
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
                    />

                    <x-form.textarea
                        name="description"
                        label="Description"
                        :rows="2"
                        placeholder="Objet de l'encaissement ou du remboursement."
                        hint="1 000 caractères maximum."
                    />
                </div>
            </section>

            <div class="flex flex-wrap items-center gap-2">
                <button type="submit" class="cf-btn-primary">
                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                    Enregistrer le paiement
                </button>

                <a href="{{ route('caisse.index') }}" class="cf-btn-secondary">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    Annuler
                </a>
            </div>
        </div>

        <aside class="space-y-6 lg:col-span-1">
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Commandes ouvertes</h2>
                    <a href="{{ route('commandes.index') }}" class="cf-link text-xs">Toutes les commandes</a>
                </div>

                @if ($commandes->isEmpty())
                    <x-empty-state
                        icon="fa-solid fa-scissors"
                        title="Aucune commande ouverte"
                        message="Créez une commande pour pouvoir enregistrer une avance ou un solde."
                        :action="route('commandes.create')"
                        action-label="Créer une commande"
                    />
                @else
                    <ul class="max-h-96 divide-y divide-brand-100 overflow-y-auto dark:divide-white/5">
                        @foreach ($commandes as $commande)
                            <li class="px-5 py-3">
                                <a href="{{ route('commandes.show', $commande) }}" class="flex items-center justify-between gap-3">
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold">{{ $commande->numero }}</span>
                                        <span class="block truncate text-xs text-brand-500 dark:text-brand-400">
                                            {{ $commande->client?->nom ?? 'Client inconnu' }}
                                        </span>
                                    </span>
                                    <span class="shrink-0 text-xs font-semibold text-brand-accent tabular-nums">
                                        {{ number_format((float) $commande->solde, 0, ',', ' ') }} {{ $devise }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </aside>
    </form>
@endsection
