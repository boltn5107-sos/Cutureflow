@php
    use App\Models\Client;
    use Illuminate\Support\Carbon;
@endphp

@props([
    'releves' => collect(),
    'total' => 0,
    'client',
])

{{--
    L'historique des mensurations, relaté séance après séance : une carte par
    jour de prise, du relevé le plus récent au plus ancien. La fiche du client
    devient un vrai carnet de mesures, au lieu d'un instantané de la dernière
    valeur de chaque champ.
--}}
<section class="cf-card overflow-hidden" data-mesures-resume>
    <div class="cf-card-header">
        <h2 class="font-serif text-lg font-semibold">Résumé des mesures</h2>

        <div class="flex items-center gap-3">
            <span class="text-xs text-brand-500 dark:text-brand-400">
                <span class="font-semibold tabular-nums">{{ number_format($total, 0, ',', ' ') }}</span>
                mesure(s) ·
                <span class="font-semibold tabular-nums">{{ $releves->count() }}</span>
                relevé(s)
            </span>

            <a href="{{ route('mesures.index', $client) }}" class="cf-link text-xs">
                Tout gérer
                <i class="fa-solid fa-arrow-right ml-1" aria-hidden="true"></i>
            </a>
        </div>
    </div>

    @if ($releves->isEmpty())
        <x-empty-state
            icon="fa-solid fa-ruler-combined"
            title="Aucune mesure"
            message="Enregistrez un relevé pour ce client : chaque prise s'affichera ici, carte par carte, dans l'ordre de son histoire."
            :action="route('mesures.index', $client)"
            action-label="Prendre des mesures"
        />
    @else
        <div class="space-y-3 p-3 sm:p-4">
            @foreach ($releves as $date => $mesures)
                <article class="overflow-hidden rounded-xl border border-brand-100 dark:border-white/10">
                    @php
                        $libelleDate = $date === Client::DATE_SANS_RELEVE
                            ? 'Relevé sans date'
                            : 'Relevé du '.Carbon::parse($date)->format('d/m/Y');
                    @endphp

                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-brand-100 bg-brand-50/50 px-4 py-2.5 dark:border-white/10 dark:bg-white/5">
                        <h3 class="flex items-center gap-2 text-sm font-semibold">
                            <i class="fa-solid fa-calendar-day text-xs text-brand-400" aria-hidden="true"></i>
                            {{ $libelleDate }}
                        </h3>
                        <span class="text-xs text-brand-500 dark:text-brand-400">
                            {{ $mesures->count() }} mesure(s)
                        </span>
                    </div>

                    <ul class="divide-y divide-brand-100 dark:divide-white/5">
                        @foreach ($mesures as $mesure)
                            <li class="flex items-center gap-3 px-4 py-2.5">
                                <span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-brand-50 text-brand-accent dark:bg-white/5">
                                    <i class="fa-solid {{ $mesure->icone() }} text-xs" aria-hidden="true"></i>
                                </span>

                                <span class="min-w-0 flex-1 truncate text-sm text-brand-700 dark:text-brand-200">
                                    {{ $mesure->libelle }}
                                </span>

                                <span class="shrink-0 text-sm font-semibold tabular-nums">
                                    {{ $mesure->valeurFormatee() }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </article>
            @endforeach
        </div>
    @endif
</section>