@extends('layouts.app')

@section('title', 'Modifier la dépense')
@section('header_title', 'Modifier la dépense')
@section('header_subtitle', $depense->libelle)

@section('content')
    @php
        $devise = config('coutureflow.currency');
        $aujourdhui = now()->toDateString();

        $categorieCourante = $depense->categorie instanceof \App\Enums\DepenseCategorie
            ? $depense->categorie->value
            : (string) $depense->categorie;
    @endphp

    <x-page-header
        :title="'Modifier la dépense'"
        :subtitle="$depense->libelle.' — '.$depense->categorieLabel().' · '.$depense->date_depense?->translatedFormat('D j M Y')"
        :back="route('depenses.index')"
    />

    <div class="grid gap-6 lg:grid-cols-3">
        <form
            method="POST"
            action="{{ route('depenses.update', $depense) }}"
            class="space-y-6 lg:col-span-2"
        >
            @csrf
            @method('PUT')

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Détail de la dépense</h2>
                </div>

                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <x-form.text
                        name="libelle"
                        label="Libellé"
                        placeholder="Ex. Achat de wax 5 mètres"
                        maxlength="150"
                        required
                        autofocus
                        :value="$depense->libelle"
                        class="sm:col-span-2"
                    />

                    <x-form.select
                        name="categorie"
                        label="Catégorie"
                        :options="$categorieOptions"
                        :value="$categorieCourante"
                        placeholder="Sélectionner une catégorie"
                        required
                    />

                    <x-form.text
                        name="montant"
                        label="Montant"
                        type="number"
                        min="1"
                        step="1"
                        max="99999999"
                        required
                        :suffix="$devise"
                        :value="(float) $depense->montant"
                    />

                    <x-form.text
                        name="date_depense"
                        label="Date de la dépense"
                        type="date"
                        required
                        :value="$depense->date_depense?->format('Y-m-d')"
                        :max="$aujourdhui"
                        hint="La date ne peut pas être dans le futur."
                    />

                    <x-form.textarea
                        name="notes"
                        label="Notes"
                        :rows="3"
                        placeholder="Fournisseur, référence, mode de paiement…"
                        :value="$depense->notes"
                        hint="2 000 caractères maximum."
                        class="sm:col-span-2"
                    />
                </div>
            </section>

            <div class="flex flex-wrap items-center gap-2">
                <button type="submit" class="cf-btn-primary">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                    Enregistrer
                </button>

                <a href="{{ route('depenses.index') }}" class="cf-btn-secondary">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    Annuler
                </a>

                <x-delete-form
                    :action="route('depenses.destroy', $depense)"
                    title="Supprimer la dépense"
                    :message="'La dépense « '.$depense->libelle.' » sera définitivement supprimée. Continuer ?'"
                    label="Supprimer la dépense"
                    class="cf-btn-danger cf-btn-sm"
                />
            </div>
        </form>

        <aside class="space-y-6 lg:col-span-1">
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">État actuel</h2>
                </div>

                <div class="space-y-4 p-5">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-brand-600 dark:text-brand-300">Montant</span>
                        <span class="font-semibold tabular-nums">
                            {{ number_format((float) $depense->montant, 0, ',', ' ') }}
                            <span class="text-[0.65rem] font-normal text-brand-500 dark:text-brand-400">{{ $devise }}</span>
                        </span>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-brand-600 dark:text-brand-300">Catégorie</span>
                        <span class="cf-badge bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-200">
                            <i class="{{ $depense->categorieIcon() }} text-[0.7em]" aria-hidden="true"></i>
                            {{ $depense->categorieLabel() }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-brand-600 dark:text-brand-300">Date</span>
                        <span class="font-semibold tabular-nums">
                            {{ $depense->date_depense?->format('d/m/Y') ?? '—' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-brand-600 dark:text-brand-300">Enregistrée par</span>
                        <span class="font-semibold">{{ $depense->creator?->name ?? '—' }}</span>
                    </div>

                    <p class="text-xs text-brand-600 dark:text-brand-300">
                        Modifiée {{ $depense->updated_at?->diffForHumans() ?? 'à l\'instant' }}.
                    </p>
                </div>
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Raccourcis</h2>
                </div>

                <div class="space-y-2 p-5">
                    <a href="{{ route('depenses.index') }}" class="cf-btn-secondary w-full">
                        <i class="fa-solid fa-list" aria-hidden="true"></i>
                        Toutes les dépenses
                    </a>

                    <a href="{{ route('depenses.create') }}" class="cf-btn-primary w-full">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        Nouvelle dépense
                    </a>
                </div>
            </section>
        </aside>
    </div>
@endsection
