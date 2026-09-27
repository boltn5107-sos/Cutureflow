@extends('layouts.admin')

@section('title', 'Administration')
@section('header_title', 'Pilotage de la plateforme')
@section('header_subtitle', 'Inscriptions, abonnements et revenus')

@section('content')
    <x-page-header
        title="Pilotage de la plateforme"
        subtitle="Validez les inscriptions, examinez les paiements Wave et suivez les revenus d'abonnement."
    >
        <x-slot:actions>
            <a href="{{ route('admin.utilisateurs.index') }}" class="cf-btn-secondary">
                <i class="fa-solid fa-users" aria-hidden="true"></i>
                Utilisateurs
            </a>
            <a href="{{ route('admin.paiements.index') }}" class="cf-btn-primary">
                <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                Paiements
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('admin.utilisateurs.index') }}" class="cf-card block p-5 transition hover:-translate-y-0.5 hover:shadow-elevated">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Ateliers</p>
                    <p class="mt-2 font-serif text-3xl font-semibold tabular-nums">{{ number_format($total, 0, ',', ' ') }}</p>
                    <p class="mt-1 text-xs text-brand-500">comptes enregistrés</p>
                </div>
                <span class="flex size-11 items-center justify-center rounded-xl bg-brand-100 text-brand-700 dark:bg-white/10 dark:text-brand-200">
                    <i class="fa-solid fa-store" aria-hidden="true"></i>
                </span>
            </div>
        </a>

        <a href="{{ route('admin.utilisateurs.index', ['statut' => 'en_attente']) }}" class="cf-card block p-5 transition hover:-translate-y-0.5 hover:shadow-elevated">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-medium tracking-wide text-amber-700 uppercase dark:text-amber-300">En Attente</p>
                    <p class="mt-2 font-serif text-3xl font-semibold tabular-nums">{{ number_format($counts['en_attente'], 0, ',', ' ') }}</p>
                    <p class="mt-1 text-xs text-brand-500">à vérifier</p>
                </div>
                <span class="flex size-11 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                    <i class="fa-regular fa-clock" aria-hidden="true"></i>
                </span>
            </div>
        </a>

        <a href="{{ route('admin.utilisateurs.index', ['statut' => 'valide']) }}" class="cf-card block p-5 transition hover:-translate-y-0.5 hover:shadow-elevated">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-medium tracking-wide text-emerald-700 uppercase dark:text-emerald-300">Validés</p>
                    <p class="mt-2 font-serif text-3xl font-semibold tabular-nums">{{ number_format($counts['valide'], 0, ',', ' ') }}</p>
                    <p class="mt-1 text-xs text-brand-500">abonnements actifs</p>
                </div>
                <span class="flex size-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                </span>
            </div>
        </a>

        <a href="{{ route('admin.paiements.index', ['statut' => 'en_attente']) }}" class="cf-card block p-5 transition hover:-translate-y-0.5 hover:shadow-elevated">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">À contrôler</p>
                    <p class="mt-2 font-serif text-3xl font-semibold tabular-nums">{{ number_format($totalPaiementsEnAttente, 0, ',', ' ') }}</p>
                    <p class="mt-1 text-xs text-brand-500">preuve(s) Wave à examiner</p>
                </div>
                <span class="flex size-11 items-center justify-center rounded-xl bg-brand-100 text-brand-700 dark:bg-white/10 dark:text-brand-200">
                    <i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i>
                </span>
            </div>
        </a>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('admin.utilisateurs.index', ['statut' => 'rejete']) }}" class="cf-card block p-4 transition hover:bg-brand-50/60 dark:hover:bg-white/[0.03]">
            <p class="text-xs font-medium tracking-wide text-red-700 uppercase dark:text-red-300">Rejetés</p>
            <p class="mt-1.5 font-serif text-2xl font-semibold tabular-nums">{{ number_format($counts['rejete'], 0, ',', ' ') }}</p>
        </a>

        <a href="{{ route('admin.utilisateurs.index', ['statut' => 'bloque']) }}" class="cf-card block p-4 transition hover:bg-brand-50/60 dark:hover:bg-white/[0.03]">
            <p class="text-xs font-medium tracking-wide text-brand-800 uppercase dark:text-brand-200">Bloqués</p>
            <p class="mt-1.5 font-serif text-2xl font-semibold tabular-nums">{{ number_format($counts['bloque'], 0, ',', ' ') }}</p>
        </a>

        <div class="cf-card p-4">
            <p class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Revenus du mois</p>
            <p class="mt-1.5 font-serif text-2xl font-semibold tabular-nums">{{ number_format($revenusMois, 0, ',', ' ') }}</p>
        </div>

        <div class="cf-card p-4">
            <p class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Expirent sous 7 j</p>
            <p class="mt-1.5 font-serif text-2xl font-semibold tabular-nums">{{ number_format($abonnementsExpires->count(), 0, ',', ' ') }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="cf-card overflow-hidden">
            <div class="cf-card-header">
                <h2 class="font-serif text-lg font-semibold">Paiements à vérifier</h2>
                <a href="{{ route('admin.paiements.index', ['statut' => 'en_attente']) }}" class="cf-link text-xs">Tout voir</a>
            </div>

            @if ($paiementsEnAttente->isEmpty())
                <x-empty-state
                    icon="fa-solid fa-circle-check"
                    title="Rien à vérifier"
                    message="Toutes les demandes de paiement ont été examinées."
                />
            @else
                <ul class="divide-y divide-brand-100 dark:divide-white/5">
                    @foreach ($paiementsEnAttente as $subscription)
                        <li class="flex flex-wrap items-center gap-3 px-5 py-4">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                                <i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i>
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold">
                                    {{ $subscription->user?->atelier?->nom ?? $subscription->user?->name ?? 'Atelier inconnu' }}
                                </p>
                                <p class="mt-0.5 truncate text-xs text-brand-500">
                                    {{ number_format((float) $subscription->montant, 0, ',', ' ') }} FCFA
                                    — {{ $subscription->wave_number_used ?: 'numéro non renseigné' }}
                                    — payé le {{ $subscription->date_paiement->format('d/m/Y') }}
                                </p>
                            </div>

                            <a href="{{ route('admin.paiements.show', $subscription) }}" class="cf-btn-primary cf-btn-sm">
                                Vérifier
                                <i class="fa-solid fa-arrow-right text-[0.6rem]" aria-hidden="true"></i>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="cf-card overflow-hidden">
            <div class="cf-card-header">
                <h2 class="font-serif text-lg font-semibold">Dernières inscriptions</h2>
                <a href="{{ route('admin.utilisateurs.index') }}" class="cf-link text-xs">Tous les ateliers</a>
            </div>

            @if ($dernieresInscriptions->isEmpty())
                <x-empty-state
                    icon="fa-solid fa-store"
                    title="Aucune inscription"
                    message="Les nouveaux ateliers apparaîtront ici."
                />
            @else
                <ul class="divide-y divide-brand-100 dark:divide-white/5">
                    @foreach ($dernieresInscriptions as $inscription)
                        <li class="flex flex-wrap items-center gap-3 px-5 py-4">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-xs font-semibold text-brand-700 dark:bg-white/10 dark:text-brand-200">
                                {{ mb_strtoupper(mb_substr($inscription->name, 0, 2)) }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold">{{ $inscription->atelier?->nom ?? $inscription->name }}</p>
                                <p class="mt-0.5 truncate text-xs text-brand-500">
                                    {{ $inscription->email }} — inscrit {{ $inscription->created_at->diffForHumans() }}
                                </p>
                            </div>

                            <x-status-badge :status="$inscription->status" />

                            <a href="{{ route('admin.utilisateurs.show', $inscription) }}" class="cf-btn-ghost cf-btn-sm" aria-label="Voir la fiche">
                                <i class="fa-solid fa-arrow-right text-[0.6rem]" aria-hidden="true"></i>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <section class="cf-card mt-6 overflow-hidden">
        <div class="cf-card-header">
            <h2 class="font-serif text-lg font-semibold">Abonnements qui expirent sous 7 jours</h2>
        </div>

        @if ($abonnementsExpires->isEmpty())
            <x-empty-state
                icon="fa-solid fa-calendar-check"
                title="Aucun expiration proche"
                message="Aucun abonnement n'arrive à échéance dans les 7 prochains jours."
            />
        @else
            <div class="overflow-x-auto">
                <table class="cf-table">
                    <thead>
                        <tr>
                            <th>Atelier</th>
                            <th>Responsable</th>
                            <th>Échéance</th>
                            <th>Jours restants</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($abonnementsExpires as $expire)
                            @php $jours = (int) now()->startOfDay()->diffInDays($expire->atelier->valid_until->startOfDay(), false); @endphp
                            <tr>
                                <td class="font-medium">{{ $expire->atelier?->nom ?? '—' }}</td>
                                <td>{{ $expire->name }}</td>
                                <td class="tabular-nums">{{ $expire->atelier?->valid_until?->format('d/m/Y') ?? '—' }}</td>
                                <td>
                                    @if ($jours < 0)
                                        <span class="cf-badge bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300">
                                            Expiré depuis {{ abs($jours) }} j
                                        </span>
                                    @elseif ($jours === 0)
                                        <span class="cf-badge bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300">
                                            Expire aujourd'hui
                                        </span>
                                    @else
                                        <span class="cf-badge bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300">
                                            {{ $jours }} j
                                        </span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.utilisateurs.show', $expire) }}" class="cf-link text-xs font-semibold">
                                        Voir la fiche
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
