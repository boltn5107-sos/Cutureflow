@extends('layouts.app')

@section('title', 'Commande '.$commande->numero)
@section('header_title', 'Commande '.$commande->numero)
@section('header_subtitle', ($commande->client?->nom ?? 'Client inconnu').' — '.$commande->statutLabel())

@section('content')
    @php
        $devise = config('coutureflow.currency');
        $aujourdhui = now()->toDateString();
        $enRetard = $commande->estEnRetard();
        $joursLivraison = $commande->livraisonDansJours();

        $totalEncaisse = $commande->totalEncaisse();
        $resteAPayer = $commande->resteAPayer();
        $tauxEncaisse = (float) $commande->prix_total > 0
            ? min(100, max(0, (int) round($totalEncaisse / (float) $commande->prix_total * 100)))
            : 0;
    @endphp

    <x-page-header
        :title="$commande->numero"
        :subtitle="($commande->client?->nom ?? 'Client inconnu').' — '.$commande->statutLabel()"
        :back="route('commandes.index')"
    >
        <x-slot:actions>
            <a href="{{ route('caisse.paiements.create', ['commande_id' => $commande->id]) }}" class="cf-btn-accent">
                <i class="fa-solid fa-sack-dollar" aria-hidden="true"></i>
                Enregistrer un paiement
            </a>

            <a href="{{ route('commandes.edit', $commande) }}" class="cf-btn-secondary">
                <i class="fa-solid fa-pen" aria-hidden="true"></i>
                Modifier
            </a>

            <x-delete-form
                :action="route('commandes.destroy', $commande)"
                title="Annuler la commande"
                message="La commande sera marquée comme annulée puis supprimée. Continuer ?"
                label="Annuler la commande"
                icon="fa-solid fa-ban"
                class="cf-btn-danger"
            />
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Suivi du statut</h2>
                    <span class="cf-badge {{ $commande->statutBadgeClass() }}">
                        <i class="{{ $commande->getStatutEnum()->icon() }} text-[0.7em]" aria-hidden="true"></i>
                        {{ $commande->statutLabel() }}
                    </span>
                </div>

                <form method="POST" action="{{ route('commandes.statut', $commande) }}">
                    @csrf
                    @method('PATCH')

                    <div class="grid gap-4 p-5 sm:grid-cols-2">
                        <x-form.select
                            name="statut"
                            label="Nouveau statut"
                            :options="$statutOptions"
                            :value="$commande->getStatutEnum()->value"
                            :empty="false"
                            required
                            hint="La date de livraison réelle est renseignée automatiquement au statut Livrée."
                        />

                        <x-form.text
                            name="date_livraison_reelle"
                            label="Date de livraison réelle"
                            type="date"
                            :value="$commande->date_livraison_reelle?->format('Y-m-d')"
                            :max="$aujourdhui"
                            hint="Facultative : renseignée automatiquement pour une livraison."
                        />
                    </div>

                    <div class="flex flex-wrap items-center gap-2 border-t border-brand-200/70 px-5 py-4 dark:border-white/10">
                        <button type="submit" class="cf-btn-primary">
                            <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>
                            Mettre à jour le statut
                        </button>

                        @if ($enRetard)
                            <span class="cf-badge bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-300">
                                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                                {{ $commande->joursDeRetard() }} j de retard
                            </span>
                        @endif
                    </div>
                </form>
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Financier</h2>
                    @if ($commande->estSoldePaye())
                        <span class="cf-badge bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300">
                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                            Solde soldé
                        </span>
                    @endif
                </div>

                <div class="space-y-4 p-5">
                    <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="rounded-lg bg-brand-50/60 p-3 dark:bg-white/5">
                            <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Prix total</dt>
                            <dd class="mt-1 font-serif text-xl font-semibold tabular-nums">
                                {{ number_format((float) $commande->prix_total, 0, ',', ' ') }} {{ $devise }}
                            </dd>
                        </div>
                        <div class="rounded-lg bg-brand-50/60 p-3 dark:bg-white/5">
                            <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Avance</dt>
                            <dd class="mt-1 font-serif text-xl font-semibold tabular-nums">
                                {{ number_format((float) $commande->avance, 0, ',', ' ') }} {{ $devise }}
                            </dd>
                        </div>
                        <div class="rounded-lg bg-brand-50/60 p-3 dark:bg-white/5">
                            <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Solde</dt>
                            <dd class="mt-1 font-serif text-xl font-semibold text-brand-accent tabular-nums">
                                {{ number_format((float) $commande->solde, 0, ',', ' ') }} {{ $devise }}
                            </dd>
                        </div>
                        <div class="rounded-lg bg-brand-50/60 p-3 dark:bg-white/5">
                            <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Total encaissé</dt>
                            <dd class="mt-1 font-serif text-xl font-semibold text-emerald-600 tabular-nums dark:text-emerald-400">
                                {{ number_format($totalEncaisse, 0, ',', ' ') }} {{ $devise }}
                            </dd>
                        </div>
                        <div class="rounded-lg bg-brand-50/60 p-3 dark:bg-white/5">
                            <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Reste à payer</dt>
                            <dd class="mt-1 font-serif text-xl font-semibold text-brand-accent tabular-nums">
                                {{ number_format($resteAPayer, 0, ',', ' ') }} {{ $devise }}
                            </dd>
                        </div>
                    </dl>

                    <div>
                        <div class="mb-1.5 flex items-center justify-between text-xs text-brand-600 dark:text-brand-300">
                            <span>Progression des encaissements</span>
                            <span class="font-semibold tabular-nums">{{ $tauxEncaisse }} %</span>
                        </div>
                        <div
                            class="h-2 w-full overflow-hidden rounded-full bg-brand-100 dark:bg-white/10"
                            role="progressbar"
                            aria-label="Montant encaissé sur le prix total"
                            aria-valuenow="{{ $tauxEncaisse }}"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        >
                            <div
                                class="h-full rounded-full bg-brand-accent"
                                style="width: {{ $tauxEncaisse }}%"
                            ></div>
                        </div>
                    </div>

                    @if ($prochainPaiement)
                        <p class="flex items-center gap-2 rounded-lg border border-brand-200 bg-brand-50/60 px-3 py-2 text-sm text-brand-700 dark:border-white/10 dark:bg-white/5 dark:text-brand-200">
                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                            Prochain paiement à enregistrer :
                            <span class="font-semibold">{{ $prochainPaiement }}</span>
                        </p>
                    @endif
                </div>

                <div class="cf-divider">
                    <div class="cf-card-header border-b-0">
                        <h3 class="font-serif text-base font-semibold">Paiements enregistrés</h3>
                        <span class="text-xs text-brand-500 dark:text-brand-400">
                            <span class="font-semibold tabular-nums">{{ $commande->paiements->count() }}</span>
                            paiement(s)
                        </span>
                    </div>

                    @if ($commande->paiements->isEmpty())
                        <x-empty-state
                            icon="fa-solid fa-receipt"
                            title="Aucun paiement"
                            message="Enregistrez l'avance ou le solde encaissé pour cette commande."
                            :action="route('caisse.paiements.create', ['commande_id' => $commande->id])"
                            action-label="Enregistrer un paiement"
                        />
                    @else
                        <div class="overflow-x-auto">
                            <table class="cf-table">
                                <caption class="sr-only">Paiements de la commande {{ $commande->numero }}</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Date</th>
                                        <th scope="col">Type</th>
                                        <th scope="col">Méthode</th>
                                        <th scope="col">Détails</th>
                                        <th scope="col" class="text-right">Montant</th>
                                        <th scope="col" class="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($commande->paiements as $paiement)
                                        <tr>
                                            <td class="whitespace-nowrap tabular-nums">
                                                {{ $paiement->date_paiement?->format('d/m/Y') ?? '—' }}
                                            </td>
                                            <td>
                                                <span class="cf-badge {{ $paiement->typeBadgeClass() }}">
                                                    {{ $paiement->typeLabel() }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="flex items-center gap-1.5 whitespace-nowrap">
                                                    <i class="{{ $paiement->methodeIcon() }} w-4 text-center text-brand-400" aria-hidden="true"></i>
                                                    {{ $paiement->methodeLabel() }}
                                                </span>
                                                @if ($paiement->reference)
                                                    <span class="block text-xs text-brand-500 dark:text-brand-400">{{ $paiement->reference }}</span>
                                                @endif
                                            </td>
                                            <td class="max-w-xs text-xs">
                                                @if ($paiement->description)
                                                    <span class="line-clamp-2">{{ $paiement->description }}</span>
                                                @else
                                                    <span class="text-brand-400">—</span>
                                                @endif
                                                @if ($paiement->creator)
                                                    <span class="block text-[0.65rem] text-brand-400">
                                                        Saisi par {{ $paiement->creator->name }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-right font-semibold tabular-nums">
                                                {{ number_format((float) $paiement->montant, 0, ',', ' ') }} {{ $devise }}
                                            </td>
                                            <td>
                                                <div class="flex items-center justify-end gap-2">
                                                    <a
                                                        href="{{ route('caisse.paiements.show', $paiement) }}"
                                                        class="cf-btn-secondary cf-btn-sm"
                                                        aria-label="Voir le paiement du {{ $paiement->date_paiement?->format('d/m/Y') }}"
                                                    >
                                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                                        Voir
                                                    </a>

                                                    <x-delete-form
                                                        :action="route('caisse.paiements.destroy', $paiement)"
                                                        title="Supprimer le paiement"
                                                        message="Ce paiement sera définitivement supprimé de la caisse. Continuer ?"
                                                        label="Supprimer"
                                                        class="cf-btn-danger cf-btn-sm"
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Détails de la commande</h2>
                </div>

                <dl class="grid gap-4 p-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Type</dt>
                        <dd class="mt-0.5 text-sm font-medium">{{ $commande->type ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Tissu</dt>
                        <dd class="mt-0.5 text-sm font-medium">{{ $commande->tissu ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Date de commande</dt>
                        <dd class="mt-0.5 text-sm font-medium">{{ $commande->date_commande?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Livraison prévue</dt>
                        <dd class="mt-0.5 text-sm font-medium {{ $enRetard ? 'text-red-600 dark:text-red-400' : '' }}">
                            {{ $commande->date_livraison_prevue?->format('d/m/Y') ?? '—' }}
                            @if ($enRetard)
                                <span class="block text-xs font-medium">
                                    {{ $commande->joursDeRetard() }} j de retard
                                </span>
                            @elseif ($joursLivraison !== null)
                                <span class="block text-xs font-normal text-brand-500 dark:text-brand-400">
                                    @if ($joursLivraison === 0)
                                        Livraison aujourd'hui
                                    @elseif ($joursLivraison > 0)
                                        Dans {{ $joursLivraison }} jour(s)
                                    @else
                                        Échéance dépassée
                                    @endif
                                </span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Livraison réelle</dt>
                        <dd class="mt-0.5 text-sm font-medium">{{ $commande->date_livraison_reelle?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    @if ($commande->creator)
                        <div>
                            <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Créée par</dt>
                            <dd class="mt-0.5 text-sm font-medium">{{ $commande->creator->name }}</dd>
                        </div>
                    @endif
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Description</dt>
                        <dd class="mt-0.5 text-sm whitespace-pre-line">{{ $commande->description ?: '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Notes internes</dt>
                        <dd class="mt-0.5 text-sm whitespace-pre-line">{{ $commande->notes ?: '—' }}</dd>
                    </div>
                </dl>
            </section>

            @if ($commande->mesures->isNotEmpty())
                <section class="cf-card overflow-hidden">
                    <div class="cf-card-header">
                        <h2 class="font-serif text-lg font-semibold">Relevé de mesures</h2>
                        @if ($commande->client)
                            <a href="{{ route('mesures.index', $commande->client) }}" class="cf-link text-xs">
                                Gérer les mesures
                                <i class="fa-solid fa-arrow-right ml-1" aria-hidden="true"></i>
                            </a>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        <table class="cf-table">
                            <caption class="sr-only">Mesures enregistrées pour cette commande</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Libellé</th>
                                    <th scope="col">Valeur</th>
                                    <th scope="col">Date de mesure</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($commande->mesures as $mesure)
                                    <tr>
                                        <td class="font-medium">{{ $mesure->libelle }}</td>
                                        <td class="tabular-nums">{{ $mesure->valeurFormatee() }}</td>
                                        <td class="whitespace-nowrap tabular-nums">
                                            {{ $mesure->date_mesure?->format('d/m/Y') ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            @if ($commande->rendezVous->isNotEmpty())
                <section class="cf-card overflow-hidden">
                    <div class="cf-card-header">
                        <h2 class="font-serif text-lg font-semibold">Rendez-vous liés</h2>
                        <a href="{{ route('planning.index') }}" class="cf-link text-xs">
                            Ouvrir le planning
                            <i class="fa-solid fa-arrow-right ml-1" aria-hidden="true"></i>
                        </a>
                    </div>

                    <ul class="divide-y divide-brand-100 dark:divide-white/5">
                        @foreach ($commande->rendezVous as $rdv)
                            <li class="flex items-center gap-3 px-5 py-3">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $rdv->typeBadgeClass() }}">
                                    <i class="{{ $rdv->typeIcon() }} text-xs" aria-hidden="true"></i>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold">{{ $rdv->titre }}</span>
                                    <span class="block truncate text-xs text-brand-500 dark:text-brand-400">
                                        {{ $rdv->typeLabel() }} — {{ $rdv->date_debut?->format('d/m/Y') }} à {{ $rdv->heureFormatee() }}
                                    </span>
                                </span>
                                @if (filled($rdv->lieu))
                                    <span class="hidden truncate text-xs text-brand-500 sm:block dark:text-brand-400">
                                        <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                                        {{ $rdv->lieu }}
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <aside class="space-y-6 lg:col-span-1">
            @if ($commande->client)
                <section class="cf-card overflow-hidden">
                    <div class="cf-card-header">
                        <h2 class="font-serif text-lg font-semibold">Client</h2>
                    </div>

                    <div class="space-y-4 p-5">
                        <div class="flex items-center gap-3">
                            @if ($commande->client->hasPhoto())
                                <img
                                    src="{{ $commande->client->photoUrl() }}"
                                    alt="Photo de {{ $commande->client->nom }}"
                                    class="size-12 shrink-0 rounded-full object-cover"
                                >
                            @else
                                <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700 dark:bg-white/10 dark:text-brand-200">
                                    {{ $commande->client->initiales() }}
                                </span>
                            @endif

                            <div class="min-w-0">
                                <a href="{{ route('clients.show', $commande->client) }}" class="cf-link block truncate font-semibold">
                                    {{ $commande->client->nom }}
                                </a>
                                @if (filled($commande->client->telephone))
                                    <a href="tel:{{ $commande->client->telephone }}" class="block truncate text-xs text-brand-500 dark:text-brand-400">
                                        <i class="fa-solid fa-phone" aria-hidden="true"></i>
                                        {{ $commande->client->telephone }}
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-1">
                            <a href="{{ route('clients.show', $commande->client) }}" class="cf-btn-secondary w-full">
                                <i class="fa-solid fa-user" aria-hidden="true"></i>
                                Fiche client
                            </a>
                            <a href="{{ route('mesures.index', $commande->client) }}" class="cf-btn-secondary w-full">
                                <i class="fa-solid fa-ruler" aria-hidden="true"></i>
                                Mesures du client
                            </a>
                        </div>
                    </div>
                </section>
            @endif

            @if ($commande->modele)
                @php $modele = $commande->modele; @endphp

                <section class="cf-card overflow-hidden">
                    <div class="cf-card-header">
                        <h2 class="font-serif text-lg font-semibold">Modèle</h2>
                        <span class="cf-badge {{ $modele->categorieBadgeClass() }}">
                            <i class="{{ $modele->categorieIcon() }} text-[0.7em]" aria-hidden="true"></i>
                            {{ $modele->categorieLabel() }}
                        </span>
                    </div>

                    <div class="space-y-4 p-5">
                        <a href="{{ route('modeles.show', $modele) }}" class="cf-link font-semibold">
                            {{ $modele->nom }}
                        </a>

                        @if (filled($modele->description))
                            <p class="text-sm text-brand-600 dark:text-brand-300">{{ $modele->description }}</p>
                        @endif

                        <div class="grid gap-2 sm:grid-cols-2">
                            <div>
                                <p class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Tissu conseillé</p>
                                <p class="mt-0.5 text-sm font-medium">{{ $modele->tissu_conseille ?: '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Prix indicatif</p>
                                <p class="mt-0.5 text-sm font-medium tabular-nums">
                                    {{ number_format((float) $modele->prix_indicatif, 0, ',', ' ') }} {{ $devise }}
                                </p>
                            </div>
                        </div>

                        @if ($modele->photos->isNotEmpty())
                            <div class="grid grid-cols-3 gap-2">
                                @foreach ($modele->photos as $photo)
                                    <a
                                        href="{{ route('modeles.photo', $photo) }}"
                                        target="_blank"
                                        rel="noopener"
                                        aria-label="Agrandir la photo : {{ $modele->nom }}"
                                        class="block overflow-hidden rounded-lg border border-brand-200 dark:border-white/10"
                                    >
                                        <img
                                            src="{{ route('modeles.photo', $photo) }}"
                                            alt="{{ $modele->nom }}"
                                            class="h-20 w-full object-cover transition hover:opacity-90"
                                            loading="lazy"
                                        >
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <a href="{{ route('modeles.show', $modele) }}" class="cf-btn-secondary w-full">
                            <i class="fa-solid fa-book-open" aria-hidden="true"></i>
                            Voir la fiche modèle
                        </a>
                    </div>
                </section>
            @endif

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Raccourcis</h2>
                </div>

                <div class="space-y-2 p-5">
                    <a href="{{ route('caisse.paiements.create', ['commande_id' => $commande->id]) }}" class="cf-btn-accent w-full">
                        <i class="fa-solid fa-sack-dollar" aria-hidden="true"></i>
                        Enregistrer un paiement
                    </a>

                    <a href="{{ route('commandes.edit', $commande) }}" class="cf-btn-secondary w-full">
                        <i class="fa-solid fa-pen" aria-hidden="true"></i>
                        Modifier la commande
                    </a>

                    <a href="{{ route('caisse.index') }}" class="cf-btn-secondary w-full">
                        <i class="fa-solid fa-wallet" aria-hidden="true"></i>
                        Ouvrir la caisse
                    </a>

                    <a href="{{ route('commandes.index') }}" class="cf-btn-ghost w-full">
                        <i class="fa-solid fa-scissors" aria-hidden="true"></i>
                        Toutes les commandes
                    </a>
                </div>
            </section>
        </aside>
    </div>
@endsection
