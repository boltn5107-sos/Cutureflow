@extends('layouts.app')

@section('title', 'Nouvelle commande')
@section('header_title', 'Nouvelle commande')
@section('header_subtitle', "Enregistrer une nouvelle commande pour un client de l'atelier")

@section('content')
    @php
        $devise = config('coutureflow.currency');
        $aujourdhui = now()->toDateString();

        $clientsOptions = $clients->mapWithKeys(
            fn ($client) => [$client->id => $client->nom.(filled($client->telephone) ? ' — '.$client->telephone : '')]
        );

        $modelesOptions = $modeles->mapWithKeys(
            fn ($modele) => [
                $modele->id => $modele->nom.' — '.number_format((float) $modele->prix_indicatif, 0, ',', ' ').' '.$devise,
            ]
        );

        $typeOptions = [
            'Confection' => 'Confection',
            'Costume' => 'Costume',
            'Robe' => 'Robe',
            'Chemise' => 'Chemise',
            'Pantalon' => 'Pantalon',
            'Tailleur' => 'Tailleur',
            'Autre' => 'Autre',
        ];
    @endphp

    <x-page-header
        title="Nouvelle commande"
        subtitle="Renseignez le client, la confection et les conditions financières."
        :back="route('commandes.index')"
    />

    <div
        class="grid gap-6 lg:grid-cols-3"
        x-data="soldeCalcul({{ (float) old('prix_total', 0) }}, {{ (float) old('avance', 0) }})"
    >
        <form method="POST" action="{{ route('commandes.store') }}" class="space-y-6 lg:col-span-2">
            @csrf

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Client et prestation</h2>
                </div>

                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <x-form.select
                        name="client_id"
                        label="Client"
                        :options="$clientsOptions"
                        placeholder="Sélectionner un client"
                        required
                        class="sm:col-span-1"
                    />

                    <x-form.select
                        name="modele_id"
                        label="Modèle du catalogue"
                        :options="$modelesOptions"
                        placeholder="Aucun modèle"
                        hint="Facultatif : lie la commande à une fiche du catalogue."
                    />

                    <x-form.select
                        name="type"
                        label="Type de confection"
                        :options="$typeOptions"
                        placeholder="Sélectionner un type"
                    />

                    <x-form.text
                        name="tissu"
                        label="Tissu"
                        placeholder="Ex. wax, coton, Faso Dan Fani"
                        maxlength="150"
                    />

                    <x-form.textarea
                        name="description"
                        label="Description de la commande"
                        :rows="3"
                        placeholder="Détails de la coupe, finitions, couleurs demandées…"
                        hint="3 000 caractères maximum."
                        class="sm:col-span-2"
                    />
                </div>
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Financement</h2>
                </div>

                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <x-form.text
                        name="prix_total"
                        label="Prix total"
                        type="number"
                        min="0"
                        step="1"
                        required
                        autofocus
                        x-model.number="prixTotal"
                        :suffix="$devise"
                        hint="Le solde est calculé automatiquement."
                    />

                    <x-form.text
                        name="avance"
                        label="Avance versée"
                        type="number"
                        min="0"
                        step="1"
                        required
                        :value="0"
                        x-model.number="avance"
                        :suffix="$devise"
                        hint="Saisissez 0 si aucune avance n'a été versée."
                    />
                </div>
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Dates et suivi</h2>
                </div>

                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <x-form.text
                        name="date_commande"
                        label="Date de commande"
                        type="date"
                        required
                        :value="$aujourdhui"
                        :max="$aujourdhui"
                    />

                    <x-form.text
                        name="date_livraison_prevue"
                        label="Livraison prévue"
                        type="date"
                        hint="Postérieure ou égale à la date de commande."
                    />

                    <x-form.select
                        name="statut"
                        label="Statut"
                        :options="$statutOptions"
                        :value="'en_attente'"
                        :empty="false"
                        required
                        hint="Modifiable à tout moment depuis la fiche commande."
                    />

                    <x-form.textarea
                        name="notes"
                        label="Notes internes"
                        :rows="2"
                        placeholder="Informations réservées à l'atelier."
                        hint="3 000 caractères maximum."
                    />
                </div>
            </section>

            <div class="flex flex-wrap items-center gap-2">
                <button type="submit" class="cf-btn-primary">
                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                    Créer la commande
                </button>

                <a href="{{ route('commandes.index') }}" class="cf-btn-secondary">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    Annuler
                </a>
            </div>
        </form>

        <aside class="space-y-6 lg:col-span-1">
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Solde à payer</h2>
                </div>

                <div class="space-y-4 p-5">
                    <p
                        class="font-serif text-3xl font-semibold tracking-tight text-brand-accent tabular-nums"
                        x-text="format(solde)"
                    >
                        {{ number_format((float) old('prix_total', 0) - (float) old('avance', 0), 0, ',', ' ') }} {{ $devise }}
                    </p>

                    <div
                        class="h-2 w-full overflow-hidden rounded-full bg-brand-100 dark:bg-white/10"
                        role="progressbar"
                        aria-label="Avance versée sur le prix total"
                        :aria-valuenow="progress"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    >
                        <div
                            class="h-full rounded-full bg-brand-accent transition-all duration-300"
                            :style="'width: ' + progress + '%'"
                        ></div>
                    </div>

                    <dl class="space-y-2 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-brand-600 dark:text-brand-300">Prix total</dt>
                            <dd class="font-semibold tabular-nums" x-text="format(prixTotal)">
                                {{ number_format((float) old('prix_total', 0), 0, ',', ' ') }} {{ $devise }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-brand-600 dark:text-brand-300">Avance</dt>
                            <dd class="font-semibold tabular-nums" x-text="format(avance)">
                                {{ number_format((float) old('avance', 0), 0, ',', ' ') }} {{ $devise }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-brand-600 dark:text-brand-300">Part versée</dt>
                            <dd class="font-semibold tabular-nums">
                                <span x-text="progress + ' %'">0 %</span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Numéro attribué</h2>
                </div>

                <div class="space-y-2 p-5">
                    <p class="font-serif text-xl font-semibold tracking-tight tabular-nums">{{ $numeroPropose }}</p>
                    <p class="text-xs text-brand-600 dark:text-brand-300">
                        Numéro attribué automatiquement à l'enregistrement de la commande.
                    </p>
                </div>
            </section>
        </aside>
    </div>
@endsection
