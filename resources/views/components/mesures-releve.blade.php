@php
    use App\Models\Client;
    use App\Models\Mesure;
@endphp

@props([
    'date' => Client::DATE_SANS_RELEVE,
    'heure' => null,
    'mesures' => collect(),
    'editable' => false,
    'editee' => null,
    'catalogue' => [],
    'unites' => [],
    'aujourdhui' => '',
])

@php
    $libelleDate = $date === Client::DATE_SANS_RELEVE
        ? 'Sans date'
        : 'du '.\Illuminate\Support\Carbon::parse($date)->format('d/m/Y');

    if ($heure) {
        $libelleDate .= ' à '.$heure;
    }

    $editeeId = $editee instanceof Mesure ? $editee->id : $editee;
@endphp

<section class="cf-card overflow-hidden">
    <div class="cf-card-header">
        <h3 class="font-serif text-lg font-semibold">Relevé {{ $libelleDate }}</h3>
        <span class="text-xs text-brand-500 dark:text-brand-400">
            {{ $mesures->count() }} mesure(s)
        </span>
    </div>

    <ul class="divide-y divide-brand-100 dark:divide-white/5">
        @foreach ($mesures as $mesure)
            <li
                x-data="{ editing: @js($editable && (int) $editeeId === (int) $mesure->id) }"
                x-bind:class="editing ? 'bg-brand-50/60 dark:bg-white/5' : ''"
            >
                <div class="flex items-center gap-3 px-4 py-3 sm:px-5">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-accent dark:bg-white/5">
                        <i class="fa-solid {{ $mesure->icone() }} text-sm" aria-hidden="true"></i>
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="flex flex-wrap items-center gap-2 text-sm font-medium">
                            {{ $mesure->libelle }}

                            @if (filled($mesure->categorie))
                                <span class="cf-badge">{{ $mesure->categorie }}</span>
                            @endif
                        </p>

                        @if (filled($mesure->commentaire))
                            <p class="mt-0.5 text-xs text-brand-500 line-clamp-2 dark:text-brand-400">
                                {{ $mesure->commentaire }}
                            </p>
                        @endif
                    </div>

                    <span class="shrink-0 text-sm font-semibold tabular-nums">
                        {{ $mesure->valeurFormatee() }}
                    </span>

                    @if ($editable)
                        <div class="flex shrink-0 items-center gap-1.5">
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
                    @endif
                </div>

                @if ($editable)
                    <div id="edition-{{ $mesure->id }}" x-show="editing" x-cloak data-edition>
                        <div class="border-t border-brand-100 px-4 pb-4 sm:px-5 dark:border-white/10">
                            <form
                                method="POST"
                                action="{{ route('mesures.update', $mesure) }}"
                                class="grid gap-3 rounded-xl bg-brand-50/60 p-3 sm:grid-cols-6 dark:bg-white/5"
                            >
                                @csrf
                                @method('PUT')

                                <div class="sm:col-span-2">
                                    <label for="edition-{{ $mesure->id }}-code" class="cf-label">Mesure</label>

                                    @php
                                        $entreeCourante = Mesure::entreeParCode($mesure->code)
                                            ?? Mesure::entreeParLibelle($mesure->libelle);
                                    @endphp

                                    <select
                                        id="edition-{{ $mesure->id }}-code"
                                        name="code"
                                        class="cf-select"
                                        aria-invalid="{{ $errors->has('libelle') ? 'true' : 'false' }}"
                                    >
                                        @unless ($entreeCourante)
                                            {{--
                                                Mesure enregistrée avant le catalogue, ou dont
                                                l'intitulé n'y figure pas : elle reste
                                                sélectionnable et reste modifiable en clair.
                                            --}}
                                            <option
                                                value=""
                                                @selected(old('code', '') === '')
                                            >
                                                {{ $mesure->libelle }} (hors catalogue)
                                            </option>
                                        @endunless

                                        @foreach ($catalogue as $zone => $entrees)
                                            <optgroup label="{{ $zone }}">
                                                @foreach ($entrees as $entree)
                                                    <option
                                                        value="{{ $entree['code'] }}"
                                                        @selected(
                                                            (string) old('code', $entreeCourante['code'] ?? '') === $entree['code']
                                                        )
                                                    >
                                                        {{ $entree['libelle'] }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>

                                    @unless ($entreeCourante)
                                        <input
                                            type="hidden"
                                            name="libelle"
                                            value="{{ old('libelle', $mesure->libelle) }}"
                                        >
                                    @endunless
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

                                <div class="flex flex-wrap items-center justify-end gap-2 sm:col-span-6">
                                    <button
                                        type="button"
                                        class="cf-btn-secondary cf-btn-sm"
                                        x-on:click="editing = false"
                                    >
                                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                        Annuler
                                    </button>

                                    <button type="submit" class="cf-btn-primary cf-btn-sm">
                                        <i class="fa-solid fa-check" aria-hidden="true"></i>
                                        Enregistrer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </li>
        @endforeach
    </ul>
</section>