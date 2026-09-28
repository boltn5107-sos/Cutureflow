@extends('layouts.app')

@section('title', 'Mesures de '.$client->nom)
@section('header_title', 'Mesures de '.$client->nom)
@section('header_subtitle', number_format($mesures->count(), 0, ',', ' ').' mesure(s) enregistrée(s)')

@section('content')
    @php
        use App\Models\Mesure;

        $aujourdhui = now()->toDateString();

        /*
         * Le catalogue est groupé par zone du corps pour l'affichage, et
         * aplati par code pour la logique Alpine : un code est la clé qui relie
         * une icône cliquée à la ligne réellement enregistrée.
         */
        $catalogueParCode = [];

        foreach ($catalogue as $zone => $entrees) {
            foreach ($entrees as $entree) {
                $catalogueParCode[$entree['code']] = $entree + ['zone' => $zone];
            }
        }

        /*
         * Le filtre par zone propose les zones du catalogue, complétées par
         * celles présentes dans l'historique : les relevés saisis avant la
         * création du catalogue portent encore des libellés d'ancienne
         * catégorie (« Manches », « Bas »), qui doivent rester filtrables.
         */
        $categoriesFiltre = collect(array_keys($catalogue))
            ->merge($historique->map(fn ($entree) => $entree['mesure']->categorie)->filter())
            ->unique()
            ->values()
            ->all();

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
        /*
         * Après un échec de validation, l'utilisateur retrouve exactement les
         * icônes qu'il avait sélectionnées, y compris celles laissées sans
         * valeur. Les lignes sont rattachées au catalogue par leur code, ou à
         * défaut par leur intitulé, ce qui préserve aussi les mesures issues
         * d'une version antérieure du formulaire.
         */
        $lignesRenvoyees = old('mesures');
        $lignesInitiales = collect(is_array($lignesRenvoyees) ? $lignesRenvoyees : [])
            ->filter(fn ($ligne) => is_array($ligne) && (
                filled($ligne['code'] ?? null)
                || filled($ligne['libelle'] ?? null)
                || filled($ligne['valeur'] ?? null)
            ))
            ->map(function ($ligne) use ($uniteParDefaut) {
                $entree = Mesure::entreeParCode($ligne['code'] ?? null)
                    ?? Mesure::entreeParLibelle($ligne['libelle'] ?? null);

                return [
                    'code' => $entree['code'] ?? null,
                    'libelle' => $entree['libelle'] ?? ($ligne['libelle'] ?? ''),
                    'valeur' => $ligne['valeur'] ?? '',
                    'unite' => $ligne['unite'] ?? $uniteParDefaut,
                ];
            })
            ->values()
            ->all();
    @endphp

    <div
        class="grid gap-6 lg:grid-cols-3"
        x-data="mesureForm(@js($catalogueParCode), @js($lignesInitiales), @js($uniteParDefaut))"
    >
        <div class="space-y-6 lg:col-span-2">
            {{-- Formulaire d'ajout --}}
            <section class="cf-card overflow-hidden">
                <div class="cf-card-header">
                    <h2 class="font-serif text-lg font-semibold">Ajouter des mesures</h2>
                    <span class="text-xs text-brand-500 dark:text-brand-400">
                        Touchez les mesures à relever
                    </span>
                </div>

                {{--
                    Le bouton d'enregistrement est dans le formulaire, sous le
                    commentaire : c'est là que l'on termine sa saisie, on n'a
                    pas à chercher une barre d'action ailleurs dans la page.
                --}}
                <form
                    id="form-mesures"
                    method="POST"
                    action="{{ route('mesures.store', $client) }}"
                >
                    @csrf

                    <div class="space-y-5 p-5 sm:p-6">
                        <p class="text-sm text-brand-700 dark:text-brand-200">
                            Choisissez les mesures que vous allez prendre. Aucune n'est obligatoire :
                            cliquez sur une icône pour l'ajouter, cliquez encore pour la retirer.
                        </p>

                        {{--
                            Palette d'icônes : une grille plate, sans les intitulés
                            de zone qui alourdissaient la lecture sur téléphone. Le
                            regroupement par zone du corps est détaillé dans
                            l'encart « Catalogue des mesures », à droite.

                            L'ancre permet au bouton « Autre mesure » de remonter
                            ici. Le défilement s'arrête à scroll-mt-6 pour que la
                            première rangée ne colle pas au bord de l'écran.
                        --}}
                        <div
                            id="palette-mesures"
                            class="grid scroll-mt-6 grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-4"
                        >
                            @foreach ($catalogueParCode as $code => $entree)
                                {{--
                                    Les couleurs de la tuile sont entièrement pilotées par
                                    Alpine : la classe statique ne porte que la mise en page.
                                    Mélanger les deux ferait dépendre l'état retenu de
                                    l'ordre d'émission des utilitaires Tailwind.

                                    Le clic passe par basculer(), qui ajoute la mesure puis
                                    amène le curseur sur son champ : le tailleur n'a pas
                                    à faire défiler la page pour aller le remplir.
                                --}}
                                <button
                                    type="button"
                                    class="flex min-h-14 flex-col items-center justify-center gap-1 rounded-lg border px-1.5 py-2 text-center transition focus-visible:ring-2 focus-visible:ring-brand-accent focus-visible:ring-offset-1 focus-visible:outline-none dark:focus-visible:ring-offset-brand-dark"
                                    x-on:click="basculer(@js($code))"
                                    x-bind:aria-pressed="contient(@js($code)) ? 'true' : 'false'"
                                    x-bind:class="contient(@js($code))
                                        ? 'border-brand-accent bg-brand-accent/10'
                                        : 'border-brand-200 bg-white hover:border-brand-400 hover:bg-brand-50 dark:border-white/10 dark:bg-white/5 dark:hover:border-brand-500/60'"
                                    title="{{ $entree['zone'] }} — {{ $entree['libelle'] }}"
                                >
                                    <i
                                        class="fa-solid {{ $entree['icone'] }} text-base"
                                        :class="contient(@js($code)) ? 'text-brand-accent' : 'text-brand-500 dark:text-brand-300'"
                                        aria-hidden="true"
                                    ></i>

                                    {{--
                                        Les intitulés les plus longs passent sur deux
                                        lignes plutôt que d'étirer la tuile : c'est
                                        ce qui gardait la grille large sur téléphone.
                                    --}}
                                    <span class="text-[0.65rem] leading-tight font-medium text-brand-800 dark:text-brand-100">
                                        {{ $entree['libelle'] }}
                                    </span>
                                </button>
                            @endforeach
                        </div>

                        {{-- Mesures sélectionnées --}}
                        <div class="border-t border-brand-200/70 pt-4 dark:border-white/10">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold">
                                    Mesures sélectionnées
                                    <span
                                        class="ml-1 text-brand-500 tabular-nums dark:text-brand-400"
                                        x-text="nombre"
                                        x-cloak
                                    ></span>
                                </h3>

                                <p
                                    class="text-xs text-brand-500 dark:text-brand-400"
                                    x-show="nombre === 0"
                                    x-cloak
                                >
                                    Aucune mesure pour l'instant.
                                </p>
                            </div>

                            <div
                                class="mt-3 space-y-2"
                                x-show="nombre > 0"
                                x-cloak
                            >
                                <template x-for="(ligne, index) in lignes" :key="ligne.code || index">
                                    <div class="rounded-xl border border-brand-200 bg-brand-50/40 p-3 dark:border-white/10 dark:bg-white/[0.03]">
                                        {{-- Ligne 1 : l'icône, le nom de la mesure, et son retrait --}}
                                        <div class="flex items-center gap-3">
                                            <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-white text-brand-accent dark:bg-white/10">
                                                <i class="fa-solid" :class="icone(ligne.code)" aria-hidden="true"></i>
                                            </span>

                                            <div class="min-w-0 flex-1">
                                                <label
                                                    class="block truncate text-sm font-semibold"
                                                    :for="'valeur-' + index"
                                                    x-text="ligne.libelle"
                                                ></label>

                                                <template x-if="!ligne.code">
                                                    <span class="text-xs text-brand-500 dark:text-brand-400">
                                                        Mesure personnalisée
                                                    </span>
                                                </template>
                                            </div>

                                            <button
                                                type="button"
                                                class="cf-btn-ghost cf-btn-sm shrink-0 text-red-600 dark:text-red-400"
                                                x-on:click="basculer(ligne.code)"
                                                :aria-label="'Retirer ' + ligne.libelle"
                                            >
                                                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                                <span class="sr-only sm:not-sr-only">Retirer</span>
                                            </button>
                                        </div>

                                        {{-- Ligne 2 : la valeur relevée et son unité --}}
                                        <div class="mt-3 flex items-end gap-2">
                                            <div class="min-w-0 flex-1">
                                                <label class="sr-only" :for="'valeur-' + index">
                                                    Valeur de <span x-text="ligne.libelle"></span>
                                                </label>

                                                <input
                                                    :id="'valeur-' + index"
                                                    class="cf-input"
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    max="9999.99"
                                                    inputmode="decimal"
                                                    required
                                                    :name="'mesures[' + index + '][valeur]'"
                                                    x-model="ligne.valeur"
                                                    placeholder="Ex. 92"
                                                    :aria-label="'Valeur de ' + ligne.libelle"
                                                >
                                            </div>

                                            <div class="w-28 shrink-0">
                                                <label class="sr-only" :for="'unite-' + index">
                                                    Unité de <span x-text="ligne.libelle"></span>
                                                </label>

                                                <select
                                                    :id="'unite-' + index"
                                                    class="cf-select"
                                                    :name="'mesures[' + index + '][unite]'"
                                                    x-model="ligne.unite"
                                                    :aria-label="'Unité de ' + ligne.libelle"
                                                >
                                                    @foreach ($unites as $valeurUnite => $libelleUnite)
                                                        <option value="{{ $valeurUnite }}">{{ $libelleUnite }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Le libellé et le code sont imposés par le catalogue --}}
                                        <input type="hidden" :name="'mesures[' + index + '][libelle]'" :value="ligne.libelle">
                                        <input type="hidden" :name="'mesures[' + index + '][code]'" :value="ligne.code">
                                    </div>
                                </template>
                            </div>

                            {{--
                                Sens inverse du défilement : une fois la valeur
                                saisie, ce bouton ramène à la palette sans avoir à
                                faire défiler toute la page vers le haut.
                            --}}
                            <button
                                type="button"
                                class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-brand-300 px-3 py-3 text-sm font-medium text-brand-700 transition hover:border-brand-accent hover:bg-brand-accent/5 hover:text-brand-accent focus-visible:ring-2 focus-visible:ring-brand-accent focus-visible:ring-offset-1 focus-visible:outline-none dark:border-white/15 dark:text-brand-200 dark:hover:border-brand-accent dark:focus-visible:ring-offset-brand-dark"
                                x-on:click="allerAuxIcones()"
                                x-show="nombre > 0"
                                x-cloak
                            >
                                <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i>
                                Autre mesure
                            </button>
                        </div>

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

                            {{-- Barre d'action : elle suit le dernier champ saisi. --}}
                            <div class="flex flex-wrap items-center justify-center gap-2 sm:col-span-6">
                                <p
                                    class="mr-auto text-xs text-brand-600 dark:text-brand-300"
                                    x-show="nombre > 0"
                                    x-cloak
                                >
                                    <span class="font-semibold tabular-nums" x-text="nombre"></span>
                                    <span x-text="nombre > 1 ? 'mesure(s) à enregistrer' : 'mesure à enregistrer'"></span>
                                </p>

                                <p
                                    class="mr-auto text-xs text-brand-500 dark:text-brand-400"
                                    x-show="nombre === 0"
                                    x-cloak
                                >
                                    Aucune mesure sélectionnée. Touchez une icône pour commencer.
                                </p>

                                 <button
                                    type="submit"
                                    class="cf-btn-primary w-full sm:w-auto mb-2 sm:mb-0 cf-btn-sm"
                                    x-bind:disabled="nombre === 0"
                                >
                                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                                    <span x-text="nombre > 1 ? 'Enregistrer les ' + nombre + ' mesures' : 'Enregistrer la mesure'"></span>
                                </button>
                                
                                <button
                                    type="button"
                                    class="cf-btn-secondary w-full sm:w-auto mb-2 sm:mb-0 cf-btn-sm"
                                    x-on:click="vider()"
                                    x-show="nombre > 0"
                                    x-cloak
                                >
                                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                    Annuler
                                </button>

                               
                            </div>
                        </div>
                    </div>
                </form>
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
                        :options="$categoriesFiltre"
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

{{-- Relevé des mesures : une carte par jour de prise, façon carnet --}}
            <section class="space-y-6" aria-labelledby="releves-titre">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 id="releves-titre" class="font-serif text-lg font-semibold">Relevé des mesures</h2>
                        <p class="text-xs text-brand-500 dark:text-brand-400">
                            Une carte par jour de prise, du plus récent au plus ancien.
                        </p>
                    </div>

                    <span class="text-xs text-brand-500 dark:text-brand-400">
                        <span class="font-semibold tabular-nums">{{ number_format($mesures->count(), 0, ',', ' ') }}</span>
                        mesure(s)
                    </span>
                </div>

                @if ($editionOuverte)
                    <div
                        class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3.5 dark:border-red-500/30 dark:bg-red-500/10"
                        role="alert"
                    >
                        <i class="fa-solid fa-triangle-exclamation mt-0.5 text-sm text-red-700 dark:text-red-300" aria-hidden="true"></i>
                        <div class="flex-1 text-sm text-red-700 dark:text-red-300">
                            <p class="font-semibold">La mesure n'a pas pu être enregistrée</p>
                            <ul class="mt-1.5 space-y-1 text-xs">
                                @error('libelle') <li>{{ $message }}</li> @enderror
                                @error('valeur') <li>{{ $message }}</li> @enderror
                                @error('unite') <li>{{ $message }}</li> @enderror
                                @error('date_mesure') <li>{{ $message }}</li> @enderror
                                @error('commentaire') <li>{{ $message }}</li> @enderror
                            </ul>
                        </div>
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
                   @foreach ($relevesParDate as $cle => $mesuresDuJour)
                    @php
                        [$date, $heure] = str_contains($cle, '|') 
                            ? explode('|', $cle) 
                            : [$cle, null];
                    @endphp

                    <x-mesures-releve
                        :date="$date"
                        :heure="$heure"
                        :mesures="$mesuresDuJour"
                        :catalogue="$catalogue"
                        :unites="$unites"
                        :aujourdhui="$aujourdhui"
                    />
                @endforeach
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
            {{--
                La carte « Enregistrer » qui vivait ici a été retirée : le
                bouton est désormais dans le formulaire, sous le commentaire.
                Il est difficile d'avoir à balayer la page pour trouver où l'on
                valide sa saisie. Cette colonne garde les repères de
                l'historique et le catalogue des mesures.
            --}}

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
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-accent dark:bg-white/5">
                                        <i class="fa-solid {{ $releve->icone() }} text-sm" aria-hidden="true"></i>
                                    </span>

                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold">{{ $libelle }}</p>
                                        <p class="truncate text-xs text-brand-500 dark:text-brand-400">
                                            @if (filled($releve->categorie))
                                                {{ $releve->categorie }} —
                                            @endif
                                            {{ $releve->date_mesure?->format('d/m/Y') ?? '—' }}
                                        </p>
                                    </div>
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

            {{-- Rappel des mesures du catalogue --}}
            {{--
                Le regroupement par zone du corps, retiré de la palette pour
                l'alléger, est détaillé ici. L'encart est replié par défaut et
                s'ouvre au clic : la palette reste l'outil principal, la
                légende reste consultable quand on cherche une mesure.
            --}}
            <section class="cf-card overflow-hidden" x-data="{ ouvert: false }">
                <h2>
                    <button
                        type="button"
                        class="cf-card-header w-full cursor-pointer appearance-none text-left hover:bg-brand-50/60 dark:hover:bg-white/5"
                        x-on:click="ouvert = !ouvert"
                        aria-controls="catalogue-mesures"
                        x-bind:aria-expanded="ouvert ? 'true' : 'false'"
                    >
                        <span class="font-serif text-lg font-semibold">Catalogue des mesures</span>

                        <span class="flex items-center gap-2 text-xs text-brand-500 dark:text-brand-400">
                            <span class="tabular-nums">
                                <span class="font-semibold">{{ count($catalogueParCode) }}</span>
                                mesures ·
                                <span class="font-semibold">{{ count($catalogue) }}</span>
                                zones
                            </span>

                            <i
                                class="fa-solid fa-chevron-down text-[0.65rem] transition-transform"
                                x-bind:class="ouvert ? 'rotate-180' : ''"
                                aria-hidden="true"
                            ></i>
                        </span>
                    </button>
                </h2>

                <div id="catalogue-mesures" x-show="ouvert" x-cloak>
                    <div class="space-y-3 border-t border-brand-200/70 p-5 dark:border-white/10">
                        <p class="text-xs text-brand-600 dark:text-brand-300">
                            Toutes ces mesures sont facultatives. Touchez une icône, ici ou dans la
                            palette, pour l'ajouter à votre relevé et aller droit à son champ ; touchez
                            encore pour la retirer.
                        </p>

                        @foreach ($catalogue as $zone => $entrees)
                            <div>
                                <p class="text-xs font-medium tracking-wide text-brand-600 uppercase dark:text-brand-300">
                                    {{ $zone }}
                                </p>

                                <ul class="mt-1.5 flex flex-wrap gap-1.5">
                                    @foreach ($entrees as $entree)
                                        {{-- Même comportement que la palette : basculer() déplace déjà le curseur sur le champ. --}}
                                        <li>
                                            <button
                                                type="button"
                                                class="inline-flex min-h-8 items-center gap-1.5 rounded-lg border px-2 py-1 text-xs transition focus-visible:ring-2 focus-visible:ring-brand-accent focus-visible:ring-offset-1 focus-visible:outline-none dark:focus-visible:ring-offset-brand-dark"
                                                x-on:click="basculer(@js($entree['code']))"
                                                x-bind:aria-pressed="contient(@js($entree['code'])) ? 'true' : 'false'"
                                                x-bind:class="contient(@js($entree['code']))
                                                    ? 'border-brand-accent bg-brand-accent/10 font-semibold text-brand-900 dark:text-brand-50'
                                                    : 'border-brand-200 text-brand-700 hover:border-brand-accent hover:bg-brand-accent/5 dark:border-white/10 dark:text-brand-200'"
                                            >
                                                <i
                                                    class="fa-solid {{ $entree['icone'] }}"
                                                    :class="contient(@js($entree['code'])) ? 'text-brand-accent' : 'text-brand-500 dark:text-brand-300'"
                                                    aria-hidden="true"
                                                ></i>
                                                {{ $entree['libelle'] }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </aside>
    </div>
@endsection
