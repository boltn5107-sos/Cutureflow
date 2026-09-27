@extends('layouts.auth')

@section('title', $status === \App\Enums\UserStatus::Bloque ? 'Compte bloqué' : 'Inscription rejetée')

@section('content')
    <div class="cf-card overflow-hidden">
        <div class="border-b border-brand-200/70 bg-red-50 px-6 py-8 text-center dark:border-white/10 dark:bg-red-500/10">
            <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-300">
                <i class="{{ $status->icon() }} text-xl" aria-hidden="true"></i>
            </span>
            <h1 class="mt-4 font-serif text-2xl font-semibold tracking-tight">{{ $status->label() }}</h1>
            <p class="mx-auto mt-2 max-w-sm text-sm text-brand-700 dark:text-brand-200">
                {{ $status->description() }}
            </p>
        </div>

        <div class="space-y-5 p-6">
            @if (filled($user->status_reason))
                <div class="rounded-lg border border-brand-200 bg-brand-50/70 p-4 dark:border-white/10 dark:bg-white/[0.03]">
                    <p class="text-xs font-semibold tracking-wide text-brand-600 uppercase dark:text-brand-300">
                        {{ $status === \App\Enums\UserStatus::Bloque ? 'Motif du blocage' : 'Motif du rejet' }}
                    </p>
                    <p class="mt-2 text-sm leading-relaxed font-medium text-brand-800 dark:text-brand-100">
                        {{ $user->status_reason }}
                    </p>
                </div>
            @endif

            <dl class="space-y-3 rounded-lg border border-brand-200 bg-brand-50/60 p-4 text-sm dark:border-white/10 dark:bg-white/[0.03]">
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-brand-600 dark:text-brand-300">Atelier</dt>
                    <dd class="text-right font-semibold">{{ $user->atelier?->nom ?? '—' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-brand-600 dark:text-brand-300">Statut</dt>
                    <dd class="text-right">
                        <x-status-badge :status="$status" />
                    </dd>
                </div>
                @if ($user->status_updated_at)
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-brand-600 dark:text-brand-300">Dernière mise à jour</dt>
                        <dd class="text-right font-semibold">{{ $user->status_updated_at->format('d/m/Y à H:i') }}</dd>
                    </div>
                @endif
            </dl>

            <div class="rounded-lg border border-brand-200 p-4 dark:border-white/10">
                <p class="flex items-center gap-2 text-sm font-semibold text-brand-800 dark:text-brand-100">
                    <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
                    Comment réagir ?
                </p>
                @if ($status === \App\Enums\UserStatus::Bloque)
                    <p class="mt-1.5 text-sm text-brand-600 dark:text-brand-300">
                        Régularisez votre abonnement mensuel en effectuant le paiement Wave ci-dessous, puis
                        transmettez votre nouveau reçu pour demander le déblocage.
                    </p>
                        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                            <div class="flex items-center gap-2.5 rounded-lg bg-brand-100 px-3.5 py-2.5 dark:bg-white/10">
                                <i class="fa-solid fa-mobile-screen text-brand-600 dark:text-brand-200" aria-hidden="true"></i>
                                <span class="text-sm font-semibold tabular-nums">{{ $wave['number'] }}</span>
                            </div>
                            <a href="{{ route('abonnement.index') }}" class="cf-btn-accent flex-1">
                                <i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>
                                Régulariser
                            </a>
                        </div>
                @else
                    <p class="mt-1.5 text-sm text-brand-600 dark:text-brand-300">
                        Votre inscription n'a pas pu être acceptée. Vérifiez la preuve de paiement transmise et
                        contactez notre équipe si vous pensez qu'il s'agit d'une erreur.
                    </p>
                @endif
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <a href="mailto:support@coutureflow.app" class="cf-btn-secondary flex-1">
                    <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                    Contacter le support
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
