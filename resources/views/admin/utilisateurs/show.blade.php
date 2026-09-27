@extends('layouts.admin')

@section('title', 'Fiche atelier')
@section('header_title', 'Fiche atelier')
@section('header_subtitle', 'Compte, abonnement et historique')

@section('content')
    <x-page-header
        :title="$user->atelier?->nom ?? $user->name"
        :subtitle="$user->email"
        :back="route('admin.utilisateurs.index')"
    >
        <x-slot:actions>
            @if ($user->latestSubscription)
                <a
                    href="{{ route('admin.paiements.show', $user->latestSubscription) }}"
                    class="cf-btn-secondary"
                >
                    <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                    Voir le dernier paiement
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="cf-card p-5">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <span class="flex size-14 shrink-0 items-center justify-center rounded-xl bg-brand-800 text-lg font-semibold text-brand-50 dark:bg-brand-400 dark:text-brand-900">
                            {{ mb_strtoupper(mb_substr($user->name, 0, 2)) }}
                        </span>

                        <div class="min-w-0">
                            <p class="truncate font-serif text-lg font-semibold">{{ $user->name }}</p>
                            <p class="truncate text-sm text-brand-500">{{ $user->atelier?->nom ?? 'Sans atelier' }}</p>
                        </div>
                    </div>

                    <x-status-badge :status="$user->status" />
                </div>

                <p class="mt-4 text-sm text-brand-600 dark:text-brand-300">
                    {{ $user->status->description() }}
                </p>

                @if ($user->status_reason)
                    <p class="mt-3 flex items-start gap-2 rounded-lg bg-red-50 px-3.5 py-2.5 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-300">
                        <i class="fa-solid fa-circle-info mt-0.5 text-[0.65rem]" aria-hidden="true"></i>
                        <span><span class="font-semibold">Raison :</span> {{ $user->status_reason }}</span>
                    </p>
                @endif

                <dl class="mt-5 grid gap-4 border-t border-brand-100 pt-5 sm:grid-cols-2 dark:border-white/5">
                    <div>
                        <dt class="text-xs text-brand-500">Email</dt>
                        <dd class="mt-0.5 font-medium">
                            <a href="mailto:{{ $user->email }}" class="cf-link">{{ $user->email }}</a>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-brand-500">Téléphone</dt>
                        <dd class="mt-0.5 font-medium">
                            <a href="tel:{{ $user->phone }}" class="cf-link">{{ $user->phone }}</a>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-brand-500">Inscrit le</dt>
                        <dd class="mt-0.5 font-medium tabular-nums">{{ $user->created_at->format('d/m/Y à H:i') }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs text-brand-500">Statut modifié le</dt>
                        <dd class="mt-0.5 font-medium tabular-nums">
                            {{ $user->status_updated_at?->format('d/m/Y à H:i') ?? '—' }}
                        </dd>
                    </div>
                </dl>
            </section>

            @if ($user->atelier)
                <section class="cf-card overflow-hidden">
                    <div class="cf-card-header">
                        <h2 class="font-serif text-lg font-semibold">Informations atelier</h2>
                    </div>

                    <dl class="grid gap-4 p-5 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs text-brand-500">Nom de l'atelier</dt>
                            <dd class="mt-0.5 font-medium">{{ $user->atelier->nom }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs text-brand-500">Téléphone</dt>
                            <dd class="mt-0.5 font-medium tabular-nums">{{ $user->atelier->telephone ?: '—' }}</dd>
                        </div>

                        <div class="sm:col-span-2">
                            <dt class="text-xs text-brand-500">Adresse</dt>
                            <dd class="mt-0.5 font-medium">{{ $user->atelier->adresse ?: '—' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs text-brand-500">Ville</dt>
                            <dd class="mt-0.5 font-medium">{{ $user->atelier->ville ?: '—' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs text-brand-500">NINEA</dt>
                            <dd class="mt-0.5 font-medium tabular-nums">{{ $user->atelier->ninea ?: '—' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs text-brand-500">Valide du</dt>
                            <dd class="mt-0.5 font-medium tabular-nums">
                                {{ $user->atelier->valid_from?->format('d/m/Y') ?? '—' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs text-brand-500">Valide jusqu'au</dt>
                            <dd class="mt-0.5 font-medium tabular-nums">
                                {{ $user->atelier->valid_until?->format('d/m/Y') ?? '—' }}
                            </dd>
                        </div>
                    </dl>
                </section>
            @endif

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Historique des paiements</h2>
                    <span class="text-xs text-brand-500">{{ $user->subscriptions->count() }}</span>
                </div>

                @if ($user->subscriptions->isEmpty())
                    <x-empty-state
                        icon="fa-regular fa-receipt"
                        title="Aucun paiement"
                        message="Ce compte n'a encore déposé aucune preuve."
                    />
                @else
                    <div class="overflow-x-auto">
                        <table class="cf-table">
                            <thead>
                                <tr>
                                    <th>Libellé</th>
                                    <th>Montant</th>
                                    <th>Payé le</th>
                                    <th>Statut</th>
                                    <th>Validé par</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($user->subscriptions->sortByDesc('date_paiement') as $subscription)
                                    <tr>
                                        <td>
                                            <p class="font-medium">{{ $subscription->libelle }}</p>
                                            @if ($subscription->reason)
                                                <p class="mt-0.5 text-xs text-red-600 dark:text-red-400">
                                                    {{ \Illuminate\Support\Str::limit($subscription->reason, 60) }}
                                                </p>
                                            @endif
                                        </td>
                                        <td class="tabular-nums">
                                            {{ number_format((float) $subscription->montant, 0, ',', ' ') }}
                                        </td>
                                        <td class="tabular-nums">{{ $subscription->date_paiement->format('d/m/Y') }}</td>
                                        <td><x-status-badge :status="$subscription->statut" /></td>
                                        <td class="text-xs text-brand-500">{{ $subscription->reviewer?->name ?? '—' }}</td>
                                        <td class="text-right">
                                            <a href="{{ route('admin.paiements.show', $subscription) }}" class="cf-link text-xs font-semibold">
                                                Vérifier
                                            </a>
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
            <section class="cf-card p-5" x-data="{ statut: '{{ $user->status->value }}', raison: '' }">
                <h2 class="font-serif text-lg font-semibold">Changer le statut</h2>
                <p class="mt-1 text-sm text-brand-600 dark:text-brand-300">
                    Le compte est prévenu automatiquement du changement de statut.
                </p>

                <form method="POST" action="{{ route('admin.utilisateurs.statut', $user) }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PATCH')

                    <x-form.select
                        name="status"
                        label="Nouveau statut"
                        :options="$statusOptions"
                        x-model="statut"
                        :value="$user->status->value"
                        :empty="false"
                        required
                    />

                    <x-form.textarea
                        name="status_reason"
                        label="Raison communiquée à l'atelier"
                        :rows="4"
                        x-model="raison"
                        :value="$user->status_reason"
                        hint="Obligatoire pour rejeter ou bloquer un compte. Elle apparaît pour l'utilisateur."
                    />

                    <p
                        x-cloak
                        x-show="statut === 'rejete' || statut === 'bloque'"
                        x-transition
                        class="flex items-start gap-2 rounded-lg bg-amber-50 px-3 py-2.5 text-xs text-amber-800 dark:bg-amber-500/10 dark:text-amber-300"
                    >
                        <i class="fa-solid fa-triangle-exclamation mt-0.5 text-[0.6rem]" aria-hidden="true"></i>
                        Une raison est attendue pour ce statut, sinon l'atelier ne saura pas quoi corriger.
                    </p>

                    <button type="submit" class="cf-btn-primary w-full">
                        <i class="fa-solid fa-check" aria-hidden="true"></i>
                        Mettre à jour le statut
                    </button>
                </form>
            </section>

            <section class="cf-card p-5">
                <h2 class="font-serif text-base font-semibold">Raccourcis</h2>
                <div class="mt-3 space-y-1.5">
                    <a
                        href="{{ route('admin.utilisateurs.index', ['q' => $user->email]) }}"
                        class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition hover:bg-brand-50 dark:hover:bg-white/5"
                    >
                        <i class="fa-solid fa-magnifying-glass w-4 text-brand-400" aria-hidden="true"></i>
                        Rechercher par email
                    </a>
                    <a
                        href="{{ route('admin.paiements.index', ['q' => $user->atelier?->nom ?? $user->name]) }}"
                        class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition hover:bg-brand-50 dark:hover:bg-white/5"
                    >
                        <i class="fa-solid fa-receipt w-4 text-brand-400" aria-hidden="true"></i>
                        Paiements de cet atelier
                    </a>
                    <a
                        href="{{ route('admin.utilisateurs.index') }}"
                        class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition hover:bg-brand-50 dark:hover:bg-white/5"
                    >
                        <i class="fa-solid fa-arrow-left w-4 text-brand-400" aria-hidden="true"></i>
                        Retour à la liste
                    </a>
                </div>
            </section>
        </div>
    </div>
@endsection
