@extends('layouts.admin')

@section('title', 'Utilisateurs')
@section('header_title', 'Ateliers et utilisateurs')
@section('header_subtitle', 'Comptes enregistrés sur la plateforme')

@section('content')
    <x-page-header
        title="Ateliers et utilisateurs"
        subtitle="Consultez le statut de chaque compte et ajustez-le si nécessaire."
    >
        <x-slot:actions>
            <a href="{{ route('admin.dashboard') }}" class="cf-btn-secondary">
                <i class="fa-solid fa-gauge" aria-hidden="true"></i>
                Tableau de bord
            </a>
            <a href="{{ route('admin.paiements.index') }}" class="cf-btn-primary">
                <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                Paiements
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-5 flex flex-wrap items-center gap-2">
        <a
            href="{{ route('admin.utilisateurs.index') }}"
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
                href="{{ route('admin.utilisateurs.index', ['statut' => $value]) }}"
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

    <form method="GET" action="{{ route('admin.utilisateurs.index') }}" class="cf-card mb-6 flex flex-wrap items-end gap-3 p-4">
        @if ($filters['statut'] ?? null)
            <input type="hidden" name="statut" value="{{ $filters['statut'] }}">
        @endif

        <div class="min-w-56 flex-1">
            <x-form.text
                name="q"
                label="Rechercher"
                :value="$filters['q'] ?? null"
                placeholder="Nom, atelier, email, téléphone…"
            />
        </div>

        <button type="submit" class="cf-btn-secondary">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            Rechercher
        </button>

        @if ($filters['q'] ?? null)
            <a href="{{ route('admin.utilisateurs.index', array_filter(['statut' => $filters['statut'] ?? null])) }}" class="cf-btn-ghost">
                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                Réinitialiser
            </a>
        @endif
    </form>

    @if ($users->isEmpty())
        <div class="cf-card">
            <x-empty-state
                icon="fa-solid fa-users"
                title="Aucun atelier trouvé"
                message="Aucun compte ne correspond à votre recherche."
            />
        </div>
    @else
        <section class="cf-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="cf-table">
                    <thead>
                        <tr>
                            <th>Atelier</th>
                            <th>Responsable</th>
                            <th>Contact</th>
                            <th>Statut</th>
                            <th>Dernier paiement</th>
                            <th>Inscription</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>
                                    <p class="font-semibold">{{ $user->atelier?->nom ?? '—' }}</p>
                                    <p class="text-xs text-brand-500">{{ $user->atelier?->ville ?? 'Ville non renseignée' }}</p>
                                </td>
                                <td>
                                    <p class="font-medium">{{ $user->name }}</p>
                                    <p class="text-xs text-brand-500">{{ $user->email }}</p>
                                </td>
                                <td class="tabular-nums">
                                    <a href="tel:{{ $user->phone }}" class="cf-link">{{ $user->phone }}</a>
                                </td>
                                <td><x-status-badge :status="$user->status" /></td>
                                <td>
                                    @if ($user->latestSubscription)
                                        <p class="tabular-nums">{{ $user->latestSubscription->date_paiement->format('d/m/Y') }}</p>
                                        <x-status-badge :status="$user->latestSubscription->statut" />
                                    @else
                                        <span class="text-xs text-brand-400">Aucun</span>
                                    @endif
                                </td>
                                <td class="text-xs whitespace-nowrap text-brand-500 tabular-nums">
                                    {{ $user->created_at->format('d/m/Y') }}
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.utilisateurs.show', $user) }}" class="cf-link text-xs font-semibold">
                                        Voir
                                        <i class="fa-solid fa-arrow-right ml-1 text-[0.6rem]" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <x-pagination :paginator="$users" label="comptes" class="mt-6" />
    @endif
@endsection
