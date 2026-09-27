@extends('layouts.app')

@section('title', 'Modifier '.$client->nom)
@section('header_title', 'Modifier '.$client->nom)
@section('header_subtitle', 'Mettez à jour la fiche client')

@section('content')
    <x-page-header
        :title="'Modifier '.$client->nom"
        :subtitle="'Coordonnées, photo et statut de la fiche client'"
        :back="route('clients.show', $client)"
    >
        <x-slot:actions>
            <a href="{{ route('mesures.index', $client) }}" class="cf-btn-secondary">
                <i class="fa-solid fa-ruler" aria-hidden="true"></i>
                Mesures
            </a>

            <a href="{{ route('clients.show', $client) }}" class="cf-btn-secondary">
                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                Voir la fiche
            </a>
        </x-slot:actions>
    </x-page-header>

    <form
        method="POST"
        action="{{ route('clients.update', $client) }}"
        enctype="multipart/form-data"
        class="grid gap-6 lg:grid-cols-3"
    >
        @csrf
        @method('PUT')

        <div class="space-y-6 lg:col-span-2">
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Coordonnées</h2>
                    <span class="text-xs text-brand-500">
                        <span class="text-brand-accent" aria-hidden="true">*</span> champs obligatoires
                    </span>
                </div>

                <div class="grid gap-4 p-5 sm:grid-cols-2 sm:p-6">
                    <x-form.text
                        name="nom"
                        label="Nom complet"
                        type="text"
                        :value="$client->nom"
                        :required="true"
                        autocomplete="name"
                        placeholder="Ex. Awa Diop"
                        maxlength="150"
                    />

                    <x-form.text
                        name="telephone"
                        label="Téléphone"
                        type="tel"
                        :value="$client->telephone"
                        :required="true"
                        autocomplete="tel"
                        placeholder="Ex. 77 000 00 00"
                        maxlength="40"
                        autofocus
                    />

                    <x-form.text
                        name="email"
                        label="Adresse email"
                        type="email"
                        :value="$client->email"
                        autocomplete="email"
                        placeholder="Ex. awa.diop@email.com"
                        maxlength="190"
                        class="sm:col-span-2"
                    />

                    <x-form.text
                        name="adresse"
                        label="Adresse"
                        type="text"
                        :value="$client->adresse"
                        autocomplete="street-address"
                        placeholder="Ex. Médina, Rue 10, Dakar"
                        maxlength="255"
                        class="sm:col-span-2"
                    />
                </div>
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Notes internes</h2>
                </div>

                <div class="p-5 sm:p-6">
                    <x-form.textarea
                        name="notes"
                        label="Préférences, matières, allergies aux tissus…"
                        :rows="4"
                        :value="$client->notes"
                        placeholder="Ex. Préfère le lin clair, coupe cintrée, livraison à domicile."
                        maxlength="2000"
                    />
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Photo</h2>
                </div>

                <div class="space-y-4 p-5 sm:p-6">
                    @if ($client->hasPhoto())
                        <div class="flex items-center gap-4 rounded-lg border border-brand-200 bg-brand-50/60 p-3 dark:border-white/10 dark:bg-white/5">
                            <img
                                src="{{ route('clients.photo', $client) }}"
                                alt="Photo actuelle de {{ $client->nom }}"
                                class="size-20 shrink-0 rounded-lg object-cover"
                            >
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold">Photo actuelle</p>
                                <p class="mt-0.5 text-xs text-brand-600 dark:text-brand-300">
                                    Choisir un nouveau fichier remplace cette photo.
                                </p>
                            </div>
                        </div>

                        <label class="flex cursor-pointer items-start gap-3">
                            <input
                                type="checkbox"
                                name="remove_photo"
                                value="1"
                                @checked(old('remove_photo'))
                                class="mt-0.5 size-4 shrink-0 rounded border-brand-300 text-brand-700 focus:ring-brand-500/40 dark:border-white/20 dark:bg-white/5"
                            >
                            <span>
                                <span class="block text-sm font-medium text-brand-800 dark:text-brand-100">
                                    Supprimer la photo
                                </span>
                                <span class="mt-0.5 block text-xs text-brand-600 dark:text-brand-300">
                                    Le fichier sera définitivement effacé du serveur.
                                </span>
                            </span>
                        </label>

                        @error('remove_photo')
                            <p class="flex items-center gap-1.5 text-xs font-medium text-red-600 dark:text-red-400">
                                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                                {{ $message }}
                            </p>
                        @enderror

                        <div class="cf-divider"></div>
                    @endif

                    <x-form.file
                        name="photo"
                        label="Photo du client"
                        accept="image/jpeg,image/png,image/webp"
                        :max-kb="$maxKb"
                        :placeholder="$client->hasPhoto() ? 'Remplacer par une nouvelle photo' : 'Cliquez pour choisir une photo'"
                        hint="JPG, PNG ou WEBP — {{ round($maxKb / 1024) }} Mo maximum."
                    />
                </div>
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Statut</h2>
                </div>

                <div class="p-5 sm:p-6">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            @checked(old('is_active', $client->is_active))
                            class="mt-0.5 size-4 shrink-0 rounded border-brand-300 text-brand-700 focus:ring-brand-500/40 dark:border-white/20 dark:bg-white/5"
                        >
                        <span>
                            <span class="block text-sm font-medium text-brand-800 dark:text-brand-100">
                                Client actif
                            </span>
                            <span class="mt-0.5 block text-xs text-brand-600 dark:text-brand-300">
                                Un client inactif reste consultable mais n'est plus proposé lors d'une nouvelle commande.
                            </span>
                        </span>
                    </label>
                </div>
            </section>
        </div>

        <div class="flex flex-wrap items-center gap-2 lg:col-span-3">
            <button type="submit" class="cf-btn-primary">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                Enregistrer
            </button>

            <a href="{{ route('clients.show', $client) }}" class="cf-btn-secondary">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                Annuler
            </a>

            <x-delete-form
                :action="route('clients.destroy', $client)"
                title="Supprimer le client"
                :message="'La fiche de '.$client->nom.' sera définitivement supprimée. Continuer ?'"
                label="Supprimer le client"
                class="cf-btn-danger ml-auto"
            />
        </div>
    </form>
@endsection
