@extends('layouts.app')

@section('title', 'Mesures de '.$client->nom)
@section('header_title', 'Mesures de '.$client->nom)
@section('header_subtitle', number_format($mesures->total(), 0, ',', ' ').' mesure(s) enregistrée(s)')

@section('content')
    @php
        $aujourdhui = now()->toDateString();
        $libellesSuggeres = array_keys($mesuresCourantes);
        $categoriesCourantes = collect($mesuresCourantes)
            ->pluck('categorie')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $filtreActif = collect($filtres)->filter(fn ($valeur) => $valeur !== null && $valeur !== '')->isNotEmpty();

        // Une mise à jour en échec rouvre la ligne concernée pour afficher les valeurs saisies.
        $champsEditables = ['libelle', 'valeur', 'unite', 'date_mesure', 'commentaire'];
        $editionOuverte = $errors->hasAny($champsEditables);
        $mesureEditee = $editionOuverte
            ? ($mesures->first(fn ($m) => (string) $m->libelle === (string) old('libelle')) ?? $mesures->first())
            : null;
    @endphp

    <x-page-header
        :title="'Mesures de '.$client->nom"
        subtitle="Ajoutez, corrigez et comparez les relevés de mesures du client."
        :back="route('clients.show', $client)"
    >
        <x-slot:actions>
            <a href="{{ route('commandes.create', ['client_id' => $client->id]) }}" class="cf-btn-secondary">
                <i class="fa-solid fa-scissors" aria-hidden="true"></i>
                Nouvelle commande
            </a>

            <a href="{{ route('clients.edit', $client) }}" class="cf-btn-secondary">
                <i class="fa-solid fa-pen" aria-hidden="true"></i>
                Modifier le client
            </a>
        </x-slot:actions>
    </x-page-header>

    @php
        // Une ligne déjà saisie est renvoyée par le validateur, sinon on
        // repart des quatre mesures proposées au tailleur.
        $lignesAnciennes = old('mesures');
        $lignesInitiales = is_array($lignesAnciennes) && $lignesAnciennes !== []
            ? $lignesAnciennes
            : array_map(
                fn (string $libelle) => ['libelle' => $libelle, 'valeur' => '', 'unite' => 'cm'],
                $mesuresParDefaut,
            );
    @endphp

    <div
        class="grid gap-6 lg:grid-cols-3"
        x-data="{
            categorie: @js(old('categorie', '')),
            lignes: @js($lignesInitiales),
            ajouter() {
                this.lignes.push({ libelle: '', valeur: '', unite: 'cm' });
            },
            ajouterMesure(libelle, categorie) {
                this.lignes.push({ libelle: libelle, valeur: '', unite: 'cm' });
                this.categorie = categorie;
            },
            retirer(index) {
                this.lignes.splice(index, 1);
            },
        }"
    >
        <div class="space-y-6 lg:col-span-2">
            {{-- Formulaire d'ajout --}}
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Ajouter une mesure</h2>
                    <span class="text-xs text-brand-500">
                        <span class="text-brand-accent" aria-hidden="true">*</span> champs obligatoires
                    </span>
                </div>

                <form method="POST" action="{{ route('mesures.store', $client) }}">
                    @csrf

                    <div class="space-y-3 p-5 sm:p-6">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm text-brand-700 dark:text-brand-200">
                                Quatre mesures sont proposées. Renommez-les librement et ajoutez-en
                                d'autres si nécessaire.
                            </p>

                            <button
                                type="button"
                                class="cf-btn-secondary cf-btn-sm"
                                x-on:click="ajouter()"
                            >
                                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                Ajouter une mesure
                            </button>
                        </div>

                        <template x-for="(ligne, index) in lignes" :key="index">
                            <div class="grid gap-3 rounded-xl border border-brand-200 bg-brand-50/40 p-3 sm:grid-cols-[minmax(0,1fr)_8rem_10rem_auto] sm:items-end dark:border-white/10 dark:bg-white/[0.03]">
                                <div>
                                    <label class="cf-label" :for="'mesure-' + index">
                                        Mesure
                                        <span class="text-brand-accent" aria-hidden="true">*</span>
                                    </label>
                                    <input
                                        :id="'mesure-' + index"
                                        class="cf-input"
                                        type="text"
                                        name="mesures[{{ '{' }}index{{ '}' }}][libelle]"
                                        x-model="ligne.libelle"
                                        list="mesures-suggerees"
                                        autocomplete="off"
                                        maxlength="120"
                                        placeholder="Ex. Tour de poitrine"
                                        x-bind:aria-invalid="ligne.libelle && !ligne.valeur ? 'true' : 'false'"
                                    >
                                </div>

                                <div>
                                    <label class="cf-label" :for="'valeur-' + index">
                                        Valeur
                                        <span class="text-brand-accent" aria-hidden="true">*</span>
                                    </label>
                                    <input
                                        :id="'valeur-' + index"
                                        class="cf-input"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        max="9999.99"
                                        name="mesures[{{ '{' }}index{{ '}' }}][valeur]"
                                        x-model="ligne.valeur"
                                        placeholder="Ex. 92"
                                    >
                                </div>

                                <div>
                                    <label class="cf-label" :for="'unite-' + index">Unité</label>
                                    <select
                                        :id="'unite-' + index"
                                        class="cf-select"
                                        name="mesures[{{ '{' }}index{{ '}' }}][unite]"
                                        x-model="ligne.unite"
                                    >
                                        @foreach ($unites as $valeurUnite => $libelleUnite)
                                            <option value="{{ $valeurUnite }}">{{ $libelleUnite }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <button
                                    type="button"
                                    class="cf-btn-ghost cf-btn-sm text-red-600 dark:text-red-400"
                                    x-on:click="retirer(index)"
                                    x-show="lignes.length > 1"
                                    x-bind:aria-label="'Retirer la mesure ' + (index + 1)"
                                >
                                    <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                    Retirer
                                </button>

                                <template x-if="ligne.libelle && !ligne.valeur">
                                    <p class="text-xs font-medium text-red-600 sm:col-span-4 dark:text-red-400">
                                        Indiquez la valeur de « <span x-text="ligne.libelle"></span> » ou effacez son intitulé.
                                    </p>
                                </template>
                            </div>
                        </template>

                        @error('mesures')
                            <p class="flex items-center gap-1.5 text-xs font-medium text-red-600 dark:text-red-400">
                                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                                {{ $message }}
                            </p>
                        @enderror

                        @error('mesures.*.libelle')
                            <p class="flex items-center gap-1.5 text-xs font-medium text-red-600 dark:text-red-400">
                                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                                {{ $message }}
                            </p>
                        @enderror

                        @error('mesures.*.valeur')
                            <p class="flex items-center gap-1.5 text-xs font-medium text-red-600 dark:text-red-400">
                                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                                {{ $message }}
                            </p>
                        @enderror

                        <div class="grid gap-4 border-t border-brand-200/70 pt-4 sm:grid-cols-2 dark:border-white/10">
                            <x-form.text
                                name="date_mesure"
                                id="nouvelle-mesure-date"
                                label="Date de la mesure"
                                type="date"
                                :value="old('date_mesure', $aujourdhui)"
                                :required="true"
                                min="2000-01-01"
                                :max="$aujourdhui"
                            />

                            <x-form.text
                                name="categorie"
                                id="nouvelle-mesure-categorie"
                                label="Catégorie"
                                type="text"
                                :value="old('categorie')"
                                list="categories-mesures"
                                autocomplete="off"
                                placeholder="Ex. Haut du corps"
                                maxlength="60"
                                x-model="categorie"
                                hint="Facultative : renseignée automatiquement pour les mesures courantes."
                            />

                            <div class="sm:col-span-2">
                                <x-form.textarea
                                    name="commentaire"
                                    id="nouvelle-mesure-commentaire"
                                    label="Commentaire"
                                    :rows="2"
                                    :value="old('commentaire')"
                                    placeholder="Ex. Mesure prise debout, sans chaussures."
                                    maxlength="1000"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 border-t border-brand-200/70 px-5 py-4 dark:border-white/10">
                        <button type="submit" class="cf-btn-primary">
                            <i class="fa-solid fa-check" aria-hidden="true"></i>
                            Enregistrer les mesures
                        </button>

                        <p class="text-xs text-brand-500 dark:text-brand-400">
                            Les lignes laissées vides sont ignorées. Chaque valeur est conservée dans l'historique.
                        </p>
                    </div>
                </form>

                <datalist id="mesures-suggerees">
                    @foreach ($libellesSuggeres as $libelleSuggere)
                        <option value="{{ $libelleSuggere }}"></option>
                    @endforeach
                </datalist>

                <datalist id="categories-mesures">
                    @foreach ($categoriesCourantes as $categorieCourante)
                        <option value="{{ $categorieCourante }}"></option>
                    @endforeach
                </datalist>
            </section>

            {{-- Recherche / filtres --}}
            <form
                method="GET"
                action="{{ route('mesures.index', $client) }}"
                class="cf-card p-4 sm:p-5"
            >
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_14rem_auto] lg:items-end">
                    <x-form.text
                        name="q"
                        id="filtre-mesures-q"
                        label="Rechercher"
                        type="search"
                        :value="$filtres['q'] ?? ''"
                        placeholder="Mesure ou catégorie…"
                        autocomplete="off"
                    />

                    <x-form.select
                        name="categorie"
                        id="filtre-mesures-categorie"
                        label="Catégorie"
                        :options="$categoriesCourantes->all()"
                        :value="$filtres['categorie'] ?? ''"
                        placeholder="Toutes les catégories"
                    />

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="cf-btn-primary">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                            Filtrer
                        </button>

                        @if ($filtreActif)
                            <a href="{{ route('mesures.index', $client) }}" class="cf-btn-ghost">
                                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                Réinitialiser
                            </a>
                        @endif
                    </div>
                </div>

                @if ($filtreActif)
                    <p class="mt-3 flex flex-wrap items-center gap-1.5 text-xs text-brand-600 dark:text-brand-300">
                        <i class="fa-solid fa-filter" aria-hidden="true"></i>
                        Filtres actifs
                        @if (filled($filtres['q'] ?? null))
                            — recherche : « {{ $filtres['q'] }} »
                        @endif
                        @if (filled($filtres['categorie'] ?? null))
                            — catégorie : {{ $filtres['categorie'] }}
                        @endif
                    </p>
                @endif
            </form>

            {{-- Tableau avec édition en ligne --}}
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Relevé des mesures</h2>
                    <span class="text-xs text-brand-500 dark:text-brand-400">
                        <span class="font-semibold tabular-nums">{{ number_format($mesures->total(), 0, ',', ' ') }}</span>
                        mesure(s)
                    </span>
                </div>

                @if ($editionOuverte)
                    <div class="border-b border-red-200 bg-red-50 px-5 py-4 dark:border-red-500/20 dark:bg-red-500/10">
                        <p class="flex items-center gap-2 text-sm font-semibold text-red-700 dark:text-red-300">
                            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                            La mesure n'a pas pu être enregistrée
                        </p>
                        <ul class="mt-1.5 space-y-1 text-xs text-red-700 dark:text-red-300">
                            @error('libelle')
                                <li class="flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                                    {{ $message }}
                                </li>
                            @enderror
                            @error('valeur')
                                <li class="flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                                    {{ $message }}
                                </li>
                            @enderror
                            @error('unite')
                                <li class="flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                                    {{ $message }}
                                </li>
                            @enderror
                            @error('date_mesure')
                                <li class="flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                                    {{ $message }}
                                </li>
                            @enderror
                            @error('commentaire')
                                <li class="flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                                    {{ $message }}
                                </li>
                            @enderror
                        </ul>
                    </div>
                @endif

                @if ($mesures->isEmpty())
                    <x-empty-state
                        icon="fa-solid fa-ruler-combined"
                        title="Aucune mesure"
                        :message="$filtreActif
                            ? 'Aucune mesure ne correspond aux filtres sélectionnés.'
                            : 'Enregistrez le premier relevé de mesures pour ce client via le formulaire ci-dessus.'"
                        :action="$filtreActif ? route('mesures.index', $client) : null"
                        :action-label="$filtreActif ? 'Réinitialiser les filtres' : null"
                    />
                @else
                    <div class="overflow-x-auto">
                        <table class="cf-table">
                            <caption class="sr-only">Mesures enregistrées pour {{ $client->nom }}</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Mesure</th>
                                    <th scope="col">Catégorie</th>
                                    <th scope="col">Valeur</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Commentaire</th>
                                    <th scope="col" class="text-right">Actions</th>
                                </tr>
                            </thead>

                            @foreach ($mesures as $mesure)
                                <tbody x-data="{ editing: {{ $mesureEditee?->id === $mesure->id ? 'true' : 'false' }} }">
                                    <tr>
                                        <td class="font-medium">{{ $mesure->libelle }}</td>

                                        <td class="text-xs">
                                            @if (filled($mesure->categorie))
                                                <span class="cf-badge">{{ $mesure->categorie }}</span>
                                            @else
                                                <span class="text-brand-400">—</span>
                                            @endif
                                        </td>

                                        <td class="font-semibold whitespace-nowrap tabular-nums">{{ $mesure->valeurFormatee() }}</td>

                                        <td class="whitespace-nowrap tabular-nums">
                                            {{ $mesure->date_mesure?->format('d/m/Y') ?? '—' }}
                                        </td>

                                        <td class="max-w-xs text-xs">
                                            @if (filled($mesure->commentaire))
                                                <span class="line-clamp-2">{{ $mesure->commentaire }}</span>
                                            @else
                                                <span class="text-brand-400">—</span>
                                            @endif
                                        </td>

                                        <td>
                                            <div class="flex items-center justify-end gap-2">
                                                <button
                                                    type="button"
                                                    class="cf-btn-secondary cf-btn-sm"
                                                    x-on:click="editing = !editing"
                                                    :aria-expanded="editing.toString()"
                                                    aria-controls="edition-{{ $mesure->id }}"
                                                >
                                                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                                    Modifier
                                                </button>

                                                <x-delete-form
                                                    :action="route('mesures.destroy', $mesure)"
                                                    title="Supprimer la mesure"
                                                    :message="'La mesure « '.$mesure->libelle.' » sera définitivement supprimée. Continuer ?'"
                                                    label="Supprimer"
                                                    class="cf-btn-danger cf-btn-sm"
                                                />
                                            </div>
                                        </td>
                                    </tr>

                                    <tr id="edition-{{ $mesure->id }}" x-show="editing" x-cloak>
                                        <td colspan="6" class="bg-brand-50/60 dark:bg-white/5">
                                            <form
                                                method="POST"
                                                action="{{ route('mesures.update', $mesure) }}"
                                                class="grid gap-3 p-1 sm:grid-cols-6"
                                            >
                                                @csrf
                                                @method('PUT')

                                                <div class="sm:col-span-2">
                                                    <label for="edition-{{ $mesure->id }}-libelle" class="cf-label">Mesure</label>
                                                    <input
                                                        id="edition-{{ $mesure->id }}-libelle"
                                                        name="libelle"
                                                        type="text"
                                                        value="{{ old('libelle', $mesure->libelle) }}"
                                                        list="mesures-suggerees"
                                                        autocomplete="off"
                                                        maxlength="120"
                                                        required
                                                        aria-invalid="{{ $errors->has('libelle') ? 'true' : 'false' }}"
                                                        class="cf-input"
                                                    >
                                                </div>

                                                <div>
                                                    <label for="edition-{{ $mesure->id }}-valeur" class="cf-label">Valeur</label>
                                                    <input
                                                        id="edition-{{ $mesure->id }}-valeur"
                                                        name="valeur"
                                                        type="number"
                                                        step="0.1"
                                                        min="0"
                                                        max="9999.99"
                                                        value="{{ old('valeur', $mesure->valeur) }}"
                                                        required
                                                        aria-invalid="{{ $errors->has('valeur') ? 'true' : 'false' }}"
                                                        class="cf-input"
                                                    >
                                                </div>

                                                <div>
                                                    <label for="edition-{{ $mesure->id }}-unite" class="cf-label">Unité</label>
                                                    <select
                                                        id="edition-{{ $mesure->id }}-unite"
                                                        name="unite"
                                                        class="cf-select"
                                                    >
                                                        @foreach ($unites as $valeurUnite => $libelleUnite)
                                                            <option
                                                                value="{{ $valeurUnite }}"
                                                                @selected((string) old('unite', $mesure->unite) === (string) $valeurUnite)
                                                            >
                                                                {{ $libelleUnite }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <label for="edition-{{ $mesure->id }}-date" class="cf-label">Date</label>
                                                    <input
                                                        id="edition-{{ $mesure->id }}-date"
                                                        name="date_mesure"
                                                        type="date"
                                                        min="2000-01-01"
                                                        max="{{ $aujourdhui }}"
                                                        value="{{ old('date_mesure', $mesure->date_mesure?->format('Y-m-d')) }}"
                                                        required
                                                        aria-invalid="{{ $errors->has('date_mesure') ? 'true' : 'false' }}"
                                                        class="cf-input"
                                                    >
                                                </div>

                                                <div class="sm:col-span-6">
                                                    <label for="edition-{{ $mesure->id }}-commentaire" class="cf-label">Commentaire</label>
                                                    <input
                                                        id="edition-{{ $mesure->id }}-commentaire"
                                                        name="commentaire"
                                                        type="text"
                                                        value="{{ old('commentaire', $mesure->commentaire) }}"
                                                        maxlength="1000"
                                                        aria-invalid="{{ $errors->has('commentaire') ? 'true' : 'false' }}"
                                                        class="cf-input"
                                                    >
                                                </div>

                                                <div class="flex flex-wrap items-center gap-2 sm:col-span-6">
                                                    <button type="submit" class="cf-btn-primary">
                                                        <i class="fa-solid fa-check" aria-hidden="true"></i>
                                                        Enregistrer
                                                    </button>

                                                    <button
                                                        type="button"
                                                        class="cf-btn-secondary"
                                                        x-on:click="editing = false"
                                                    >
                                                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                                        Annuler
                                                    </button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                </tbody>
                            @endforeach
                        </table>
                    </div>

                    <x-pagination :paginator="$mesures" label="mesures" />
                @endif
            </section>

            {{-- Évolution --}}
            @if ($mesuresComparees->isNotEmpty())
                <section class="cf-card overflow-hidden">
                    <div class="cf-card-header">
                        <h2 class="font-serif text-lg font-semibold">Évolution</h2>
                        <span class="text-xs text-brand-500 dark:text-brand-400">
                            Mesures relevées au moins deux fois sur les 12 derniers mois
                        </span>
                    </div>

                    <ul class="divide-y divide-brand-100 dark:divide-white/5">
                        @foreach ($mesuresComparees as $libelle => $releves)
                            @php
                                $premiere = $releves->first();
                                $derniere = $releves->last();
                                $variation = round((float) $derniere->valeur - (float) $premiere->valeur, 2);
                                $variationFormatee = rtrim(rtrim(number_format($variation, 1, ',', ' '), '0'), ',');
                                $unite = $derniere->unite ?: 'cm';

                                $classesVariation = match (true) {
                                    $variation > 0 => 'bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-300',
                                    $variation < 0 => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
                                    default => 'bg-stone-100 text-stone-700 dark:bg-white/10 dark:text-stone-300',
                                };

                                $iconeVariation = match (true) {
                                    $variation > 0 => 'fa-solid fa-arrow-trend-up',
                                    $variation < 0 => 'fa-solid fa-arrow-trend-down',
                                    default => 'fa-solid fa-minus',
                                };
                            @endphp

                            <li class="px-5 py-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="font-semibold">{{ $libelle }}</p>
                                        <p class="text-xs text-brand-500 dark:text-brand-400">
                                            {{ $releves->count() }} relevé(s) — du {{ $premiere->date_mesure?->format('d/m/Y') ?? '—' }} au {{ $derniere->date_mesure?->format('d/m/Y') ?? '—' }}
                                        </p>
                                    </div>

                                    <span class="cf-badge {{ $classesVariation }}">
                                        <i class="{{ $iconeVariation }} text-[0.7em]" aria-hidden="true"></i>
                                        {{ $variation > 0 ? '+' : '' }}{{ $variationFormatee }} {{ $unite }}
                                    </span>
                                </div>

                                <ol class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($releves as $releve)
                                        <li class="rounded-lg border border-brand-200 bg-brand-50/60 px-3 py-2 dark:border-white/10 dark:bg-white/5">
                                            <span class="block text-sm font-semibold tabular-nums">{{ $releve->valeurFormatee() }}</span>
                                            <span class="block text-[0.65rem] text-brand-500 tabular-nums dark:text-brand-400">
                                                {{ $releve->date_mesure?->format('d/m/Y') ?? '—' }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ol>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <aside class="space-y-6 lg:col-span-1">
            {{-- Repères actuels, issus de l'historique complet --}}
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Repères actuels</h2>
                    <span class="text-xs text-brand-500 dark:text-brand-400">
                        {{ $historique->count() }} libellé(s)
                    </span>
                </div>

                @if ($historique->isEmpty())
                    <x-empty-state
                        icon="fa-solid fa-ruler-combined"
                        title="Aucun repère"
                        message="Les dernières valeurs connues de chaque mesure apparaîtront ici."
                    />
                @else
                    <ul class="divide-y divide-brand-100 dark:divide-white/5">
                        @foreach ($historique as $libelle => $entree)
                            @php $releve = $entree['mesure']; @endphp

                            <li class="flex items-center justify-between gap-3 px-5 py-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold">{{ $libelle }}</p>
                                    <p class="truncate text-xs text-brand-500 dark:text-brand-400">
                                        @if (filled($releve->categorie))
                                            {{ $releve->categorie }} —
                                        @endif
                                        {{ $releve->date_mesure?->format('d/m/Y') ?? '—' }}
                                    </p>
                                </div>

                                <div class="shrink-0 text-right">
                                    <p class="text-sm font-semibold tabular-nums">{{ $releve->valeurFormatee() }}</p>
                                    <p class="text-[0.65rem] text-brand-500 dark:text-brand-400">
                                        {{ count($entree['historique']) }} relevé(s)
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Mesures courantes</h2>
                </div>

                <div class="space-y-4 p-5">
                    <p class="text-xs text-brand-600 dark:text-brand-300">
                        Cliquez sur un intitulé pour ajouter une ligne à remplir dans le formulaire.
                    </p>

                    @foreach ($categoriesCourantes as $categorie)
                        <div>
                            <p class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">
                                {{ $categorie }}
                            </p>
                            <ul class="mt-1.5 flex flex-wrap gap-1.5">
                                @foreach (array_keys(collect($mesuresCourantes)->filter(
                                    fn ($definition) => ($definition['categorie'] ?? null) === $categorie
                                )->all()) as $libelleCourant)
                                    <li>
                                        <button
                                            type="button"
                                            class="cf-btn-secondary cf-btn-sm"
                                            x-on:click="ajouterMesure(@js($libelleCourant), @js($categorie))"
                                        >
                                            <i class="fa-solid fa-plus text-[0.6rem]" aria-hidden="true"></i>
                                            {{ $libelleCourant }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </section>
        </aside>
    </div>
@endsection
