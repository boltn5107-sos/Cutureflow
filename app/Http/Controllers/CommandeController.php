<?php

namespace App\Http\Controllers;

use App\Enums\CommandeStatut;
use App\Http\Requests\CommandeRequest;
use App\Http\Requests\CommandeStatutRequest;
use App\Models\Client;
use App\Models\Commande;
use App\Models\Modele;
use App\Services\NotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommandeController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(Request $request): View
    {
        $atelierId = $request->user()->atelier?->id;

        $commandes = Commande::query()
            ->forAtelier($atelierId)
            ->with(['client', 'modele'])
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->toString()))
            ->when($request->filled('statut'), fn ($q) => $q->statut($request->string('statut')->toString()))
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->integer('client_id')))
            ->when($request->filled('retard'), fn ($q) => $q->enRetard())
            ->latest('date_commande')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $compteurs = Commande::forAtelier($atelierId)
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->all();

        return view('commandes.index', [
            'commandes' => $commandes,
            'filtres' => $request->only('q', 'statut', 'client_id', 'retard'),
            'statutOptions' => CommandeStatut::options(),
            'clients' => Client::forAtelier($atelierId)->orderBy('nom')->get(['id', 'nom']),
            'compteurs' => $compteurs,
            'totalEnRetard' => Commande::forAtelier($atelierId)->enRetard()->count(),
        ]);
    }

    public function create(Request $request): View
    {
        $atelierId = $request->user()->atelier?->id;

        return view('commandes.create', [
            'clients' => Client::forAtelier($atelierId)->orderBy('nom')->get(['id', 'nom', 'telephone']),
            'modeles' => Modele::forAtelier($atelierId)->where('is_active', true)->orderBy('nom')->get(['id', 'nom', 'prix_indicatif']),
            'statutOptions' => CommandeStatut::options(),
            'commande' => null,
            'numeroPropose' => Commande::genererNumero($atelierId),
        ]);
    }

    public function store(CommandeRequest $request): RedirectResponse
    {
        $atelierId = $request->user()->atelier?->id;

        $commande = Commande::create([
            ...$request->safe()->except(['date_livraison_reelle']),
            'atelier_id' => $atelierId,
            'numero' => Commande::genererNumero($atelierId),
            'date_livraison_reelle' => $request->input('date_livraison_reelle'),
            'created_by' => $request->user()->id,
        ]);

        $this->notifications->commandeMiseAJour($commande, 'créée');

        return redirect()
            ->route('commandes.show', $commande)
            ->with('success', "La commande {$commande->numero} a été créée avec succès.");
    }

    public function show(Commande $commande): View
    {
        $this->authorize('view', $commande);

        $commande->load(['client', 'modele.photos', 'paiements.creator', 'mesures', 'rendezVous']);

        return view('commandes.show', [
            'commande' => $commande,
            'statutOptions' => CommandeStatut::options(),
            'prochainPaiement' => $commande->prochainPaiement(),
        ]);
    }

    public function edit(Commande $commande): View
    {
        $this->authorize('update', $commande);

        return view('commandes.edit', [
            'commande' => $commande,
            'clients' => Client::forAtelier($commande->atelier_id)->orderBy('nom')->get(['id', 'nom', 'telephone']),
            'modeles' => Modele::forAtelier($commande->atelier_id)->where('is_active', true)->orderBy('nom')->get(['id', 'nom', 'prix_indicatif']),
            'statutOptions' => CommandeStatut::options(),
        ]);
    }

    public function update(CommandeRequest $request, Commande $commande): RedirectResponse
    {
        $this->authorize('update', $commande);

        $commande->update([
            ...$request->safe()->except(['date_livraison_reelle']),
            'date_livraison_reelle' => $request->input('date_livraison_reelle'),
        ]);

        $this->notifications->commandeMiseAJour($commande->fresh(), 'mise à jour');

        return redirect()
            ->route('commandes.show', $commande)
            ->with('success', "La commande {$commande->numero} a été mise à jour.");
    }

    public function statut(CommandeStatutRequest $request, Commande $commande): RedirectResponse
    {
        $this->authorize('update', $commande);

        $statut = CommandeStatut::from($request->string('statut')->toString());
        $ancien = $commande->statut;

        $commande->update([
            'statut' => $statut,
            'date_livraison_reelle' => $statut === CommandeStatut::Livree
                ? ($commande->date_livraison_reelle ?? now())
                : $request->input('date_livraison_reelle'),
        ]);

        $commande = $commande->fresh();

        if ($statut === CommandeStatut::Prete && $ancien !== CommandeStatut::Prete) {
            $this->notifications->commandePrete($commande);
        }

        if ($statut === CommandeStatut::EnProduction && $ancien === CommandeStatut::EnAttente) {
            $this->notifications->commandeMiseAJour($commande, 'mise en production');
        }

        return back()->with('success', "Statut de la commande {$commande->numero} : {$statut->label()}.");
    }

    public function destroy(Commande $commande): RedirectResponse
    {
        $this->authorize('delete', $commande);

        $numero = $commande->numero;
        $commande->update(['statut' => CommandeStatut::Annulee]);
        $commande->delete();

        return redirect()
            ->route('commandes.index')
            ->with('success', "La commande {$numero} a été annulée.");
    }
}
