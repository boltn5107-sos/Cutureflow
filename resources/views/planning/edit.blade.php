@extends('layouts.app')

@section('title', 'Modifier l\'événement')
@section('header_title', 'Modifier l\'événement')
@section('header_subtitle', $rendezVous->titre)

@section('content')
    @php
        $typeCourant = $rendezVous->type instanceof \App\Enums\RendezVousType
            ? $rendezVous->type->value
            : (string) $rendezVous->type;

        $clientsOptions = $clients->mapWithKeys(
            fn ($client) => [$client->id => $client->nom.(filled($client->telephone) ? ' — '.$client->telephone : '')]
        );

        $commandesOptions = $commandes->mapWithKeys(
            fn ($commande) => [
                $commande->id => $commande->numero.($commande->client ? ' — '.$commande->client->nom : ''),
            ]
        );
    @endphp

    <x-page-header
        title="Modifier l'événement"
        :subtitle="$rendezVous->titre.' — '.$rendezVous->typeLabel().' · '.$rendezVous->date_debut?->translatedFormat('D j M Y')"
        :back="route('planning.index', ['mois' => $rendezVous->date_debut?->format('Y-m')])"
    >
        <x-slot:actions>
            @if ($rendezVous->estPasse())
                <span class="cf-badge bg-stone-100 text-stone-700 dark:bg-white/10 dark:text-stone-300">
                    <i class="fa-solid fa-clock-rotate-left text-[0.7em]" aria-hidden="true"></i>
                    Événement passé
                </span>
            @else
                <span class="cf-badge bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300">
                    <i class="fa-regular fa-calendar-check text-[0.7em]" aria-hidden="true"></i>
                    Événement à venir
                </span>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <form
            method="POST"
            action="{{ route('planning.update', $rendezVous) }}"
            class="space-y-6 lg:col-span-2"
        >
            @csrf
            @method('PUT')

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
                        :value="$rendezVous->titre"
                        class="sm:col-span-2"
                    />

                    <x-form.select
                        name="type"
                        label="Type"
                        :options="$typeOptions"
                        :value="$typeCourant"
                        placeholder="Sélectionner un type"
                        required
                    />

                    <x-form.select
                        name="client_id"
                        label="Client"
                        :options="$clientsOptions"
                        :value="$rendezVous->client_id"
                        placeholder="Aucun client"
                        hint="Facultatif : lie l'événement à une fiche client."
                    />

                    <x-form.select
                        name="commande_id"
                        label="Commande"
                        :options="$commandesOptions"
                        :value="$rendezVous->commande_id"
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
                        :value="$rendezVous->date_debut?->format('Y-m-d')"
                    />

                    <x-form.text
                        name="heure_debut"
                        label="Heure de début"
                        type="time"
                        :value="$rendezVous->heure_debut"
                        hint="Facultatif : laissez vide pour un événement sur la journée."
                    />

                    <x-form.text
                        name="date_fin"
                        label="Date de fin"
                        type="date"
                        :value="$rendezVous->date_fin?->format('Y-m-d')"
                        hint="Si l'heure de fin est renseignée, la date de fin devient obligatoire."
                    />

                    <x-form.text
                        name="heure_fin"
                        label="Heure de fin"
                        type="time"
                        :value="$rendezVous->heure_fin"
                        hint="Renseignez l'heure de fin uniquement pour un événement sur un créneau."
                    />

                    <x-form.text
                        name="lieu"
                        label="Lieu"
                        placeholder="Ex. Atelier, domicile du client"
                        maxlength="255"
                        :value="$rendezVous->lieu"
                        class="sm:col-span-2"
                    />

                    <x-form.textarea
                        name="notes"
                        label="Notes"
                        :rows="2"
                        placeholder="Détails utiles à l'atelier…"
                        :value="$rendezVous->notes"
                        hint="2 000 caractères maximum."
                        class="sm:col-span-2"
                    />
                </div>
            </section>

            <div class="flex flex-wrap items-center gap-2">
                <button type="submit" class="cf-btn-primary">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                    Enregistrer
                </button>

                <a
                    href="{{ route('planning.index', ['mois' => $rendezVous->date_debut?->format('Y-m')]) }}"
                    class="cf-btn-secondary"
                >
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    Annuler
                </a>

                <x-delete-form
                    :action="route('planning.destroy', $rendezVous)"
                    title="Supprimer l'événement"
                    :message="'L\'événement « '.$rendezVous->titre.' » sera définitivement supprimé du planning. Continuer ?'"
                    label="Supprimer l'événement"
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
                        <span class="text-sm text-brand-600 dark:text-brand-300">Type</span>
                        <span class="cf-badge {{ $rendezVous->typeBadgeClass() }}">
                            <i class="{{ $rendezVous->typeIcon() }} text-[0.7em]" aria-hidden="true"></i>
                            {{ $rendezVous->typeLabel() }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-brand-600 dark:text-brand-300">Début</span>
                        <span class="font-semibold tabular-nums">
                            {{ $rendezVous->date_debut?->format('d/m/Y') ?? '—' }}
                            · {{ $rendezVous->heureFormatee() }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-brand-600 dark:text-brand-300">Fin</span>
                        <span class="font-semibold tabular-nums">
                            {{ $rendezVous->date_fin?->format('d/m/Y') ?? '—' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-brand-600 dark:text-brand-300">Client</span>
                        <span class="font-semibold">{{ $rendezVous->client?->nom ?? '—' }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-brand-600 dark:text-brand-300">Commande</span>
                        @if ($rendezVous->commande)
                            <a href="{{ route('commandes.show', $rendezVous->commande) }}" class="cf-link font-semibold">
                                {{ $rendezVous->commande->numero }}
                            </a>
                        @else
                            <span class="font-semibold">—</span>
                        @endif
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-brand-600 dark:text-brand-300">Lieu</span>
                        <span class="font-semibold">{{ $rendezVous->lieu ?: '—' }}</span>
                    </div>

                    <p class="text-xs text-brand-600 dark:text-brand-300">
                        Modifié {{ $rendezVous->updated_at?->diffForHumans() ?? 'à l\'instant' }}.
                    </p>
                </div>
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Raccourcis</h2>
                </div>

                <div class="space-y-2 p-5">
                    <a
                        href="{{ route('planning.index', ['mois' => $rendezVous->date_debut?->format('Y-m')]) }}"
                        class="cf-btn-secondary w-full"
                    >
                        <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                        Revenir au planning
                    </a>

                    @if ($rendezVous->client)
                        <a href="{{ route('clients.show', $rendezVous->client) }}" class="cf-btn-secondary w-full">
                            <i class="fa-solid fa-user" aria-hidden="true"></i>
                            Fiche du client
                        </a>
                    @endif
                </div>
            </section>
        </aside>
    </div>
@endsection
