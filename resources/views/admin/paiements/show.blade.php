@extends('layouts.admin')

@section('title', 'Paiement — '.$subscription->user?->displayName())
@section('header_title', 'Vérification du paiement')
@section('header_subtitle', $subscription->user?->displayName())

@section('content')
    @php
        $devise = config('coutureflow.currency');
        $utilisateur = $subscription->user;
        $atelier = $utilisateur?->atelier;
        $preuveUrl = route('admin.paiements.proof', $subscription);
        $preuveTelechargement = $preuveUrl.'?telecharger=1';
        $aUnePreuve = $subscription->hasProof();
        $estImage = $aUnePreuve && $subscription->isImage();
        $estPdf = $aUnePreuve && str_starts_with((string) $subscription->proof_mime, 'application/pdf');
        $statutActuel = $subscription->statut?->value;
    @endphp

    <x-page-header
        :title="$utilisateur?->displayName() ?? 'Atelier inconnu'"
        :subtitle="$subscription->libelle ?: 'Abonnement'.' — '.number_format((float) $subscription->montant, 0, ',', ' ').' '.$devise"
        :back="route('admin.paiements.index')"
    >
        <x-slot:actions>
            <x-status-badge :status="$subscription->statut" />

            @if ($utilisateur)
                <a href="{{ route('admin.utilisateurs.show', $utilisateur) }}" class="cf-btn-secondary">
                    <i class="fa-solid fa-user" aria-hidden="true"></i>
                    Voir le dossier
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- (1) Preuve de paiement --}}
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Preuve de paiement</h2>
                    @if ($aUnePreuve)
                        <span class="text-xs text-brand-500">
                            {{ $subscription->proofSizeForHumans() }}
                            @if (filled($subscription->proof_original_name))
                                — {{ $subscription->proof_original_name }}
                            @endif
                        </span>
                    @endif
                </div>

                @if (! $aUnePreuve)
                    <x-empty-state
                        icon="fa-solid fa-file-circle-question"
                        title="Aucune preuve transmise"
                        message="L'atelier n'a pas déposé de reçu. Vérifiez le numéro Wave utilisé avant de valider l'abonnement."
                    />
                @elseif ($estImage)
                    <div class="p-5" x-data="{ apercuIndisponible: false }">
                        <img
                            src="{{ $preuveUrl }}"
                            alt="Preuve de paiement de {{ $utilisateur?->displayName() ?? 'l\'atelier' }}"
                            class="max-h-[32rem] w-full rounded-xl border border-brand-200 bg-brand-50 object-contain dark:border-white/10 dark:bg-white/[0.03]"
                            x-on:load="apercuIndisponible = false"
                            x-on:error="apercuIndisponible = true"
                        >

                        <div
                            x-cloak
                            x-show="apercuIndisponible"
                            class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300"
                            role="alert"
                        >
                            <p class="flex items-center gap-2 font-semibold">
                                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                                Aperçu en ligne indisponible
                            </p>
                            <p class="mt-1.5 leading-relaxed">
                                L'image n'a pas pu s'afficher dans cette page. Utilisez les boutons
                                ci-dessous pour l'ouvrir ou la télécharger, puis revenez appliquer
                                votre décision.
                            </p>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <a href="{{ $preuveUrl }}" target="_blank" rel="noopener" class="cf-btn-primary">
                                <i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i>
                                Ouvrir la preuve
                            </a>

                            <a href="{{ $preuveTelechargement }}" class="cf-btn-secondary">
                                <i class="fa-solid fa-download" aria-hidden="true"></i>
                                Télécharger
                            </a>
                        </div>
                    </div>
                @else
                    <div class="p-5">
                        <div class="flex items-start gap-3 rounded-xl border border-brand-200 bg-brand-50/60 p-4 dark:border-white/10 dark:bg-white/[0.03]">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700 dark:bg-white/10 dark:text-brand-200">
                                <i class="fa-solid fa-file-pdf text-lg" aria-hidden="true"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold">Document PDF</p>
                                <p class="mt-0.5 truncate text-xs text-brand-600 dark:text-brand-300">
                                    {{ $subscription->proof_original_name ?: 'preuve-paiement.pdf' }}
                                    @if ($subscription->proof_mime)
                                        — {{ $subscription->proof_mime }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        <a href="{{ $preuveUrl }}" target="_blank" rel="noopener" class="cf-btn-primary mt-4">
                            <i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i>
                            Ouvrir la preuve (PDF)
                        </a>
                    </div>
                @endif
            </section>

            {{-- (2) Informations --}}
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Informations du paiement</h2>
                </div>

                <dl class="grid gap-5 p-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Utilisateur</dt>
                        <dd class="mt-1">
                            @if ($utilisateur)
                                <a href="{{ route('admin.utilisateurs.show', $utilisateur) }}" class="cf-link font-semibold">
                                    {{ $utilisateur->name }}
                                </a>
                            @else
                                <span class="text-brand-400">Compte supprimé</span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Email</dt>
                        <dd class="mt-1 min-w-0">
                            @if ($utilisateur)
                                <a href="mailto:{{ $utilisateur->email }}" class="cf-link block truncate">{{ $utilisateur->email }}</a>
                            @else
                                <span class="text-brand-400">—</span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Téléphone</dt>
                        <dd class="mt-1">
                            @if ($utilisateur && filled($utilisateur->phone))
                                <a href="tel:{{ $utilisateur->phone }}" class="cf-link tabular-nums">{{ $utilisateur->phone }}</a>
                            @else
                                <span class="text-brand-400">Non renseigné</span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Atelier</dt>
                        <dd class="mt-1 font-medium">
                            {{ $atelier?->nom ?? 'Aucun atelier' }}
                            @if ($atelier?->ville)
                                <span class="block text-xs font-normal text-brand-500">{{ $atelier->ville }}</span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Montant</dt>
                        <dd class="mt-1 text-base font-semibold tabular-nums text-brand-accent">
                            {{ number_format((float) $subscription->montant, 0, ',', ' ') }} {{ $devise }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Durée</dt>
                        <dd class="mt-1 text-sm font-medium">
                            {{ $subscription->duree_mois }} mois
                            <span class="block text-xs font-normal text-brand-500">{{ $subscription->libelle ?: 'Abonnement' }}</span>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Date de paiement</dt>
                        <dd class="mt-1 text-sm font-medium tabular-nums">
                            {{ $subscription->date_paiement?->format('d/m/Y') ?? '—' }}
                            @if ($subscription->date_paiement)
                                <span class="block text-xs font-normal text-brand-500">{{ $subscription->date_paiement->translatedFormat('D j M Y') }}</span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Numéro Wave utilisé</dt>
                        <dd class="mt-1 text-sm font-medium tabular-nums">
                            @if (filled($subscription->wave_number_used))
                                {{ $subscription->wave_number_used }}
                            @else
                                <span class="text-brand-400">Non renseigné</span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Référence Wave</dt>
                        <dd class="mt-1 text-sm font-medium">
                            @if (filled($subscription->wave_reference))
                                <span class="font-mono">{{ $subscription->wave_reference }}</span>
                            @else
                                <span class="text-brand-400">Non renseignée</span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Statut</dt>
                        <dd class="mt-1">
                            <x-status-badge :status="$subscription->statut" />
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Validé du</dt>
                        <dd class="mt-1 text-sm font-medium tabular-nums">
                            {{ $subscription->valid_from?->format('d/m/Y') ?? 'Non défini' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Valable jusqu'au</dt>
                        <dd class="mt-1 text-sm font-medium tabular-nums">
                            {{ $subscription->valid_until?->format('d/m/Y') ?? 'Non défini' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Vérifié par</dt>
                        <dd class="mt-1 text-sm font-medium">
                            @if ($subscription->reviewer)
                                {{ $subscription->reviewer->name }}
                                <span class="block text-xs font-normal text-brand-500 tabular-nums">
                                    le {{ $subscription->reviewed_at?->format('d/m/Y à H:i') ?? '—' }}
                                </span>
                            @else
                                <span class="text-brand-400">Jamais vérifié</span>
                            @endif
                        </dd>
                    </div>

                    @if (filled($subscription->reason))
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">Raison</dt>
                            <dd class="mt-1.5 rounded-lg border border-brand-200 bg-brand-50/60 p-3.5 text-sm leading-relaxed dark:border-white/10 dark:bg-white/[0.03]">
                                {{ $subscription->reason }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </section>
        </div>

        {{-- (3) Décision --}}
        <div class="space-y-6">
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Décision</h2>
                    <span class="text-xs text-brand-500">Mise à jour du compte et de l'atelier</span>
                </div>

                <form
                    method="POST"
                    action="{{ route('admin.paiements.review', $subscription) }}"
                    class="space-y-4 p-5"
                    x-data="{ statut: @js(old('statut', $statutActuel)), raison: @js(old('raison', (string) ($subscription->reason ?? ''))), get raisonRequise() { return ['rejete', 'bloque'].includes(this.statut); } }"
                >
                    @csrf
                    @method('PATCH')

                    <div>
                        <p class="cf-label">Décision rapide</p>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                class="cf-btn-primary cf-btn-sm"
                                :class="statut === 'valide' && 'ring-2 ring-brand-accent'"
                                @click="statut = 'valide'"
                            >
                                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                Valider
                            </button>

                            <button
                                type="button"
                                class="cf-btn-danger cf-btn-sm"
                                :class="statut === 'rejete' && 'ring-2 ring-brand-accent'"
                                @click="statut = 'rejete'"
                            >
                                <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
                                Rejeter
                            </button>

                            <button
                                type="button"
                                class="cf-btn-secondary cf-btn-sm"
                                :class="statut === 'bloque' && 'ring-2 ring-brand-accent'"
                                @click="statut = 'bloque'"
                            >
                                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                Bloquer
                            </button>

                            <button
                                type="button"
                                class="cf-btn-ghost cf-btn-sm"
                                :class="statut === 'en_attente' && 'ring-2 ring-brand-accent'"
                                @click="statut = 'en_attente'"
                            >
                                <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                En attente
                            </button>
                        </div>
                        <p class="mt-1.5 text-xs text-brand-600/80 dark:text-brand-300/80">
                            Ces boutons remplissent le champ « Statut » ci-dessous. Ajustez ensuite le motif
                            et les dates avant d'appliquer.
                        </p>
                    </div>

                    <div class="cf-divider"></div>

                    <x-form.select
                        name="statut"
                        label="Statut"
                        :options="$statusOptions"
                        :value="$statutActuel"
                        placeholder="Choisir un statut…"
                        :required="true"
                        x-model="statut"
                        hint="Valide, Rejeté et Bloqué ouvrent ou ferment l'accès de l'atelier à l'application."
                    />

                    <div>
                        <label for="champ-raison" class="cf-label">
                            Raison de la décision
                            <span x-cloak x-show="raisonRequise" class="text-brand-accent" aria-hidden="true">*</span>
                        </label>
                        <textarea
                            id="champ-raison"
                            name="raison"
                            rows="3"
                            x-model="raison"
                            x-bind:required="raisonRequise"
                            x-bind:aria-required="raisonRequise"
                            @error('raison') aria-invalid="true" aria-describedby="raison-error" @enderror
                            class="cf-input resize-y @error('raison') border-red-400 dark:border-red-500/50 @enderror"
                        >{{ old('raison', (string) ($subscription->reason ?? '')) }}</textarea>

                        @error('raison')
                            <p id="raison-error" class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-red-600 dark:text-red-400">
                                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                                {{ $message }}
                            </p>
                        @enderror

                        @if (! $errors->has('raison'))
                            <p class="mt-1.5 text-xs text-brand-600/80 dark:text-brand-300/80">
                                <span x-show="raisonRequise">Obligatoire (5 caractères minimum) pour un rejet ou un blocage.</span>
                                <span x-show="!raisonRequise">Facultative. Elle est affichée au responsable sur sa page d'accès refusé.</span>
                            </p>
                        @endif
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-form.text
                            name="valid_from"
                            label="Valable à partir du"
                            type="date"
                            :value="$subscription->valid_from?->format('Y-m-d')"
                        />

                        <x-form.text
                            name="valid_until"
                            label="Valable jusqu'au"
                            type="date"
                            :value="$subscription->valid_until?->format('Y-m-d')"
                            :min="$subscription->valid_from?->format('Y-m-d')"
                        />
                    </div>

                    <p class="text-xs leading-relaxed text-brand-600/80 dark:text-brand-300/80">
                        Laissez les dates vides pour qu'elles soient calculées automatiquement :
                        la fin de validité devient le jour de la validation + {{ max($subscription->duree_mois, 1) }} mois.
                    </p>

                    <button type="submit" class="cf-btn-primary w-full">
                        <i class="fa-solid fa-gavel" aria-hidden="true"></i>
                        Appliquer la décision
                    </button>
                </form>
            </section>

            @unless ($estPdf || $estImage)
                @if ($aUnePreuve)
                    <p class="rounded-lg border border-brand-200 bg-brand-50/60 p-4 text-xs leading-relaxed text-brand-700 dark:border-white/10 dark:bg-white/[0.03] dark:text-brand-200">
                        <i class="fa-solid fa-circle-info mr-1.5" aria-hidden="true"></i>
                        Format de preuve inhabituel
                        ({{ $subscription->proof_mime ?: 'type non détecté' }}) : ouvrez le fichier pour l'examiner avant de valider.
                    </p>
                @endif
            @endunless
        </div>
    </div>
@endsection
