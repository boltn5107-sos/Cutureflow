@extends('layouts.admin')

@section('title', 'Paiements')
@section('header_title', 'Paiements d\'abonnement')
@section('header_subtitle', 'Preuves Wave à examiner et décisions')

@section('content')
    <x-page-header
        title="Paiements d'abonnement"
        subtitle="Examinez chaque preuve de paiement et appliquez votre décision."
    >
        <x-slot:actions>
            <a href="{{ route('admin.dashboard') }}" class="cf-btn-secondary">
                <i class="fa-solid fa-gauge" aria-hidden="true"></i>
                Tableau de bord
            </a>
            <a href="{{ route('admin.utilisateurs.index') }}" class="cf-btn-primary">
                <i class="fa-solid fa-users" aria-hidden="true"></i>
                Utilisateurs
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-5 flex flex-wrap items-center gap-2">
        <a
            href="{{ route('admin.paiements.index') }}"
            @class([
                'cf-badge px-3 py-1.5',
                'bg-brand-800 text-brand-50 dark:bg-brand-400 dark:text-brand-900' => empty($filters['statut']),
                'bg-white text-brand-700 ring-1 ring-brand-200 ring-inset hover:bg-brand-50 dark:bg-white/5 dark:text-brand-200 dark:ring-white/10 dark:hover:bg-white/10' => ! empty($filters['statut']),
            ])
        >
            Tous
        </a>

        @foreach ($statusOptions as $value => $label)
            <a
                href="{{ route('admin.paiements.index', ['statut' => $value]) }}"
                @class([
                    'cf-badge px-3 py-1.5',
                    'bg-brand-800 text-brand-50 dark:bg-brand-400 dark:text-brand-900' => ($filters['statut'] ?? null) === $value,
                    'bg-white text-brand-700 ring-1 ring-brand-200 ring-inset hover:bg-brand-50 dark:bg-white/5 dark:text-brand-200 dark:ring-white/10 dark:hover:bg-white/10' => ($filters['statut'] ?? null) !== $value,
                ])
            >
                {{ $label }}
                <span class="ml-1 opacity-70">{{ $counts[$value] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.paiements.index') }}" class="cf-card mb-6 flex flex-wrap items-end gap-3 p-4">
        @if ($filters['statut'] ?? null)
            <input type="hidden" name="statut" value="{{ $filters['statut'] }}">
        @endif

        <div class="min-w-56 flex-1">
            <x-form.text
                name="q"
                label="Rechercher"
                :value="$filters['q'] ?? null"
                placeholder="Nom, atelier, numéro Wave, référence…"
            />
        </div>

        <button type="submit" class="cf-btn-secondary">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            Rechercher
        </button>

        @if ($filters['q'] ?? null)
            <a href="{{ route('admin.paiements.index', array_filter(['statut' => $filters['statut'] ?? null])) }}" class="cf-btn-ghost">
                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                Réinitialiser
            </a>
        @endif
    </form>

    @if ($subscriptions->isEmpty())
        <div class="cf-card">
            <x-empty-state
                icon="fa-solid fa-receipt"
                title="Aucun paiement trouvé"
                message="Aucune demande ne correspond à votre recherche."
            />
        </div>
    @else
        <section class="cf-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="cf-table">
                    <thead>
                        <tr>
                            <th>Atelier</th>
                            <th>Montant</th>
                            <th>Payé le</th>
                            <th>Wave utilisé</th>
                            <th>Référence</th>
                            <th>Statut</th>
                            <th>Validé par</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subscriptions as $subscription)
                            <tr>
                                <td data-label="Atelier">
                                    <p class="font-semibold">{{ $subscription->user?->atelier?->nom ?? $subscription->user?->name ?? '—' }}</p>
                                    <p class="text-xs text-brand-500">{{ $subscription->user?->email }}</p>
                                </td>
                                <td data-label="Montant" class="font-semibold tabular-nums">
                                    {{ number_format((float) $subscription->montant, 0, ',', ' ') }}
                                    <span class="text-xs font-normal text-brand-500">FCFA</span>
                                </td>
                                <td data-label="Payé le" class="tabular-nums">{{ $subscription->date_paiement->format('d/m/Y') }}</td>
                                <td data-label="Wave utilisé" class="tabular-nums">{{ $subscription->wave_number_used ?: '—' }}</td>
                                <td data-label="Référence" class="text-xs text-brand-500">{{ $subscription->wave_reference ?: '—' }}</td>
                                <td data-label="Statut"><x-status-badge :status="$subscription->statut" /></td>
                                <td data-label="Validé par" class="text-xs text-brand-500">
                                    {{ $subscription->reviewer?->name ?? '—' }}
                                    @if ($subscription->reviewed_at)
                                        <span class="block tabular-nums">{{ $subscription->reviewed_at->format('d/m/Y') }}</span>
                                    @endif
                                </td>
                                <td data-compact class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if ($subscription->hasProof())
                                            <a
                                                href="{{ route('admin.paiements.proof', $subscription) }}"
                                                target="_blank"
                                                rel="noopener"
                                                class="cf-btn-ghost cf-btn-sm"
                                                aria-label="Ouvrir la preuve de paiement"
                                            >
                                                <i class="fa-solid fa-file-image text-[0.65rem]" aria-hidden="true"></i>
                                                Preuve
                                            </a>
                                        @endif

                                        <a href="{{ route('admin.paiements.show', $subscription) }}" class="cf-btn-primary cf-btn-sm">
                                            Vérifier
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <x-pagination :paginator="$subscriptions" label="paiements" class="mt-6" />
    @endif
@endsection
