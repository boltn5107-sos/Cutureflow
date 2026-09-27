@extends('layouts.app')

@section('title', 'Modifier le modèle')
@section('header_title', 'Modifier le modèle')
@section('header_subtitle', $modele->nom)

@section('content')
    <x-page-header
        :title="$modele->nom"
        subtitle="Mettez à jour les informations et les photos de ce modèle."
        :back="route('modeles.show', $modele)"
    >
        <x-slot:actions>
            <a href="{{ route('modeles.show', $modele) }}" class="cf-btn-secondary">
                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                Voir la fiche
            </a>
        </x-slot:actions>
    </x-page-header>

    <form
        method="POST"
        action="{{ route('modeles.update', $modele) }}"
        enctype="multipart/form-data"
        class="grid gap-6 lg:grid-cols-3"
    >
        @csrf
        @method('PUT')

        <div class="space-y-6 lg:col-span-2">
            <section class="cf-card p-5">
                <h2 class="font-serif text-lg font-semibold">Informations</h2>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-form.text
                            name="nom"
                            label="Nom du modèle"
                            :value="$modele->nom"
                            required
                            autofocus
                        />
                    </div>

                    <x-form.select
                        name="categorie"
                        label="Catégorie"
                        :options="$categorieOptions"
                        :value="$modele->categorie instanceof \BackedEnum ? $modele->categorie->value : $modele->categorie"
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
                        :value="$modele->prix_indicatif"
                    />

                    <x-form.text
                        name="tissu_conseille"
                        label="Tissu conseillé"
                        :value="$modele->tissu_conseille"
                    />

                    <x-form.text
                        name="duree_estimate"
                        label="Durée estimée"
                        :value="$modele->duree_estimate"
                    />

                    <div class="sm:col-span-2">
                        <x-form.textarea
                            name="description"
                            label="Description"
                            :rows="5"
                            :value="$modele->description"
                        />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="flex items-center gap-3 text-sm">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                @checked(old('is_active', $modele->is_active))
                                class="size-4 rounded border-brand-300 text-brand-800 focus:ring-brand-500 dark:border-white/20 dark:bg-white/5"
                            >
                            <span class="font-medium text-brand-800 dark:text-brand-100">Modèle actif</span>
                        </label>
                    </div>
                </div>
            </section>

            <section class="cf-card p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-serif text-lg font-semibold">Photos existantes</h2>
                    <span class="text-xs text-brand-500">{{ $modele->photos->count() }} photo(s)</span>
                </div>

                @if ($modele->photos->isEmpty())
                    <p class="mt-3 text-sm text-brand-500">Aucune photo pour ce modèle.</p>
                @else
                    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        @foreach ($modele->photos->sortBy('position') as $photo)
                            <figure class="group relative overflow-hidden rounded-lg border border-brand-200 dark:border-white/10">
                                <img
                                    src="{{ route('modeles.photo', $photo) }}"
                                    alt="{{ $modele->nom }} — photo {{ $loop->iteration }}"
                                    class="aspect-square w-full object-cover"
                                >

                                <figcaption class="absolute inset-x-0 bottom-0 flex items-center justify-between gap-2 bg-brand-900/80 px-2 py-1.5 text-[0.65rem] text-white">
                                    <span class="tabular-nums">#{{ $photo->position + 1 }}</span>

                                    <button
                                        type="button"
                                        @click="$store.confirm.ask({ title: 'Supprimer la photo', message: 'Cette photo sera définitivement supprimée du modèle.', confirmLabel: 'Supprimer', tone: 'danger', onConfirm: () => $refs['photo-{{ $photo->id }}'].submit() })"
                                        class="rounded p-1 transition hover:bg-white/20"
                                        aria-label="Supprimer la photo {{ $loop->iteration }}"
                                    >
                                        <i class="fa-solid fa-trash text-[0.6rem]" aria-hidden="true"></i>
                                    </button>
                                </figcaption>
                            </figure>

                            <form
                                x-ref="photo-{{ $photo->id }}"
                                method="POST"
                                action="{{ route('modeles.photos.destroy', [$modele, $photo]) }}"
                                class="hidden"
                            >
                                @csrf
                                @method('DELETE')
                            </form>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="cf-card p-5">
                <h2 class="font-serif text-lg font-semibold">Ajouter des photos</h2>

                <div class="mt-4">
                    <x-form.file
                        name="photos[]"
                        label="Ajouter jusqu'à 6 photos"
                        accept="image/jpeg,image/png,image/webp"
                        :max-kb="$maxKb"
                        :multiple="true"
                        preview="multi"
                        placeholder="Cliquez pour choisir vos photos"
                        hint="Elles s'ajouteront à la fin de la galerie du modèle."
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
                        Enregistrer
                    </button>
                    <a href="{{ route('modeles.show', $modele) }}" class="cf-btn-secondary w-full">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        Annuler
                    </a>

                    <x-delete-form
                        :action="route('modeles.destroy', $modele)"
                        title="Supprimer le modèle"
                        message="Le modèle « {{ $modele->nom }} » et ses photos seront supprimés définitivement. Continuer ?"
                        label="Supprimer le modèle"
                        class="cf-btn-danger w-full"
                    />
                </div>
            </section>
        </div>
    </form>
@endsection
