@extends('layouts.app')

@section('title', 'Nouveau modèle')
@section('header_title', 'Nouveau modèle')
@section('header_subtitle', 'Ajoutez une réalisation à votre catalogue')

@section('content')
    <x-page-header
        title="Nouveau modèle"
        subtitle="Décrivez la réalisation et ajoutez jusqu'à 6 photos."
        :back="route('catalogue.index')"
    />

    <form
        method="POST"
        action="{{ route('modeles.store') }}"
        enctype="multipart/form-data"
        class="grid gap-6 lg:grid-cols-3"
    >
        @csrf

        <div class="space-y-6 lg:col-span-2">
            <section class="cf-card p-5">
                <h2 class="font-serif text-lg font-semibold">Informations</h2>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-form.text
                            name="nom"
                            label="Nom du modèle"
                            :value="old('nom')"
                            placeholder="Robe waxwaxSignature"
                            required
                            autofocus
                        />
                    </div>

                    <x-form.select
                        name="categorie"
                        label="Catégorie"
                        :options="$categorieOptions"
                        :value="old('categorie', 'femme')"
                        placeholder="Choisir une catégorie"
                        required
                    />

                    <x-form.text
                        name="prix_indicatif"
                        label="Prix indicatif"
                        type="number"
                        input-class="tabular-nums"
                        suffix="FCFA"
                        min="0"
                        step="1"
                        :value="old('prix_indicatif')"
                        placeholder="45000"
                        hint="Laissez vide si le prix dépend du tissu choisi."
                    />

                    <x-form.text
                        name="tissu_conseille"
                        label="Tissu conseillé"
                        :value="old('tissu_conseille')"
                        placeholder="Wax, pagne, lin…"
                    />

                    <x-form.text
                        name="duree_estimate"
                        label="Durée estimée"
                        :value="old('duree_estimate')"
                        placeholder="2 semaines"
                    />

                    <div class="sm:col-span-2">
                        <x-form.textarea
                            name="description"
                            label="Description"
                            :rows="5"
                            :value="old('description')"
                            placeholder="Coupe, finitions, détails particuliers…"
                        />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="flex items-center gap-3 text-sm">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                @checked(old('is_active', true))
                                class="size-4 rounded border-brand-300 text-brand-800 focus:ring-brand-500 dark:border-white/20 dark:bg-white/5"
                            >
                            <span class="font-medium text-brand-800 dark:text-brand-100">Modèle actif</span>
                            <span class="text-xs text-brand-500">visible dans la liste des modèles sélectionnables</span>
                        </label>
                    </div>
                </div>
            </section>

            <section class="cf-card p-5">
                <h2 class="font-serif text-lg font-semibold">Photos</h2>
                <p class="mt-1 text-sm text-brand-600 dark:text-brand-300">
                    Les photos sont stockées en privé : elles ne sont servies qu'aux membres de votre atelier.
                </p>

                <div class="mt-4">
                    <x-form.file
                        name="photos[]"
                        label="Ajouter jusqu'à 6 photos"
                        accept="image/jpeg,image/png,image/webp"
                        :max-kb="$maxKb"
                        :multiple="true"
                        preview="multi"
                        placeholder="Cliquez pour choisir vos photos"
                        hint="JPG, PNG ou WEBP — {{ round($maxKb / 1024) }} Mo maximum par photo, 6 photos au maximum."
                    />
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="cf-card p-5">
                <h2 class="font-serif text-lg font-semibold">Enregistrer</h2>
                <div class="mt-4 space-y-2">
                    <button type="submit" class="cf-btn-primary w-full">
                        <i class="fa-solid fa-check" aria-hidden="true"></i>
                        Ajouter au catalogue
                    </button>
                    <a href="{{ route('catalogue.index') }}" class="cf-btn-secondary w-full">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        Annuler
                    </a>
                </div>
            </section>

            <section class="cf-card p-5">
                <h2 class="font-serif text-base font-semibold">Bon à savoir</h2>
                <ul class="mt-3 space-y-2 text-sm text-brand-600 dark:text-brand-300">
                    <li class="flex gap-2">
                        <i class="fa-solid fa-circle-check mt-0.5 text-[0.65rem] text-emerald-500" aria-hidden="true"></i>
                        Un modèle inactif reste consultable mais ne peut plus être choisi sur une commande.
                    </li>
                    <li class="flex gap-2">
                        <i class="fa-solid fa-circle-check mt-0.5 text-[0.65rem] text-emerald-500" aria-hidden="true"></i>
                        Le prix indicatif est informatif : la commande garde son propre prix.
                    </li>
                </ul>
            </section>
        </div>
    </form>
@endsection
