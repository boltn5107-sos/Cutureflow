<?php

namespace App\Http\Controllers;

use App\Enums\CommandeStatut;
use App\Enums\DepenseCategorie;
use App\Enums\PaiementMethode;
use App\Enums\PaiementType;
use App\Http\Requests\PaiementRequest;
use App\Models\Client;
use App\Models\Commande;
use App\Models\Paiement;
use App\Services\DashboardService;
use App\Services\NotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CaisseController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): View
    {
        $atelierId = $request->user()->atelier?->id;
        $service = DashboardService::for($request->user());

        $from = $request->filled('from') ? Carbon::parse($request->string('from')) : Carbon::now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->string('to')) : Carbon::now()->endOfMonth();

        $paiements = Paiement::query()
            ->forAtelier($atelierId)
            ->with(['commande', 'client'])
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->toString()))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')->toString()))
            ->when($request->filled('methode'), fn ($q) => $q->where('methode', $request->string('methode')->toString()))
            ->when($request->filled('commande_id'), fn ($q) => $q->where('commande_id', $request->integer('commande_id')))
            ->whereBetween('date_paiement', [$from, $to])
            ->orderByDesc('date_paiement')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('caisse.index', [
            'paiements' => $paiements,
            'indicateurs' => $service->indicateurs($from->toDateString(), $to->toDateString()),
            'repartitionTypes' => $service->repartitionPaiements($from->toDateString(), $to->toDateString()),
            'repartitionMethodes' => $service->repartitionMethodes($from->toDateString(), $to->toDateString()),
            'evolution' => $service->evolutionFinanciere(),
            'commandesOuvertes' => Commande::forAtelier($atelierId)->ouvertes()->with('client')->orderBy('date_livraison_prevue')->get(),
            'typeOptions' => PaiementType::options(),
            'methodeOptions' => PaiementMethode::options(),
            'filtres' => $request->only('q', 'type', 'methode', 'commande_id', 'from', 'to'),
        ]);
    }

    public function create(Request $request): View
    {
        $atelierId = $request->user()->atelier?->id;

        return view('caisse.paiements.create', [
            'commandes' => Commande::forAtelier($atelierId)
                ->ouvertes()
                ->with('client')
                ->orderBy('date_livraison_prevue')
                ->get(),
            'clients' => Client::forAtelier($atelierId)->orderBy('nom')->get(['id', 'nom']),
            'typeOptions' => PaiementType::options(),
            'methodeOptions' => PaiementMethode::options(),
            'commandePreselectionnee' => $request->integer('commande_id') ?: null,
        ]);
    }

    public function store(PaiementRequest $request): RedirectResponse
    {
        $paiement = Paiement::create([
            ...$request->safe()->all(),
            'atelier_id' => $request->user()->atelier?->id,
            'created_by' => $request->user()->id,
        ]);

        if ($paiement->commande) {
            $this->notifications->paiementRecu($paiement->commande, (float) $paiement->montant);
        }

        return redirect()
            ->route('caisse.index')
            ->with('success', 'Paiement de '.number_format((float) $paiement->montant, 0, ',', ' ').' '.config('coutureflow.currency').' enregistré.');
    }

    public function show(Paiement $paiement): View
    {
        $this->authorize('view', $paiement);

        $paiement->load(['commande.client', 'client', 'creator']);

        return view('caisse.paiements.show', [
            'paiement' => $paiement,
        ]);
    }

    public function edit(Paiement $paiement): View
    {
        $this->authorize('update', $paiement);

        return view('caisse.paiements.edit', [
            'paiement' => $paiement,
            'commandes' => Commande::forAtelier($paiement->atelier_id)->with('client')->orderBy('date_commande')->get(),
            'clients' => Client::forAtelier($paiement->atelier_id)->orderBy('nom')->get(['id', 'nom']),
            'typeOptions' => PaiementType::options(),
            'methodeOptions' => PaiementMethode::options(),
        ]);
    }

    public function update(PaiementRequest $request, Paiement $paiement): RedirectResponse
    {
        $paiement->update($request->safe()->all());

        return redirect()
            ->route('caisse.paiements.show', $paiement)
            ->with('success', 'Le paiement a été mis à jour.');
    }

    public function destroy(Paiement $paiement): RedirectResponse
    {
        $this->authorize('delete', $paiement);

        $montant = number_format((float) $paiement->montant, 0, ',', ' ');
        $paiement->delete();

        return redirect()
            ->route('caisse.index')
            ->with('success', "Le paiement de {$montant} ".config('coutureflow.currency').' a été supprimé.');
    }
}
