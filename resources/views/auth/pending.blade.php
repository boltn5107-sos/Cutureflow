@extends('layouts.auth')

@section('title', 'Votre compte est en attente')

@section('content')
    <div class="cf-card overflow-hidden">
        <div class="border-b border-brand-200/70 bg-amber-50 px-6 py-8 text-center dark:border-white/10 dark:bg-amber-500/10">
            <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-300">
                <i class="fa-regular fa-clock text-xl" aria-hidden="true"></i>
            </span>
            <h1 class="mt-4 font-serif text-2xl font-semibold tracking-tight">Inscription en attente</h1>
            <p class="mx-auto mt-2 max-w-sm text-sm text-brand-700 dark:text-brand-200">
                Votre demande a bien été reçue. Nous vérifions votre paiement avant d'activer votre accès.
            </p>
        </div>

        <div class="space-y-5 p-6">
            <dl class="space-y-3 rounded-lg border border-brand-200 bg-brand-50/60 p-4 text-sm dark:border-white/10 dark:bg-white/[0.03]">
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-brand-600 dark:text-brand-300">Atelier</dt>
                    <dd class="text-right font-semibold">{{ auth()->user()->atelier?->nom ?? '—' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-brand-600 dark:text-brand-300">Responsable</dt>
                    <dd class="text-right font-semibold">{{ auth()->user()->name }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-brand-600 dark:text-brand-300">Montant déclaré</dt>
                    <dd class="text-right font-semibold tabular-nums">
                        {{ $subscription ? number_format((float) $subscription->montant, 0, ',', ' ') : '—' }} {{ config('coutureflow.currency') }}
                    </dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-brand-600 dark:text-brand-300">Date du paiement</dt>
                    <dd class="text-right font-semibold">
                        {{ $subscription?->date_paiement?->format('d/m/Y') ?? '—' }}
                    </dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-brand-600 dark:text-brand-300">Preuve transmise</dt>
                    <dd class="text-right font-semibold">
                        @if ($subscription?->hasProof())
                            <a href="{{ route('abonnement.preuve', $subscription) }}" target="_blank" rel="noopener" class="cf-link">
                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                Consulter
                            </a>
                        @else
                            <span class="text-brand-400">Non transmise</span>
                        @endif
                    </dd>
                </div>
            </dl>

            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-500/30 dark:bg-blue-500/10">
                <p class="flex items-center gap-2 text-sm font-semibold text-blue-800 dark:text-blue-300">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    Prochaine étape
                </p>
                <p class="mt-1.5 text-sm text-blue-700 dark:text-blue-300/90">
                    Un administrateur vérifie votre reçu puis valide votre abonnement. Vous recevrez une
                    notification dès que votre accès sera ouvert. Vous pouvez vous déconnecter et revenir plus tard.
                </p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <a href="{{ route('abonnement.index') }}" class="cf-btn-secondary flex-1">
                    <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                    Suivre mon dossier
                </a>

                <form method="POST" action="{{ route('logout') }}" class="flex-1">
                    @csrf
                    <button type="submit" class="cf-btn-secondary w-full">
                        <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
                        Se déconnecter
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
