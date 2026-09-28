<?php

namespace App\Http\Controllers;

use App\Enums\RendezVousType;
use App\Http\Requests\RendezVousRequest;
use App\Models\Client;
use App\Models\Commande;
use App\Models\RendezVous;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PlanningController extends Controller
{
    public function index(Request $request): View
    {
        $atelierId = $request->user()->atelier?->id;

        $mois = $request->filled('mois')
            ? Carbon::parse($request->string('mois').'-01')
            : Carbon::now()->startOfMonth();

        $debut = $mois->copy()->startOfMonth();
        $fin = $mois->copy()->endOfMonth();

        $evenements = RendezVous::forAtelier($atelierId)
            ->with(['client', 'commande'])
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->toString()))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')->toString()))
            ->whereBetween('date_debut', [$debut, $fin])
            ->orderBy('date_debut')
            ->orderBy('heure_debut')
            ->get();

        $parJour = $evenements
            ->groupBy(fn (RendezVous $rdv) => $rdv->date_debut->format('Y-m-d'))
            ->map(fn ($items) => $items->sortBy(fn (RendezVous $r) => $r->heure_debut ?? '00:00')->values());

        $semaine = $this->grilleSemaine($parJour, $debut);

        $commandesALivrer = Commande::forAtelier($atelierId)
            ->ouvertes()
            ->whereNotNull('date_livraison_prevue')
            ->whereBetween('date_livraison_prevue', [$debut, $fin])
            ->with('client')
            ->orderBy('date_livraison_prevue')
            ->get()
            ->groupBy(fn (Commande $c) => $c->date_livraison_prevue->format('Y-m-d'));

        return view('planning.index', [
            'evenements' => $evenements,
            'parJour' => $parJour,
            'grille' => $semaine,
            'commandesALivrer' => $commandesALivrer,
            'mois' => $mois,
            'moisPrecedent' => $mois->copy()->subMonthNoOverflow()->format('Y-m'),
            'moisSuivant' => $mois->copy()->addMonthNoOverflow()->format('Y-m'),
            'typeOptions' => RendezVousType::options(),
            'filtres' => $request->only('q', 'type', 'mois'),
        ]);
    }

    public function create(Request $request): View
    {
        $atelierId = $request->user()->atelier?->id;

        return view('planning.create', [
            'clients' => Client::forAtelier($atelierId)->orderBy('nom')->get(['id', 'nom', 'telephone']),
            'commandes' => Commande::forAtelier($atelierId)->ouvertes()->with('client')->orderByDesc('date_commande')->get(['id', 'numero', 'client_id']),
            'typeOptions' => RendezVousType::options(),
            'dateParDefaut' => $request->input('date', today()->toDateString()),
        ]);
    }

    public function store(RendezVousRequest $request): RedirectResponse
    {
        $rendezVous = RendezVous::create([
            ...$request->safe()->all(),
            'atelier_id' => $request->user()->atelier?->id,
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('planning.index', ['mois' => $rendezVous->date_debut->format('Y-m')])
            ->with('success', "L'événement « {$rendezVous->titre} » a été ajouté au planning.");
    }

    public function edit(RendezVous $rendezVous): View
    {
        $this->authorize('update', $rendezVous);

        return view('planning.edit', [
            'rendezVous' => $rendezVous,
            'clients' => Client::forAtelier($rendezVous->atelier_id)->orderBy('nom')->get(['id', 'nom', 'telephone']),
            'commandes' => Commande::forAtelier($rendezVous->atelier_id)->ouvertes()->with('client')->get(['id', 'numero', 'client_id']),
            'typeOptions' => RendezVousType::options(),
        ]);
    }

    public function update(RendezVousRequest $request, RendezVous $rendezVous): RedirectResponse
    {
        $this->authorize('update', $rendezVous);

        $rendezVous->update($request->safe()->all());

        return redirect()
            ->route('planning.index', ['mois' => $rendezVous->date_debut->format('Y-m')])
            ->with('success', "L'événement « {$rendezVous->titre} » a été mis à jour.");
    }

    public function destroy(RendezVous $rendezVous): RedirectResponse
    {
        $this->authorize('delete', $rendezVous);

        $titre = $rendezVous->titre;
        $mois = $rendezVous->date_debut->format('Y-m');
        $rendezVous->delete();

        return redirect()
            ->route('planning.index', ['mois' => $mois])
            ->with('success', "L'événement « {$titre} » a été supprimé.");
    }

    /**
     * Grille calendrier du mois : semaines de 7 jours, complétée à 6 semaines.
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function grilleSemaine(mixed $parJour, Carbon $debut): array
    {
        $cursor = $debut->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $fin = $debut->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $grille = [];

        while ($cursor->lte($fin)) {
            $semaine = [];

            for ($i = 0; $i < 7; $i++) {
                $cle = $cursor->format('Y-m-d');
                $semaine[] = [
                    'date' => $cursor->copy(),
                    'cle' => $cle,
                    'dansMois' => $cursor->month === $debut->month,
                    'aujourdhui' => $cursor->isToday(),
                    'weekend' => in_array($cursor->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY], true),
                    'evenements' => $parJour[$cle] ?? collect(),
                ];
                $cursor->addDay();
            }

            $grille[] = $semaine;
        }

        return $grille;
    }
}
