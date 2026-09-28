@extends('layouts.app')

@section('title', $modele->nom)
@section('header_title', $modele->nom)
@section('header_subtitle', $modele->categorieLabel())

@section('content')
    <x-page-header
        :title="$modele->nom"
        :subtitle="$modele->description ? \Illuminate\Support\Str::limit($modele->description, 120) : $modele->categorieLabel()"
        :back="route('catalogue.index')"
    >
        <x-slot:actions>
            <a href="{{ route('commandes.create', ['modele_id' => $modele->id]) }}" class="cf-btn-secondary">
                <i class="fa-solid fa-scissors" aria-hidden="true"></i>
                Commander
            </a>
            <a href="{{ route('modeles.edit', $modele) }}" class="cf-btn-primary">
                <i class="fa-solid fa-pen" aria-hidden="true"></i>
                Modifier
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <section
                class="cf-card overflow-hidden"
                x-data="{
                    photos: {{ $modele->photos->map(fn ($photo) => route('modeles.photo', $photo))->toJson() }},
                    active: 0,
                }"
            >
                <div class="relative aspect-[4/3] bg-brand-100 dark:bg-white/5">
                    @forelse ($modele->photos as $photo)
                        <img
                            x-show="active === {{ $loop->index }}"
                            x-cloak="{{ $loop->index !== 0 ? true : false }}"
                            src="{{ route('modeles.photo', $photo) }}"
                            alt="{{ $modele->nom }} — photo {{ $loop->iteration }}"
                            class="absolute inset-0 h-full w-full object-cover"
                        >
                    @empty
                        <div class="flex h-full w-full items-center justify-center text-5xl text-brand-300 dark:text-white/15">
                            <i class="{{ $modele->categorieIcon() }}" aria-hidden="true"></i>
                        </div>
                    @endforelse

                    @if ($modele->photos->count() > 1)
                        <button
                            type="button"
                            @click="active = active === 0 ? {{ $modele->photos->count() - 1 }} : active - 1"
                            class="absolute top-1/2 left-3 flex size-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/85 text-brand-800 shadow transition hover:bg-white dark:bg-brand-900/85 dark:text-brand-100"
                            aria-label="Photo précédente"
                        >
                            <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
                        </button>
                        <button
                            type="button"
                            @click="active = active === {{ $modele->photos->count() - 1 }} ? 0 : active + 1"
                            class="absolute top-1/2 right-3 flex size-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/85 text-brand-800 shadow transition hover:bg-white dark:bg-brand-900/85 dark:text-brand-100"
                            aria-label="Photo suivante"
                        >
                            <i class="fa-solid fa-chevron-right text-xs" aria-hidden="true"></i>
                        </button>

                        <div class="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-1.5">
                            @foreach ($modele->photos as $photo)
                                <button
                                    type="button"
                                    @click="active = {{ $loop->index }}"
                                    :class="active === {{ $loop->index }} ? 'bg-white w-6' : 'bg-white/50 w-2'"
                                    class="h-2 rounded-full transition-all"
                                    aria-label="Voir la photo {{ $loop->iteration }}"
                                ></button>
                            @endforeach
                        </div>
                    @endif

                    @unless ($modele->is_active)
                        <span class="absolute top-3 left-3 cf-badge bg-stone-800/85 text-white">Inactif</span>
                    @endunless
                </div>

                @if ($modele->photos->count() > 1)
                    <div class="flex gap-2 overflow-x-auto p-3">
                        @foreach ($modele->photos as $photo)
                            <button
                                type="button"
                                @click="active = {{ $loop->index }}"
                                :class="active === {{ $loop->index }} ? 'ring-2 ring-brand-600' : 'opacity-60 hover:opacity-100'"
                                class="size-16 shrink-0 overflow-hidden rounded-lg transition"
                            >
                                <img src="{{ route('modeles.photo', $photo) }}" alt="" class="h-full w-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </section>

            @if ($modele->description)
                <section class="cf-card mt-6 p-5">
                    <h2 class="font-serif text-lg font-semibold">Description</h2>
                    <p class="mt-2 text-sm leading-relaxed whitespace-pre-line text-brand-700 dark:text-brand-200">
                        {{ $modele->description }}
                    </p>
                </section>
            @endif

            <section class="cf-card mt-6 overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Commandes avec ce modèle</h2>
                    <span class="text-xs text-brand-500">5 dernières</span>
                </div>

                @if ($modele->commandes->isEmpty())
                    <x-empty-state
                        icon="fa-solid fa-scissors"
                        title="Aucune commande"
                        message="Ce modèle n'a pas encore été commandé."
                        :action="route('commandes.create', ['modele_id' => $modele->id])"
                        action-label="Créer une commande"
                    />
                @else
                    <div class="overflow-x-auto">
                        <table class="cf-table">
                            <thead>
                                <tr>
                                    <th>Numéro</th>
                                    <th>Client</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                    <th class="text-right">Solde</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($modele->commandes as $commande)
                                    <tr>
                                        <td data-label="Numéro">
                                            <a href="{{ route('commandes.show', $commande) }}" class="cf-link font-semibold">
                                                {{ $commande->numero }}
                                            </a>
                                        </td>
                                        <td data-label="Client">{{ $commande->client?->nom ?? '—' }}</td>
                                        <td data-label="Statut">
                                            <span class="cf-badge {{ $commande->statutBadgeClass() }}">
                                                {{ $commande->statutLabel() }}
                                            </span>
                                        </td>
                                        <td data-label="Date" class="tabular-nums">{{ $commande->date_commande->format('d/m/Y') }}</td>
                                        <td data-label="Solde" class="text-right tabular-nums">
                                            {{ number_format((float) $commande->solde, 0, ',', ' ') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        <div class="space-y-6">
            <section class="cf-card p-5">
                <h2 class="font-serif text-lg font-semibold">Informations</h2>

                <dl class="mt-4 space-y-3.5 text-sm">
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-brand-500">Catégorie</dt>
                        <dd>
                            <span class="cf-badge {{ $modele->categorieBadgeClass() }}">
                                <i class="{{ $modele->categorieIcon() }} text-[0.7em]" aria-hidden="true"></i>
                                {{ $modele->categorieLabel() }}
                            </span>
                        </dd>
                    </div>

                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-brand-500">Prix indicatif</dt>
                        <dd class="text-right font-semibold tabular-nums">
                            @if ($modele->prix_indicatif)
                                {{ number_format((float) $modele->prix_indicatif, 0, ',', ' ') }} FCFA
                            @else
                                <span class="text-brand-400">Non défini</span>
                            @endif
                        </dd>
                    </div>

                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-brand-500">Tissu conseillé</dt>
                        <dd class="text-right font-medium">{{ $modele->tissu_conseille ?: '—' }}</dd>
                    </div>

                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-brand-500">Durée estimée</dt>
                        <dd class="text-right font-medium">{{ $modele->duree_estimate ?: '—' }}</dd>
                    </div>

                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-brand-500">Photos</dt>
                        <dd class="text-right font-medium tabular-nums">{{ $modele->photos->count() }}</dd>
                    </div>

                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-brand-500">Statut</dt>
                        <dd class="text-right">
                            @if ($modele->is_active)
                                <span class="cf-badge bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300">Actif</span>
                            @else
                                <span class="cf-badge bg-stone-100 text-stone-700 dark:bg-white/10 dark:text-stone-300">Inactif</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="cf-card p-5">
                <h2 class="font-serif text-lg font-semibold">Gérer</h2>
                <div class="mt-4 space-y-2">
                    <a href="{{ route('modeles.edit', $modele) }}" class="cf-btn-secondary w-full">
                        <i class="fa-solid fa-pen" aria-hidden="true"></i>
                        Modifier ce modèle
                    </a>
                    <a href="{{ route('commandes.create', ['modele_id' => $modele->id]) }}" class="cf-btn-secondary w-full">
                        <i class="fa-solid fa-scissors" aria-hidden="true"></i>
                        Nouvelle commande
                    </a>

                    <x-delete-form
                        :action="route('modeles.destroy', $modele)"
                        title="Supprimer le modèle"
                        message="Le modèle « {{ $modele->nom }} » et ses photos seront supprimés définitivement. Continuer ?"
                        label="Supprimer le modèle"
                        class="cf-btn-danger w-full"
                    />
                </div>
            </section>
        </div>
    </div>
@endsection
