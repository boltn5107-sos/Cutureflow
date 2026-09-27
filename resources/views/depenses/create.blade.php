@extends('layouts.app')

@section('title', 'Nouvelle dépense')
@section('header_title', 'Nouvelle dépense')
@section('header_subtitle', 'Enregistrer une charge de l\'atelier et son montant')

@section('content')
    @php
        $devise = config('coutureflow.currency');
        $aujourdhui = now()->toDateString();

        $categoriesLegende = collect($categorieOptions)->map(fn ($libelle, $valeur) => [
            'libelle' => $libelle,
            'icon' => \App\Enums\DepenseCategorie::tryFrom((string) $valeur)?->icon() ?? 'fa-solid fa-box-open',
        ]);
    @endphp

    <x-page-header
        title="Nouvelle dépense"
        subtitle="Renseignez le libellé, la catégorie, le montant et la date de la charge."
        :back="route('depenses.index')"
    />

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('depenses.store') }}" class="space-y-6 lg:col-span-2">
            @csrf

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
                        class="sm:col-span-2"
                    />

                    <x-form.select
                        name="categorie"
                        label="Catégorie"
                        :options="$categorieOptions"
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
                    />

                    <x-form.text
                        name="date_depense"
                        label="Date de la dépense"
                        type="date"
                        required
                        :value="$aujourdhui"
                        :max="$aujourdhui"
                        hint="La date ne peut pas être dans le futur."
                    />

                    <x-form.textarea
                        name="notes"
                        label="Notes"
                        :rows="3"
                        placeholder="Fournisseur, référence, mode de paiement…"
                        hint="2 000 caractères maximum."
                        class="sm:col-span-2"
                    />
                </div>
            </section>

            <div class="flex flex-wrap items-center gap-2">
                <button type="submit" class="cf-btn-primary">
                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                    Enregistrer
                </button>

                <a href="{{ route('depenses.index') }}" class="cf-btn-secondary">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    Annuler
                </a>
            </div>
        </form>

        <aside class="space-y-6 lg:col-span-1">
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Catégories</h2>
                </div>

                <ul class="divide-y divide-brand-100 dark:divide-white/5">
                    @foreach ($categoriesLegende as $categorie)
                        <li class="flex items-center gap-2.5 px-5 py-2.5 text-sm">
                            <i class="{{ $categorie['icon'] }} w-4 shrink-0 text-center text-brand-400" aria-hidden="true"></i>
                            <span class="min-w-0 truncate">{{ $categorie['libelle'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Bon à savoir</h2>
                </div>

                <div class="space-y-2 p-5 text-sm text-brand-600 dark:text-brand-300">
                    <p>
                        Le montant saisi est déduit du solde de la période dans la caisse et sur le tableau de bord.
                    </p>
                    <p>
                        La date choisie détermine la période de rattachement de la dépense.
                    </p>
                </div>
            </section>
        </aside>
    </div>
@endsection
