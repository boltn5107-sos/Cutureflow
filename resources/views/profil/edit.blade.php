@extends('layouts.app')

@section('title', 'Mon profil')
@section('header_title', 'Mon profil')
@section('header_subtitle', 'Vos informations et votre atelier')

@section('content')
    <x-page-header
        title="Mon profil"
        subtitle="Mettez à jour vos informations personnelles et celles de votre atelier."
    >
        <x-slot:actions>
            <a href="{{ route('abonnement.index') }}" class="cf-btn-secondary">
                <i class="fa-solid fa-credit-card" aria-hidden="true"></i>
                Mon abonnement
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Mes informations</h2>
                </div>

                <form method="POST" action="{{ route('profil.update') }}" class="p-5">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-form.text
                                name="name"
                                label="Nom du responsable"
                                :value="$user->name"
                                autocomplete="name"
                                required
                                autofocus
                            />
                        </div>

                        <x-form.text
                            name="email"
                            label="Adresse email"
                            type="email"
                            :value="$user->email"
                            autocomplete="email"
                            required
                        />

                        <x-form.text
                            name="phone"
                            label="Téléphone"
                            type="tel"
                            :value="$user->phone"
                            autocomplete="tel"
                            required
                        />
                    </div>

                    <h3 class="mt-6 font-serif text-base font-semibold">Mon atelier</h3>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-form.text
                                name="atelier_nom"
                                label="Nom de l'atelier"
                                :value="$atelier?->nom"
                                required
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <x-form.text
                                name="adresse"
                                label="Adresse"
                                :value="$atelier?->adresse"
                            />
                        </div>

                        <x-form.text
                            name="ville"
                            label="Ville"
                            :value="$atelier?->ville"
                        />

                        <x-form.text
                            name="ninea"
                            label="NINEA"
                            :value="$atelier?->ninea"
                            hint="Numéro d'identification fiscale de l'atelier."
                        />
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="cf-btn-primary">
                            <i class="fa-solid fa-check" aria-hidden="true"></i>
                            Enregistrer
                        </button>
                    </div>
                </form>
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Mot de passe</h2>
                </div>

                <form method="POST" action="{{ route('profil.password') }}" class="p-5">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-form.text
                            name="password_actuel"
                            label="Mot de passe actuel"
                            type="password"
                            autocomplete="current-password"
                            required
                        />

                        <x-form.text
                            name="password"
                            label="Nouveau mot de passe"
                            type="password"
                            autocomplete="new-password"
                            hint="8 caractères minimum."
                            required
                        />

                        <x-form.text
                            name="password_confirmation"
                            label="Confirmer"
                            type="password"
                            autocomplete="new-password"
                            required
                        />
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="cf-btn-secondary">
                            <i class="fa-solid fa-key" aria-hidden="true"></i>
                            Modifier le mot de passe
                        </button>
                    </div>
                </form>
            </section>
        </div>

        <div class="space-y-6">
            <section class="cf-card p-5 text-center">
                <span class="mx-auto flex size-20 items-center justify-center rounded-full bg-brand-800 text-2xl font-semibold text-brand-50 dark:bg-brand-400 dark:text-brand-900">
                    {{ mb_strtoupper(mb_substr($user->name, 0, 2)) }}
                </span>

                <p class="mt-4 font-serif text-lg font-semibold">{{ $user->name }}</p>
                <p class="text-sm text-brand-500">{{ $atelier?->nom ?? 'Sans atelier' }}</p>

                <div class="mt-4 flex justify-center">
                    <x-status-badge :status="$user->status" />
                </div>
            </section>

            @if ($atelier)
                <section class="cf-card p-5">
                    <h2 class="font-serif text-lg font-semibold">Fiche atelier</h2>

                    <dl class="mt-4 space-y-3.5 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-brand-500">Téléphone</dt>
                            <dd class="text-right font-medium">
                                <a href="tel:{{ $atelier->telephone }}" class="cf-link">{{ $atelier->telephone }}</a>
                            </dd>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-brand-500">Adresse</dt>
                            <dd class="text-right font-medium">{{ $atelier->adresse ?: '—' }}</dd>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-brand-500">Ville</dt>
                            <dd class="text-right font-medium">{{ $atelier->ville ?: '—' }}</dd>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-brand-500">NINEA</dt>
                            <dd class="text-right font-medium">{{ $atelier->ninea ?: '—' }}</dd>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-brand-500">Valide depuis</dt>
                            <dd class="text-right font-medium tabular-nums">
                                {{ $atelier->valid_from?->format('d/m/Y') ?? '—' }}
                            </dd>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-brand-500">Valide jusqu'au</dt>
                            <dd class="text-right font-medium tabular-nums">
                                {{ $atelier->valid_until?->format('d/m/Y') ?? '—' }}
                            </dd>
                        </div>
                    </dl>

                    <a href="{{ route('abonnement.index') }}" class="cf-btn-secondary mt-5 w-full">
                        <i class="fa-solid fa-credit-card" aria-hidden="true"></i>
                        Gérer mon abonnement
                    </a>
                </section>
            @endif

            <section class="cf-card p-5">
                <h2 class="font-serif text-base font-semibold">Raccourcis</h2>
                <div class="mt-3 space-y-1.5">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition hover:bg-brand-50 dark:hover:bg-white/5">
                        <i class="fa-solid fa-gauge w-4 text-brand-400" aria-hidden="true"></i>
                        Tableau de bord
                    </a>
                    <a href="{{ route('clients.index') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition hover:bg-brand-50 dark:hover:bg-white/5">
                        <i class="fa-solid fa-users w-4 text-brand-400" aria-hidden="true"></i>
                        Mes clients
                    </a>
                    <a href="{{ route('commandes.index') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition hover:bg-brand-50 dark:hover:bg-white/5">
                        <i class="fa-solid fa-scissors w-4 text-brand-400" aria-hidden="true"></i>
                        Mes commandes
                    </a>
                    <a href="{{ route('caisse.index') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition hover:bg-brand-50 dark:hover:bg-white/5">
                        <i class="fa-solid fa-sack-dollar w-4 text-brand-400" aria-hidden="true"></i>
                        Ma caisse
                    </a>
                </div>
            </section>
        </div>
    </div>
@endsection
