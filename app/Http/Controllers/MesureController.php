<?php

namespace App\Http\Controllers;

use App\Http\Requests\MesureRequest;
use App\Models\Client;
use App\Models\Mesure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MesureController extends Controller
{
    public function index(Request $request, Client $client): View
    {
        $this->authorize('view', $client);

        $mesures = $client->mesures()
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q')->toString();
                $q->where(function ($sub) use ($term) {
                    $sub->where('libelle', 'like', "%{$term}%")
                        ->orWhere('categorie', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('categorie'), fn ($q) => $q->where('categorie', $request->string('categorie')->toString()))
            ->latest('date_mesure')
            ->latest('id')
            ->get();

        /*
         * Une carte par jour de prise : le reçu de l'enregistrement a laissé
         * la place à un carnet permanent, ordonné du relevé le plus récent au
         * plus ancien.
         */
        $relevesParDate = $client->relevesMesures($mesures);

        $historique = $client->mesures()
            ->latest('date_mesure')
            ->latest('id')
            ->get()
            ->groupBy('libelle')
            ->map(fn ($items) => [
                'mesure' => $items->first(),
                'historique' => $items->sortByDesc('date_mesure')->values(),
            ]);

        $mesuresComparees = $client->mesures()
            ->where('date_mesure', '>=', now()->subYear()->toDateString())
            ->orderBy('date_mesure')
            ->orderBy('libelle')
            ->get()
            ->groupBy('libelle')
            ->map(fn ($items) => $items->sortBy('date_mesure')->values())
            ->filter(fn ($items) => $items->count() > 1);

        return view('mesures.index', [
            'client' => $client,
            'mesures' => $mesures,
            'relevesParDate' => $relevesParDate,
            'historique' => $historique,
            'mesuresComparees' => $mesuresComparees,
            'catalogue' => Mesure::catalogue(),
            'unites' => config('coutureflow.unites_mesure', ['cm' => 'Centimètres (cm)']),
            'uniteParDefaut' => config('coutureflow.unite_mesure_par_defaut', 'cm'),
            'filtres' => $request->only('q', 'categorie'),
        ]);
    }

    public function store(MesureRequest $request, Client $client): RedirectResponse
    {
        $uniteParDefaut = config('coutureflow.unite_mesure_par_defaut', 'cm');

        /*
         * Le code et la catégorie ne sont jamais repris du formulaire : ils
         * sont déduits du catalogue à partir du code choisi. Un champ
         * manipulé ne peut donc pas rattacher une mesure à une zone du corps
         * qui n'existe pas, ni à un intitulé arbitraire.
         */
        $enregistrees = DB::transaction(function () use ($client, $request, $uniteParDefaut) {
            $ids = [];

            foreach ($request->validated('mesures') as $ligne) {
                $entree = Mesure::entreeParCode($ligne['code'] ?? null);

                $mesure = $client->mesures()->create([
                    'code' => $entree['code'] ?? null,
                    'libelle' => $entree['libelle'] ?? trim((string) $ligne['libelle']),
                    'categorie' => $entree['categorie'] ?? null,
                    'valeur' => $ligne['valeur'],
                    'unite' => $ligne['unite'] ?: $uniteParDefaut,
                    'date_mesure' => $request->date('date_mesure'),
                    'commentaire' => $request->input('commentaire'),
                    'created_by' => $request->user()->id,
                ]);

                $ids[] = $mesure->id;
            }

            return $ids;
        });

        $total = count($enregistrees);

        /*
         * Le reçu « Relevé enregistré » a été remplacé par la section
         * permanente « Relevé des mesures », ordonnée du jour le plus récent
         * au plus ancien : la prise qui vient d'être validée apparaît donc
         * tout en haut, sans qu'un flash éphémère soit nécessaire.
         */
        return back()->with('success', $total > 1
            ? "{$total} mesures ont été enregistrées."
            : 'La mesure « '.$request->validated('mesures')[0]['libelle'].' » a été enregistrée.');
    }

    public function update(Request $request, Mesure $mesure): RedirectResponse
    {
        $this->authorize('update', $mesure);

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:60'],
            'libelle' => ['nullable', 'string', 'max:120', 'required_without:code'],
            'valeur' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'unite' => ['nullable', 'string', Rule::in(array_keys(config('coutureflow.unites_mesure', ['cm' => ''])))],
            'date_mesure' => ['required', 'date', 'before_or_equal:today', 'after:2000-01-01'],
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ], messages: [
            'libelle.required_without' => 'Indiquez le nom de la mesure.',
            'valeur.required' => 'Indiquez la valeur mesurée.',
            'unite.in' => 'L\'unité sélectionnée n\'est pas reconnue.',
            'date_mesure.before_or_equal' => 'La date de mesure ne peut pas être dans le futur.',
        ], attributes: [
            'libelle' => 'mesure',
            'valeur' => 'valeur',
            'date_mesure' => 'date de mesure',
        ]);

        /*
         * Même règle qu'à la création : le code choisi dans le catalogue
         * entraîne l'intitulé et la zone du corps. Le champ « libelle » reste
         * validé pour les mesures qui n'appartiennent à aucune entrée du
         * catalogue, que le formulaire propose alors en saisie libre.
         */
        $entree = Mesure::entreeParCode($validated['code'] ?? null);

        $mesure->update([
            'code' => $entree['code'] ?? null,
            'libelle' => $entree['libelle'] ?? $validated['libelle'],
            'categorie' => $entree['categorie'] ?? null,
            'valeur' => $validated['valeur'],
            'unite' => $validated['unite'] ?: config('coutureflow.unite_mesure_par_defaut', 'cm'),
            'date_mesure' => $validated['date_mesure'],
            'commentaire' => $validated['commentaire'] ?? null,
        ]);

        return back()->with('success', "La mesure « {$mesure->libelle} » a été mise à jour.");
    }

    public function destroy(Mesure $mesure): RedirectResponse
    {
        $this->authorize('delete', $mesure);

        $libelle = $mesure->libelle;
        $mesure->delete();

        return back()->with('success', "La mesure « {$libelle} » a été supprimée.");
    }
}
