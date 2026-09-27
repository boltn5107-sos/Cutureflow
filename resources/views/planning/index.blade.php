@extends('layouts.app')

@section('title', 'Planning')
@section('header_title', 'Planning')
@section('header_subtitle', 'Rendez-vous, essayages et livraisons de l\'atelier')

@section('content')
    @php
        $entetesSemaine = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        $initialesSemaine = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];

        $baseFiltres = array_filter([
            'q' => $filtres['q'] ?? null,
            'type' => $filtres['type'] ?? null,
        ], fn ($valeur) => $valeur !== null && $valeur !== '');

        $nbEvenements = $evenements->count();
        $nbLivraisons = $commandesALivrer->flatten()->count();
    @endphp

    <x-page-header
        :title="'Planning — '.ucfirst($mois->translatedFormat('F Y'))"
        :subtitle="$nbEvenements.' événement(s) planifié(s) et '.$nbLivraisons.' livraison(s) prévue(s) sur le mois.'"
    >
        <x-slot:actions>
            <a href="{{ route('planning.create', ['date' => today()->toDateString()]) }}" class="cf-btn-primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Nouvel événement
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="cf-card mb-4 flex flex-wrap items-center justify-between gap-3 p-4">
        <a
            href="{{ route('planning.index', $baseFiltres + ['mois' => $moisPrecedent]) }}"
            class="cf-btn-secondary cf-btn-sm"
            aria-label="Voir le mois précédent"
        >
            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            Mois précédent
        </a>

        <p class="font-serif text-lg font-semibold tracking-tight capitalize sm:text-xl">
            {{ $mois->translatedFormat('F Y') }}
        </p>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('planning.index', $baseFiltres) }}" class="cf-btn-ghost cf-btn-sm">
                <i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i>
                Aujourd'hui
            </a>

            <a
                href="{{ route('planning.index', $baseFiltres + ['mois' => $moisSuivant]) }}"
                class="cf-btn-secondary cf-btn-sm"
                aria-label="Voir le mois suivant"
            >
                Mois suivant
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </a>
        </div>
    </div>

    <form
        method="GET"
        action="{{ route('planning.index') }}"
        class="cf-card mb-4 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end"
    >
        <x-form.text
            name="q"
            label="Rechercher"
            placeholder="Titre, lieu, client"
            :value="$filtres['q'] ?? null"
        />

        <x-form.select
            name="type"
            label="Type"
            :options="$typeOptions"
            :value="$filtres['type'] ?? null"
            placeholder="Tous les types"
        />

        <x-form.text
            name="mois"
            label="Mois affiché"
            type="month"
            :value="$mois->format('Y-m')"
        />

        <div class="flex flex-wrap items-center gap-2">
            <button type="submit" class="cf-btn-primary">
                <i class="fa-solid fa-filter" aria-hidden="true"></i>
                Filtrer
            </button>

            <a href="{{ route('planning.index') }}" class="cf-btn-ghost">
                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                Réinitialiser
            </a>
        </div>
    </form>

    <section class="cf-card overflow-hidden">
        <div class="cf-card-header">
            <h2 class="font-serif text-lg font-semibold capitalize">{{ $mois->translatedFormat('F Y') }}</h2>
            <span class="text-xs text-brand-500 dark:text-brand-400">
                Faites défiler le calendrier horizontalement sur petit écran.
            </span>
        </div>

        <div class="overflow-x-auto">
            <div class="min-w-[56rem]">
                <div class="grid grid-cols-7 border-b border-brand-200/70 bg-brand-50/60 dark:border-white/10 dark:bg-white/[0.02]">
                    @foreach ($entetesSemaine as $index => $entete)
                        <div class="px-2 py-2.5 text-center text-[0.65rem] font-semibold tracking-wide text-brand-700 uppercase dark:text-brand-200">
                            <span class="sm:hidden">{{ $initialesSemaine[$index] }}</span>
                            <span class="hidden sm:inline">{{ $entete }}</span>
                        </div>
                    @endforeach
                </div>

                @foreach ($grille as $semaine)
                    <div class="grid grid-cols-7">
                        @foreach ($semaine as $jour)
                            @php
                                $commandesDuJour = $jour['date']->lt(today())
                                    ? ($commandesALivrer[$jour['cle']] ?? collect())
                                    : collect();

                                $fondJour = match (true) {
                                    $jour['aujourdhui'] => 'bg-brand-100 dark:bg-brand-accent/10',
                                    ! $jour['dansMois'] => 'bg-stone-100/60 dark:bg-white/[0.02]',
                                    $jour['weekend'] => 'bg-brand-50/70 dark:bg-white/[0.02]',
                                    default => '',
                                };
                            @endphp

                            <div
                                @class([
                                    'flex min-h-28 flex-col gap-1.5 border-r border-b border-brand-100 p-2 last:border-r-0 dark:border-white/5',
                                    $fondJour,
                                    'opacity-60' => ! $jour['dansMois'],
                                ])
                            >
                                <div class="flex items-center justify-between gap-1">
                                    <span
                                        @class([
                                            'flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold tabular-nums',
                                            'bg-brand-800 text-brand-50 dark:bg-brand-400 dark:text-brand-900' => $jour['aujourdhui'],
                                            'text-brand-400 dark:text-brand-500' => ! $jour['dansMois'] && ! $jour['aujourdhui'],
                                            'text-brand-800 dark:text-brand-100' => $jour['dansMois'] && ! $jour['aujourdhui'],
                                        ])
                                    >
                                        {{ $jour['date']->day }}
                                    </span>

                                    <a
                                        href="{{ route('planning.create', ['date' => $jour['cle']]) }}"
                                        class="flex size-6 items-center justify-center rounded-lg text-brand-500 transition hover:bg-brand-100 hover:text-brand-800 dark:text-brand-300 dark:hover:bg-white/10 dark:hover:text-brand-100"
                                        aria-label="Ajouter un événement le {{ $jour['date']->translatedFormat('j F Y') }}"
                                    >
                                        <i class="fa-solid fa-plus text-[0.65rem]" aria-hidden="true"></i>
                                    </a>
                                </div>

                                @foreach ($jour['evenements'] as $evenement)
                                    <a
                                        href="{{ route('planning.edit', $evenement) }}"
                                        class="block rounded-lg border border-brand-200 bg-white p-1.5 transition hover:border-brand-400 dark:border-white/10 dark:bg-white/5 dark:hover:border-brand-400"
                                    >
                                        <span class="inline-flex max-w-full items-center gap-1 rounded-full px-1.5 py-0.5 text-[0.6rem] font-medium {{ $evenement->typeBadgeClass() }}">
                                            <i class="{{ $evenement->typeIcon() }} text-[0.65em]" aria-hidden="true"></i>
                                            <span class="truncate">{{ $evenement->typeLabel() }}</span>
                                        </span>

                                        <span class="mt-1 block truncate text-xs font-semibold">{{ $evenement->titre }}</span>

                                        <span class="mt-0.5 block truncate text-[0.65rem] text-brand-500 dark:text-brand-400">
                                            {{ $evenement->heureFormatee() }}
                                            @if ($evenement->client)
                                                · {{ $evenement->client->nom }}
                                            @endif
                                        </span>
                                    </a>
                                @endforeach

                                @if ($commandesDuJour->isNotEmpty())
                                    <div class="mt-auto space-y-1 border-t border-dashed border-red-200 pt-1.5 dark:border-red-500/25">
                                        @foreach ($commandesDuJour as $commande)
                                            <a
                                                href="{{ route('commandes.show', $commande) }}"
                                                class="block rounded-lg border border-red-200 bg-red-50 px-1.5 py-1 text-left text-[0.65rem] text-red-700 transition hover:bg-red-100 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300 dark:hover:bg-red-500/20"
                                            >
                                                <i class="fa-solid fa-box-open" aria-hidden="true"></i>
                                                <span class="font-semibold">{{ $commande->numero }}</span>
                                                @if ($commande->client)
                                                    <span class="block truncate">
                                                        {{ $commande->client->nom }}
                                                    </span>
                                                @endif
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <section class="cf-card overflow-hidden lg:col-span-2">
            <div class="cf-card-header">
                <h2 class="font-serif text-lg font-semibold">Légende</h2>
                <span class="text-xs text-brand-500 dark:text-brand-400">Types d'événement du planning</span>
            </div>

            <ul class="grid gap-3 p-5 sm:grid-cols-2">
                @foreach ($typeOptions as $valeur => $libelle)
                    @php $enumType = \App\Enums\RendezVousType::tryFrom((string) $valeur); @endphp

                    <li class="flex items-center gap-2.5">
                        <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg {{ $enumType?->badgeClass() ?? 'bg-stone-100 text-stone-700 dark:bg-white/10 dark:text-stone-300' }}">
                            <i class="{{ $enumType?->icon() ?? 'fa-regular fa-calendar' }} text-sm" aria-hidden="true"></i>
                        </span>
                        <span class="min-w-0 text-sm font-medium">{{ $libelle }}</span>
                    </li>
                @endforeach

                <li class="flex items-center gap-2.5">
                    <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-600 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
                        <i class="fa-solid fa-box-open text-sm" aria-hidden="true"></i>
                    </span>
                    <span class="min-w-0 text-sm font-medium">Livraison en retard</span>
                </li>
            </ul>
        </section>

        <aside class="cf-card overflow-hidden lg:col-span-1">
            <div class="cf-card-header">
                <h2 class="font-serif text-lg font-semibold">À savoir</h2>
            </div>

            <div class="space-y-2 p-5 text-sm text-brand-600 dark:text-brand-300">
                <p>
                    Cliquez sur le « + » d'un jour pour créer directement un événement à cette date.
                </p>
                <p>
                    Les livraisons en retard des commandes ouvertes s'affichent automatiquement sur les jours passés.
                </p>
                <p>
                    Le filtre de recherche et le filtre de type s'appliquent au calendrier et à la liste du mois.
                </p>
            </div>
        </aside>
    </div>
@endsection
