@extends('layouts.app')

@section('title', 'Catalogue')
@section('header_title', 'Catalogue de modèles')
@section('header_subtitle', 'Vos modèles, leurs prix et leurs photos')

@section('content')
    <x-page-header
        title="Catalogue de modèles"
        subtitle="Présentez vos réalisation à vos clients et associez-les à vos commandes."
    >
        <x-slot:actions>
            <a href="{{ route('catalogue.index') }}" class="cf-btn-secondary">
                <i class="fa-solid fa-list" aria-hidden="true"></i>
                Liste
            </a>
            <a href="{{ route('modeles.create') }}" class="cf-btn-primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Nouveau modèle
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-5 flex flex-wrap items-center gap-2">
        <a
            href="{{ route('catalogue.index', array_filter(['categorie' => $filtres['categorie'] ?? null])) }}"
            @class([
                'cf-badge px-3 py-1.5',
                'bg-brand-800 text-brand-50 dark:bg-brand-400 dark:text-brand-900' => empty($filtres['categorie']),
                'bg-white text-brand-700 ring-1 ring-brand-200 ring-inset hover:bg-brand-50 dark:bg-white/5 dark:text-brand-200 dark:ring-white/10 dark:hover:bg-white/10' => ! empty($filtres['categorie']),
            ])
        >
            Toutes
            <span class="ml-1 opacity-70">{{ array_sum($compteurs) }}</span>
        </a>

        @foreach ($categorieOptions as $value => $label)
            <a
                href="{{ route('catalogue.index', array_filter(['categorie' => $value])) }}"
                @class([
                    'cf-badge px-3 py-1.5',
                    'bg-brand-800 text-brand-50 dark:bg-brand-400 dark:text-brand-900' => ($filtres['categorie'] ?? null) === $value,
                    'bg-white text-brand-700 ring-1 ring-brand-200 ring-inset hover:bg-brand-50 dark:bg-white/5 dark:text-brand-200 dark:ring-white/10 dark:hover:bg-white/10' => ($filtres['categorie'] ?? null) !== $value,
                ])
            >
                {{ $label }}
                <span class="ml-1 opacity-70">{{ $compteurs[$value] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('catalogue.index') }}" class="cf-card mb-6 flex flex-wrap items-end gap-3 p-4">
        @if ($filtres['categorie'] ?? null)
            <input type="hidden" name="categorie" value="{{ $filtres['categorie'] }}">
        @endif

        <div class="min-w-56 flex-1">
            <x-form.text
                name="q"
                label="Rechercher un modèle"
                :value="$filtres['q'] ?? null"
                placeholder="Nom, tissu, description…"
            />
        </div>

        <button type="submit" class="cf-btn-secondary">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            Rechercher
        </button>

        @if ($filtres['q'] ?? null)
            <a href="{{ route('catalogue.index', array_filter(['categorie' => $filtres['categorie'] ?? null])) }}" class="cf-btn-ghost">
                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                Réinitialiser
            </a>
        @endif
    </form>

    @if ($modeles->isEmpty())
        <div class="cf-card">
            <x-empty-state
                icon="fa-solid fa-shirt"
                title="Aucun modèle dans le catalogue"
                message="Ajoutez vos premiers modèles pour illustrer vos prestations."
                :action="route('modeles.create')"
                action-label="Ajouter un modèle"
            />
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($modeles as $modele)
                @php $photo = $modele->photoPrincipale() ?? $modele->photos->first(); @endphp

                <article class="cf-card group flex flex-col overflow-hidden transition hover:-translate-y-0.5 hover:shadow-elevated">
                    <a href="{{ route('modeles.show', $modele) }}" class="relative block aspect-[4/3] overflow-hidden bg-brand-100 dark:bg-white/5">
                        @if ($photo)
                            <img
                                src="{{ route('modeles.photo', $photo) }}"
                                alt="{{ $modele->nom }}"
                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                loading="lazy"
                            >
                        @else
                            <span class="flex h-full w-full items-center justify-center text-3xl text-brand-300 dark:text-white/15">
                                <i class="{{ $modele->categorieIcon() }}" aria-hidden="true"></i>
                            </span>
                        @endif

                        @unless ($modele->is_active)
                            <span class="absolute top-3 left-3 cf-badge bg-stone-800/85 text-white">
                                Inactif
                            </span>
                        @endunless

                        @if ($modele->photos->count() > 1)
                            <span class="absolute right-3 bottom-3 flex items-center gap-1 rounded-lg bg-brand-900/75 px-2 py-1 text-[0.65rem] font-medium text-white">
                                <i class="fa-solid fa-images text-[0.6rem]" aria-hidden="true"></i>
                                {{ $modele->photos->count() }}
                            </span>
                        @endif
                    </a>

                    <div class="flex flex-1 flex-col p-5">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="font-serif text-base leading-snug font-semibold">
                                <a href="{{ route('modeles.show', $modele) }}" class="transition hover:text-brand-600 dark:hover:text-brand-300">
                                    {{ $modele->nom }}
                                </a>
                            </h3>
                            <span class="cf-badge {{ $modele->categorieBadgeClass() }} shrink-0">
                                <i class="{{ $modele->categorieIcon() }} text-[0.7em]" aria-hidden="true"></i>
                                {{ $modele->categorieLabel() }}
                            </span>
                        </div>

                        @if ($modele->tissu_conseille)
                            <p class="mt-2 flex items-center gap-1.5 text-xs text-brand-600 dark:text-brand-300">
                                <i class="fa-solid fa-layer-group text-[0.65rem]" aria-hidden="true"></i>
                                {{ $modele->tissu_conseille }}
                            </p>
                        @endif

                        @if ($modele->description)
                            <p class="mt-2 line-clamp-2 text-sm text-brand-600 dark:text-brand-300">
                                {{ $modele->description }}
                            </p>
                        @endif

                        <div class="mt-auto flex items-end justify-between gap-3 pt-4">
                            <p class="font-serif text-lg font-semibold tabular-nums">
                                @if ($modele->prix_indicatif)
                                    {{ number_format((float) $modele->prix_indicatif, 0, ',', ' ') }}
                                    <span class="text-xs font-normal text-brand-500">FCFA</span>
                                @else
                                    <span class="text-sm font-normal text-brand-400">Prix à définir</span>
                                @endif
                            </p>

                            <a
                                href="{{ route('modeles.show', $modele) }}"
                                class="text-xs font-semibold text-brand-600 transition hover:text-brand-800 dark:text-brand-300 dark:hover:text-brand-100"
                            >
                                Détails
                                <i class="fa-solid fa-arrow-right ml-1 text-[0.6rem]" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <x-pagination :paginator="$modeles" label="modèles" class="mt-6" />
    @endif
@endsection
