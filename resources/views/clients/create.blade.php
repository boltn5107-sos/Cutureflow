@extends('layouts.app')

@section('title', 'Nouveau client')
@section('header_title', 'Nouveau client')
@section('header_subtitle', 'Créez une fiche client pour l\'atelier')

@section('content')
    <x-page-header
        title="Nouveau client"
        subtitle="Renseignez les coordonnées du client, vous pourrez ensuite prendre ses mesures et créer ses commandes."
        :back="route('clients.index')"
    />

    <form
        method="POST"
        action="{{ route('clients.store') }}"
        enctype="multipart/form-data"
        class="grid gap-6 lg:grid-cols-3"
    >
        @csrf

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
                        :required="true"
                        autocomplete="name"
                        placeholder="Ex. Awa Diop"
                        maxlength="150"
                    />

                    <x-form.text
                        name="telephone"
                        label="Téléphone"
                        type="tel"
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
                        autocomplete="email"
                        placeholder="Ex. awa.diop@email.com"
                        maxlength="190"
                        class="sm:col-span-2"
                    />

                    <x-form.text
                        name="adresse"
                        label="Adresse"
                        type="text"
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

                <div class="p-5 sm:p-6">
                    <x-form.file
                        name="photo"
                        label="Photo du client"
                        accept="image/jpeg,image/png,image/webp"
                        :max-kb="$maxKb"
                        placeholder="Cliquez pour choisir une photo"
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
                            @checked(old('is_active', true))
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

            <a href="{{ route('clients.index') }}" class="cf-btn-secondary">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                Annuler
            </a>
        </div>
    </form>
@endsection
