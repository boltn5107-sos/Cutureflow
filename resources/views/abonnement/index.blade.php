@extends('layouts.app')

@section('title', 'Mon abonnement')
@section('header_title', 'Mon abonnement')
@section('header_subtitle', 'Paiement Wave manuel et historique')

@section('content')
    @php
        $statut = $user->status instanceof \BackedEnum ? $user->status : \App\Enums\UserStatus::from($user->status);
        $joursRestants = $atelier?->valid_until ? (int) now()->startOfDay()->diffInDays($atelier->valid_until->startOfDay(), false) : null;
        $maxKb = (int) config('coutureflow.proof.max_kb');
    @endphp

    <x-page-header
        title="Mon abonnement"
        :subtitle="$config['label'].' — '.number_format((float) $config['amount'], 0, ',', ' ').' FCFA.'"
        :back="route('dashboard')"
    />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="cf-card p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">
                            Statut de mon compte
                        </p>
                        <p class="mt-2 font-serif text-2xl font-semibold">{{ $statut->label() }}</p>
                        <p class="mt-1.5 text-sm text-brand-600 dark:text-brand-300">
                            {{ $statut->description() }}
                        </p>
                    </div>

                    <x-status-badge :status="$statut" />
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-lg bg-brand-50 p-4 dark:bg-white/5">
                        <p class="text-xs text-brand-500">Montant</p>
                        <p class="mt-1 font-serif text-lg font-semibold tabular-nums">
                            {{ number_format((float) $config['amount'], 0, ',', ' ') }}
                            <span class="text-xs font-normal">FCFA</span>
                        </p>
                    </div>

                    <div class="rounded-lg bg-brand-50 p-4 dark:bg-white/5">
                        <p class="text-xs text-brand-500">Durée</p>
                        <p class="mt-1 font-serif text-lg font-semibold">
                            {{ (int) $config['months'] }} mois
                        </p>
                    </div>

                    <div class="rounded-lg bg-brand-50 p-4 dark:bg-white/5">
                        <p class="text-xs text-brand-500">Échéance</p>
                        <p class="mt-1 font-serif text-lg font-semibold tabular-nums">
                            {{ $atelier?->valid_until?->format('d/m/Y') ?? '—' }}
                        </p>
                    </div>
                </div>

                @if ($joursRestants !== null)
                    <p
                        @class([
                            'mt-4 flex items-center gap-2 rounded-lg px-3.5 py-2.5 text-sm font-medium',
                            'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300' => $joursRestants < 0,
                            'bg-amber-50 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300' => $joursRestants >= 0 && $joursRestants <= 7,
                            'bg-emerald-50 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300' => $joursRestants > 7,
                        ])
                    >
                        <i
                            @class([
                                'fa-solid fa-triangle-exclamation',
                                'fa-solid fa-clock',
                                'fa-solid fa-circle-check',
                            ])
                            aria-hidden="true"
                        ></i>

                        @if ($joursRestants < 0)
                            Votre abonnement est expiré depuis {{ abs($joursRestants) }} jour(s). Régularisez pour retrouver votre accès.
                        @elseif ($joursRestants === 0)
                            Votre abonnement expire aujourd'hui. Pensez à renouveler.
                        @else
                            Il vous reste {{ $joursRestants }} jour(s) avant l'échéance.
                        @endif
                    </p>
                @endif

                @if ($statut->value === 'bloque' && $user->status_reason)
                    <p class="mt-4 flex items-start gap-2 rounded-lg bg-red-50 px-3.5 py-2.5 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-300">
                        <i class="fa-solid fa-circle-info mt-0.5 text-[0.65rem]" aria-hidden="true"></i>
                        <span><span class="font-semibold">Motif du blocage :</span> {{ $user->status_reason }}</span>
                    </p>
                @endif
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Comment payer</h2>
                    <span class="cf-badge bg-brand-100 text-brand-800 dark:bg-brand-500/20 dark:text-brand-200">
                        Paiement Wave manuel
                    </span>
                </div>

                <div class="p-5">
                    <div class="rounded-xl border border-brand-200 bg-brand-50/60 p-5 dark:border-white/10 dark:bg-white/[0.02]">
                        <div class="flex flex-wrap items-end justify-between gap-4">
                            <div>
                                <p class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">
                                    Numéro à payer
                                </p>
                                <p class="mt-1.5 font-serif text-3xl font-semibold tracking-tight tabular-nums">
                                    {{ $wave['number'] }}
                                </p>
                                <p class="mt-1 text-sm text-brand-600 dark:text-brand-300">
                                    {{ $wave['name'] }} — {{ $wave['country'] }}
                                </p>
                            </div>

                            <div class="text-right">
                                <p class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">
                                    Montant exact
                                </p>
                                <p class="mt-1.5 font-serif text-3xl font-semibold tabular-nums">
                                    {{ number_format((float) $config['amount'], 0, ',', ' ') }}
                                    <span class="text-base font-normal">FCFA</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <ol class="mt-5 space-y-3 text-sm text-brand-700 dark:text-brand-200">
                        <li class="flex gap-3">
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-800 text-xs font-semibold text-brand-50 dark:bg-brand-400 dark:text-brand-900">1</span>
                            Ouvrez l'application Wave sur votre téléphone.
                        </li>
                        <li class="flex gap-3">
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-800 text-xs font-semibold text-brand-50 dark:bg-brand-400 dark:text-brand-900">2</span>
                            Envoyez <span class="font-semibold">{{ number_format((float) $config['amount'], 0, ',', ' ') }} FCFA</span> au numéro <span class="font-semibold">{{ $wave['number'] }}</span>.
                        </li>
                        <li class="flex gap-3">
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-800 text-xs font-semibold text-brand-50 dark:bg-brand-400 dark:text-brand-900">3</span>
                            Faites une capture d'écran du reçu de paiement.
                        </li>
                        <li class="flex gap-3">
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-800 text-xs font-semibold text-brand-50 dark:bg-brand-400 dark:text-brand-900">4</span>
                            Envoyez le formulaire ci-dessous avec la capture.
                        </li>
                    </ol>
                </div>
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Envoyer ma preuve de paiement</h2>
                </div>

                @unless ($user->hasValidatedAccess())
                    <div class="mx-5 mt-5 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3.5 dark:border-amber-500/30 dark:bg-amber-500/10">
                        <i class="fa-solid fa-circle-info mt-0.5 text-amber-600 dark:text-amber-400" aria-hidden="true"></i>
                        <p class="text-sm text-amber-800 dark:text-amber-200">
                            Votre compte n'est pas encore validé. Vous pouvez tout de même déposer une preuve :
                            elle sera examinée par l'administrateur comme les autres demandes.
                        </p>
                    </div>
                @endunless

                <form
                    method="POST"
                    action="{{ route('abonnement.store') }}"
                    enctype="multipart/form-data"
                    class="p-5"
                    x-data="{ envoyer: false }"
                    @submit="envoyer = true"
                >
                    @csrf

                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-form.text
                            name="date_paiement"
                            label="Date du paiement"
                            type="date"
                            :max="today()->toDateString()"
                            :value="old('date_paiement', today()->toDateString())"
                            required
                        />

                        <x-form.text
                            name="wave_number_used"
                            label="Numéro Wave utilisé"
                            type="tel"
                            :value="old('wave_number_used')"
                            placeholder="77 000 00 00"
                            required
                            hint="Le numéro depuis lequel vous avez envoyé l'argent."
                        />

                        <div class="sm:col-span-2">
                            <x-form.text
                                name="wave_reference"
                                label="Référence de la transaction"
                                :value="old('wave_reference')"
                                placeholder="Facultatif — référence affichée sur votre reçu Wave"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <x-form.file
                                name="preuve"
                                label="Preuve de paiement"
                                accept="image/jpeg,image/png,image/webp,application/pdf"
                                :max-kb="$maxKb"
                                :required="true"
                                placeholder="Cliquez pour joindre votre reçu"
                                hint="JPG, PNG, WEBP ou PDF — {{ round($maxKb / 1024) }} Mo maximum."
                            />
                        </div>
                    </div>

                    <div class="mt-5 flex items-center justify-between gap-3">
                        <p class="text-xs text-brand-500">
                            Vérification sous 24 à 48 heures ouvrées.
                        </p>

                        <button type="submit" class="cf-btn-primary" x-bind:disabled="envoyer">
                            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                            <span x-text="envoyer ? 'Envoi…' : 'Envoyer ma preuve'"></span>
                        </button>
                    </div>
                </form>
            </section>
        </div>

        <div class="space-y-6">
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Historique</h2>
                    <span class="text-xs text-brand-500">{{ $subscriptions->count() }} demande(s)</span>
                </div>

                @if ($subscriptions->isEmpty())
                    <x-empty-state
                        icon="fa-regular fa-receipt"
                        title="Aucun paiement enregistré"
                        message="Votre première preuve de paiement apparaîtra ici."
                    />
                @else
                    <ul class="divide-y divide-brand-100 dark:divide-white/5">
                        @foreach ($subscriptions->sortByDesc('created_at') as $subscription)
                            <li class="px-5 py-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold">{{ $subscription->libelle }}</p>
                                        <p class="mt-0.5 text-xs text-brand-500 tabular-nums">
                                            {{ number_format((float) $subscription->montant, 0, ',', ' ') }} FCFA
                                            — payé le {{ $subscription->date_paiement->format('d/m/Y') }}
                                        </p>
                                    </div>

                                    <x-status-badge :status="$subscription->statut" />
                                </div>

                                <dl class="mt-2.5 space-y-1 text-xs text-brand-600 dark:text-brand-300">
                                    @if ($subscription->wave_number_used)
                                        <div class="flex gap-1.5">
                                            <dt class="text-brand-400">Wave :</dt>
                                            <dd>{{ $subscription->wave_number_used }}</dd>
                                        </div>
                                    @endif

                                    @if ($subscription->wave_reference)
                                        <div class="flex gap-1.5">
                                            <dt class="text-brand-400">Référence :</dt>
                                            <dd>{{ $subscription->wave_reference }}</dd>
                                        </div>
                                    @endif

                                    @if ($subscription->reviewed_at)
                                        <div class="flex gap-1.5">
                                            <dt class="text-brand-400">Vérifié le :</dt>
                                            <dd>
                                                {{ $subscription->reviewed_at->format('d/m/Y') }}
                                                @if ($subscription->reviewer)
                                                    par {{ $subscription->reviewer->name }}
                                                @endif
                                            </dd>
                                        </div>
                                    @endif
                                </dl>

                                @if ($subscription->reason)
                                    <p class="mt-2.5 flex items-start gap-1.5 rounded-lg bg-red-50 px-2.5 py-2 text-xs text-red-700 dark:bg-red-500/10 dark:text-red-300">
                                        <i class="fa-solid fa-circle-exclamation mt-0.5 text-[0.6rem]" aria-hidden="true"></i>
                                        {{ $subscription->reason }}
                                    </p>
                                @endif

                                @if ($subscription->hasProof())
                                    <a
                                        href="{{ route('abonnement.preuve', $subscription) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="mt-2.5 inline-flex items-center gap-1.5 text-xs font-semibold text-brand-600 hover:underline dark:text-brand-300"
                                    >
                                        <i class="fa-solid fa-file-image text-[0.65rem]" aria-hidden="true"></i>
                                        Voir la preuve
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="cf-card p-5">
                <h2 class="font-serif text-base font-semibold">Une question ?</h2>
                <p class="mt-2 text-sm text-brand-600 dark:text-brand-300">
                    Votre administrateur examine chaque demande. Si votre paiement est refusé ou bloqué,
                    la raison exacte apparaît dans l'historique ci-dessus.
                </p>
            </section>
        </div>
    </div>
@endsection
