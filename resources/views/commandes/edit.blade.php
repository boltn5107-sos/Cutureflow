@extends('layouts.app')

@section('title', 'Modifier '.$commande->numero)
@section('header_title', 'Modifier la commande')
@section('header_subtitle', $commande->numero.' — '.($commande->client?->nom ?? 'Client inconnu'))

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

        $prixTotal = (float) old('prix_total', (float) $commande->prix_total);
        $avance = (float) old('avance', (float) $commande->avance);
    @endphp

    <x-page-header
        :title="'Modifier '.$commande->numero"
        :subtitle="'Mise à jour de la commande '.$commande->numero.' — '.$commande->statutLabel()"
        :back="route('commandes.show', $commande)"
    >
        <x-slot:actions>
            <a href="{{ route('commandes.show', $commande) }}" class="cf-btn-secondary">
                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                Voir la fiche
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3" x-data="soldeCalcul({{ $prixTotal }}, {{ $avance }})">
        <form
            method="POST"
            action="{{ route('commandes.update', $commande) }}"
            class="space-y-6 lg:col-span-2"
        >
            @csrf
            @method('PUT')

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Client et prestation</h2>
                </div>

                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <x-form.select
                        name="client_id"
                        label="Client"
                        :options="$clientsOptions"
                        :value="$commande->client_id"
                        placeholder="Sélectionner un client"
                        required
                    />

                    <x-form.select
                        name="modele_id"
                        label="Modèle du catalogue"
                        :options="$modelesOptions"
                        :value="$commande->modele_id"
                        placeholder="Aucun modèle"
                        hint="Facultatif : lie la commande à une fiche du catalogue."
                    />

                    <x-form.select
                        name="type"
                        label="Type de confection"
                        :options="$typeOptions"
                        :value="$commande->type"
                        placeholder="Sélectionner un type"
                    />

                    <x-form.text
                        name="tissu"
                        label="Tissu"
                        placeholder="Ex. wax, coton, Faso Dan Fani"
                        maxlength="150"
                        :value="$commande->tissu"
                    />

                    <x-form.textarea
                        name="description"
                        label="Description de la commande"
                        :rows="3"
                        :value="$commande->description"
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
                        :value="$commande->prix_total"
                        hint="Le solde est recalculé et enregistré automatiquement."
                    />

                    <x-form.text
                        name="avance"
                        label="Avance versée"
                        type="number"
                        min="0"
                        step="1"
                        required
                        x-model.number="avance"
                        :suffix="$devise"
                        :value="$commande->avance"
                        hint="Ne peut pas dépasser le prix total."
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
                        :value="$commande->date_commande?->format('Y-m-d')"
                        :max="$aujourdhui"
                    />

                    <x-form.text
                        name="date_livraison_prevue"
                        label="Livraison prévue"
                        type="date"
                        :value="$commande->date_livraison_prevue?->format('Y-m-d')"
                        hint="Postérieure ou égale à la date de commande."
                    />

                    <x-form.text
                        name="date_livraison_reelle"
                        label="Date de livraison réelle"
                        type="date"
                        :value="$commande->date_livraison_reelle?->format('Y-m-d')"
                        :max="$aujourdhui"
                        hint="Renseignée automatiquement au passage au statut Livrée."
                    />

                    <x-form.select
                        name="statut"
                        label="Statut"
                        :options="$statutOptions"
                        :value="$commande->getStatutEnum()->value"
                        :empty="false"
                        required
                        hint="Le statut peut aussi être changé depuis la fiche commande."
                    />

                    <x-form.textarea
                        name="notes"
                        label="Notes internes"
                        :rows="2"
                        :value="$commande->notes"
                        placeholder="Informations réservées à l'atelier."
                        hint="3 000 caractères maximum."
                        class="sm:col-span-2"
                    />
                </div>
            </section>

            <div class="flex flex-wrap items-center gap-2">
                <button type="submit" class="cf-btn-primary">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                    Enregistrer
                </button>

                <a href="{{ route('commandes.show', $commande) }}" class="cf-btn-secondary">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    Annuler
                </a>

                <x-delete-form
                    :action="route('commandes.destroy', $commande)"
                    title="Annuler la commande"
                    message="La commande sera marquée comme annulée puis supprimée. Continuer ?"
                    label="Annuler la commande"
                    icon="fa-solid fa-ban"
                    class="cf-btn-danger cf-btn-sm"
                />
            </div>
        </form>

        <aside class="space-y-6 lg:col-span-1">
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">État actuel</h2>
                </div>

                <div class="space-y-4 p-5">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-brand-600 dark:text-brand-300">Numéro</span>
                        <span class="font-semibold tabular-nums">{{ $commande->numero }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-brand-600 dark:text-brand-300">Statut</span>
                        <span class="cf-badge {{ $commande->statutBadgeClass() }}">
                            <i class="{{ $commande->getStatutEnum()->icon() }} text-[0.7em]" aria-hidden="true"></i>
                            {{ $commande->statutLabel() }}
                        </span>
                    </div>

                    <dl class="space-y-2 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-brand-600 dark:text-brand-300">Prix total</dt>
                            <dd class="font-semibold tabular-nums">
                                {{ number_format((float) $commande->prix_total, 0, ',', ' ') }} {{ $devise }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-brand-600 dark:text-brand-300">Avance</dt>
                            <dd class="font-semibold tabular-nums">
                                {{ number_format((float) $commande->avance, 0, ',', ' ') }} {{ $devise }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-brand-600 dark:text-brand-300">Solde</dt>
                            <dd class="font-semibold text-brand-accent tabular-nums" x-text="format(solde)">
                                {{ number_format((float) $commande->solde, 0, ',', ' ') }} {{ $devise }}
                            </dd>
                        </div>
                    </dl>

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

                    <p class="text-xs text-brand-600 dark:text-brand-300">
                        Solde recalculé en direct : le montant enregistré correspond à
                        <span class="font-semibold">prix total - avance</span>.
                    </p>
                </div>
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Raccourcis</h2>
                </div>

                <div class="space-y-2 p-5">
                    <a href="{{ route('caisse.paiements.create', ['commande_id' => $commande->id]) }}" class="cf-btn-accent w-full">
                        <i class="fa-solid fa-sack-dollar" aria-hidden="true"></i>
                        Enregistrer un paiement
                    </a>

                    @if ($commande->client)
                        <a href="{{ route('mesures.index', $commande->client) }}" class="cf-btn-secondary w-full">
                            <i class="fa-solid fa-ruler" aria-hidden="true"></i>
                            Voir les mesures du client
                        </a>
                    @endif

                    <a href="{{ route('planning.index') }}" class="cf-btn-secondary w-full">
                        <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                        Ouvrir le planning
                    </a>
                </div>
            </section>
        </aside>
    </div>
@endsection
