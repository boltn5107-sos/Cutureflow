@extends('layouts.app')

@section('title', 'Nouvel événement')
@section('header_title', 'Nouvel événement')
@section('header_subtitle', 'Planifier un rendez-vous, un essayage, une livraison ou une date importante')

@section('content')
    @php
        $clientsOptions = $clients->mapWithKeys(
            fn ($client) => [$client->id => $client->nom.(filled($client->telephone) ? ' — '.$client->telephone : '')]
        );

        $commandesOptions = $commandes->mapWithKeys(
            fn ($commande) => [
                $commande->id => $commande->numero.($commande->client ? ' — '.$commande->client->nom : ''),
            ]
        );

        $legendeTypes = collect($typeOptions)->map(fn ($libelle, $valeur) => [
            'libelle' => $libelle,
            'icon' => \App\Enums\RendezVousType::tryFrom((string) $valeur)?->icon() ?? 'fa-regular fa-calendar',
        ]);
    @endphp

    <x-page-header
        title="Nouvel événement"
        subtitle="Renseignez le titre, le type et la date. Les horaires sont facultatifs."
        :back="route('planning.index')"
    />

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('planning.store') }}" class="space-y-6 lg:col-span-2">
            @csrf

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Événement</h2>
                </div>

                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <x-form.text
                        name="titre"
                        label="Titre"
                        placeholder="Ex. Essayage de la robe de Marième"
                        maxlength="150"
                        required
                        autofocus
                        class="sm:col-span-2"
                    />

                    <x-form.select
                        name="type"
                        label="Type"
                        :options="$typeOptions"
                        placeholder="Sélectionner un type"
                        required
                    />

                    <x-form.select
                        name="client_id"
                        label="Client"
                        :options="$clientsOptions"
                        placeholder="Aucun client"
                        hint="Facultatif : lie l'événement à une fiche client."
                    />

                    <x-form.select
                        name="commande_id"
                        label="Commande"
                        :options="$commandesOptions"
                        placeholder="Aucune commande"
                        hint="Seules les commandes ouvertes de l'atelier sont proposées."
                    />
                </div>
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Date et horaires</h2>
                </div>

                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <x-form.text
                        name="date_debut"
                        label="Date de début"
                        type="date"
                        required
                        :value="$dateParDefaut"
                    />

                    <x-form.text
                        name="heure_debut"
                        label="Heure de début"
                        type="time"
                        hint="Facultatif : laissez vide pour un événement sur la journée."
                    />

                    <x-form.text
                        name="date_fin"
                        label="Date de fin"
                        type="date"
                        hint="Si l'heure de fin est renseignée, la date de fin devient obligatoire."
                    />

                    <x-form.text
                        name="heure_fin"
                        label="Heure de fin"
                        type="time"
                        hint="Renseignez l'heure de fin uniquement pour un événement sur un créneau."
                    />

                    <x-form.text
                        name="lieu"
                        label="Lieu"
                        placeholder="Ex. Atelier, domicile du client"
                        maxlength="255"
                        class="sm:col-span-2"
                    />

                    <x-form.textarea
                        name="notes"
                        label="Notes"
                        :rows="2"
                        placeholder="Détails utiles à l'atelier…"
                        hint="2 000 caractères maximum."
                        class="sm:col-span-2"
                    />
                </div>
            </section>

            <div class="flex flex-wrap items-center gap-2">
                <button type="submit" class="cf-btn-primary">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    Ajouter au planning
                </button>

                <a href="{{ route('planning.index') }}" class="cf-btn-secondary">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    Annuler
                </a>
            </div>
        </form>

        <aside class="space-y-6 lg:col-span-1">
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Types d'événement</h2>
                </div>

                <ul class="divide-y divide-brand-100 dark:divide-white/5">
                    @foreach ($legendeTypes as $type)
                        <li class="flex items-center gap-2.5 px-5 py-2.5 text-sm">
                            <i class="{{ $type['icon'] }} w-4 shrink-0 text-center text-brand-400" aria-hidden="true"></i>
                            <span class="min-w-0 truncate">{{ $type['libelle'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Bon à savoir</h2>
                </div>

                <div class="space-y-2 p-5 text-sm text-brand-600 dark:text-brand-300">
                    <p>
                        Si l'heure de fin est renseignée, la date de fin est obligatoire et doit être postérieure
                        ou égale à la date de début.
                    </p>
                    <p>
                        L'événement apparaît dans le mois correspondant à sa date de début.
                    </p>
                </div>
            </section>
        </aside>
    </div>
@endsection
