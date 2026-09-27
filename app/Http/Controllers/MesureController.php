<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\MesureRequest;
use App\Models\Client;
use App\Models\Mesure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ->paginate(20)
            ->withQueryString();

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
            'historique' => $historique,
            'mesuresComparees' => $mesuresComparees,
            'mesuresCourantes' => config('coutureflow.mesures_courantes', []),
            'mesuresParDefaut' => config('coutureflow.mesures_par_defaut', []),
            'unites' => config('coutureflow.unites_mesure', ['cm' => 'Centimètres (cm)']),
            'filtres' => $request->only('q', 'categorie'),
        ]);
    }

    public function store(MesureRequest $request, Client $client): RedirectResponse
    {
        $mesuresCourantes = config('coutureflow.mesures_courantes', []);

        $enregistrees = DB::transaction(function () use ($client, $request, $mesuresCourantes) {
            $ids = [];

            foreach ($request->validated('mesures') as $ligne) {
                $libelle = trim((string) $ligne['libelle']);

                $mesure = $client->mesures()->create([
                    'libelle' => $libelle,
                    'valeur' => $ligne['valeur'],
                    'unite' => $ligne['unite'] ?: 'cm',
                    'categorie' => $request->input('categorie')
                        ?: ($mesuresCourantes[$libelle]['categorie'] ?? null),
                    'date_mesure' => $request->date('date_mesure'),
                    'commande_id' => $request->input('commande_id'),
                    'commentaire' => $request->input('commentaire'),
                    'created_by' => $request->user()->id,
                ]);

                $ids[] = $mesure->id;
            }

            return $ids;
        });

        $total = count($enregistrees);

        return back()->with(
            'success',
            $total > 1
                ? "{$total} mesures ont été enregistrées."
                : 'La mesure « '.$request->validated('mesures')[0]['libelle'].' » a été enregistrée.',
        );
    }

    public function update(Request $request, Mesure $mesure): RedirectResponse
    {
        $this->authorize('update', $mesure);

        $validated = $request->validate([
            'libelle' => ['required', 'string', 'max:120'],
            'valeur' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'unite' => ['nullable', 'string', 'max:12'],
            'categorie' => ['nullable', 'string', 'max:60'],
            'date_mesure' => ['required', 'date', 'before_or_equal:today', 'after:2000-01-01'],
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ], messages: [
            'libelle.required' => 'Indiquez le nom de la mesure.',
            'valeur.required' => 'Indiquez la valeur mesurée.',
            'date_mesure.before_or_equal' => 'La date de mesure ne peut pas être dans le futur.',
        ], attributes: [
            'libelle' => 'mesure',
            'valeur' => 'valeur',
            'date_mesure' => 'date de mesure',
        ]);

        $mesure->update($validated);

        return back()->with('success', "La mesure « {$validated['libelle']} » a été mise à jour.");
    }

    public function destroy(Mesure $mesure): RedirectResponse
    {
        $this->authorize('delete', $mesure);

        $libelle = $mesure->libelle;
        $mesure->delete();

        return back()->with('success', "La mesure « {$libelle} » a été supprimée.");
    }
}
