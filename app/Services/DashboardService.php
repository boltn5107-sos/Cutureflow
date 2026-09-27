<?php

namespace App\Services;

use App\Enums\CommandeStatut;
use App\Enums\DepenseCategorie;
use App\Enums\PaiementType;
use App\Models\Client;
use App\Models\Commande;
use App\Models\Depense;
use App\Models\Paiement;
use App\Models\RendezVous;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Agrégation des indicateurs de l'atelier (dashboard + caisse).
 * Toutes les requêtes sont filtrées par atelier_id : aucun mélange possible.
 */
class DashboardService
{
    public function __construct(private readonly ?int $atelierId = null) {}

    public static function for(User $user): self
    {
        return new self($user->atelier?->id);
    }

    public function atelierId(): ?int
    {
        return $this->atelierId;
    }

    /**
     * @return array<string, mixed>
     */
    public function indicateurs(?string $from = null, ?string $to = null): array
    {
        $atelierId = $this->atelierId;
        $from = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfMonth();
        $to = $to ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfMonth();

        $commandes = Commande::query()
            ->forAtelier($atelierId)
            ->whereBetween('date_commande', [$from, $to])
            ->where('statut', '!=', CommandeStatut::Annulee->value);

        $recettes = Paiement::query()
            ->forAtelier($atelierId)
            ->recettes()
            ->whereBetween('date_paiement', [$from, $to]);

        $depenses = Depense::query()
            ->forAtelier($atelierId)
            ->whereBetween('date_depense', [$from, $to]);

        $recettesTotal = (float) (clone $recettes)->sum('montant');
        $depensesTotal = (float) (clone $depenses)->sum('montant');

        $soldeDu = (float) Commande::query()
            ->forAtelier($atelierId)
            ->ouvertes()
            ->sum('solde');

        $avancesDu = (float) Commande::query()
            ->forAtelier($atelierId)
            ->ouvertes()
            ->sum('avance');

        return [
            'from' => $from,
            'to' => $to,
            'chiffre_affaires' => $recettesTotal,
            'recettes' => $recettesTotal,
            'depenses' => $depensesTotal,
            'benefice' => round($recettesTotal - $depensesTotal, 2),
            'commandes_total' => (clone $commandes)->count(),
            'commandes_en_cours' => Commande::forAtelier($atelierId)->where('statut', CommandeStatut::EnProduction->value)->count(),
            'commandes_en_attente' => Commande::forAtelier($atelierId)->where('statut', CommandeStatut::EnAttente->value)->count(),
            'commandes_pretes' => Commande::forAtelier($atelierId)->where('statut', CommandeStatut::Prete->value)->count(),
            'commandes_livrees' => Commande::forAtelier($atelierId)->where('statut', CommandeStatut::Livree->value)->count(),
            'commandes_en_retard' => Commande::forAtelier($atelierId)->enRetard()->count(),
            'clients_total' => Client::forAtelier($atelierId)->count(),
            'solde_total' => $soldeDu,
            'avances_total' => $avancesDu,
            'paiements_en_attente' => $soldeDu,
        ];
    }

    /**
     * @return Collection<int, Commande>
     */
    public function dernieresCommandes(int $limit = 6): Collection
    {
        return Commande::forAtelier($this->atelierId)
            ->with('client')
            ->latest('date_commande')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Commande>
     */
    public function commandesALivrerProchainement(int $limit = 6): Collection
    {
        return Commande::forAtelier($this->atelierId)
            ->with('client')
            ->ouvertes()
            ->whereNotNull('date_livraison_prevue')
            ->orderBy('date_livraison_prevue')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Commande>
     */
    public function commandesEnRetard(int $limit = 6): Collection
    {
        return Commande::forAtelier($this->atelierId)
            ->with('client')
            ->enRetard()
            ->orderBy('date_livraison_prevue')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, RendezVous>
     */
    public function prochainsRendezVous(int $limit = 6): Collection
    {
        return RendezVous::forAtelier($this->atelierId)
            ->with(['client', 'commande'])
            ->whereDate('date_debut', '>=', today())
            ->orderBy('date_debut')
            ->orderBy('heure_debut')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array<int, array{label: string, depense: float, recette: float}>
     */
    public function evolutionFinanciere(int $months = 6): array
    {
        $atelierId = $this->atelierId;
        $cursor = Carbon::now()->startOfMonth()->subMonths($months - 1);
        $series = [];

        for ($i = 0; $i < $months; $i++) {
            $start = $cursor->copy()->startOfMonth();
            $end = $cursor->copy()->endOfMonth();

            $recette = (float) Paiement::forAtelier($atelierId)
                ->recettes()
                ->whereBetween('date_paiement', [$start, $end])
                ->sum('montant');

            $depense = (float) Depense::forAtelier($atelierId)
                ->whereBetween('date_depense', [$start, $end])
                ->sum('montant');

            $series[] = [
                'label' => $start->translatedFormat('M Y'),
                'depense' => $depense,
                'recette' => $recette,
            ];

            $cursor->addMonth();
        }

        return $series;
    }

    /**
     * @return array<string, float>
     */
    public function repartitionDepenses(?string $from = null, ?string $to = null): array
    {
        $atelierId = $this->atelierId;
        $from = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfMonth();
        $to = $to ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfMonth();

        $rows = Depense::forAtelier($atelierId)
            ->whereBetween('date_depense', [$from, $to])
            ->get()
            ->groupBy(fn (Depense $d) => $d->categorieLabel());

        $out = [];

        foreach ($rows as $label => $items) {
            $out[$label] = (float) $items->sum('montant');
        }

        arsort($out);

        return $out;
    }

    /**
     * Clients les plus actifs de l'atelier.
     *
     * @return array<int, array{client: Client, libelle: string, total: float}>
     */
    public function topClients(int $limit = 5): array
    {
        $atelierId = $this->atelierId;

        return Client::forAtelier($atelierId)
            // whereHas plutôt que having() : having sur une requête non
            // agrégée n'est accepté que par MySQL et échoue sur SQLite.
            ->whereHas('commandes', fn ($q) => $q->where('statut', '!=', CommandeStatut::Annulee->value))
            ->withCount([
                'commandes as total_commandes' => fn ($q) => $q->where('statut', '!=', CommandeStatut::Annulee->value),
            ])
            ->orderByDesc('total_commandes')
            ->limit($limit)
            ->get()
            ->map(fn (Client $client) => [
                'client' => $client,
                'libelle' => $client->nom,
                'total' => (int) $client->total_commandes,
            ])
            ->all();
    }

    /**
     * Activité récente (événements internes de l'atelier).
     *
     * @return Collection<int, array{titre: string, detail: string, icon: string, tone: string, url: string, date: Carbon}>
     */
    public function activiteRecente(int $limit = 8): Collection
    {
        $atelierId = $this->atelierId;
        $events = collect();

        Commande::forAtelier($atelierId)
            ->with('client')
            ->latest('updated_at')
            ->limit(10)
            ->get()
            ->each(function (Commande $commande) use ($events) {
                $events->push([
                    'titre' => "Commande {$commande->numero}",
                    'detail' => $commande->client?->nom.' — '.$commande->statutLabel(),
                    'icon' => 'fa-solid fa-scissors',
                    'tone' => 'bg-brand-100 text-brand-700 dark:bg-white/10 dark:text-brand-200',
                    'url' => route('commandes.show', $commande->id),
                    'date' => $commande->updated_at,
                ]);
            });

        Paiement::forAtelier($atelierId)
            ->with('commande', 'client')
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->each(function (Paiement $paiement) use ($events) {
                $events->push([
                    'titre' => 'Paiement '.number_format((float) $paiement->montant, 0, ',', ' ').' '.config('coutureflow.currency'),
                    'detail' => $paiement->commande?->numero ?? $paiement->client?->nom ?? $paiement->description ?? 'Recette diverse',
                    'icon' => 'fa-solid fa-sack-dollar',
                    'tone' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
                    'url' => $paiement->commande_id
                        ? route('commandes.show', $paiement->commande_id)
                        : route('caisse.index'),
                    'date' => $paiement->created_at,
                ]);
            });

        Depense::forAtelier($atelierId)
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->each(function (Depense $depense) use ($events) {
                $events->push([
                    'titre' => 'Dépense — '.$depense->categorieLabel(),
                    'detail' => $depense->libelle.' ('.number_format((float) $depense->montant, 0, ',', ' ').' '.config('coutureflow.currency').')',
                    'icon' => $depense->categorieIcon(),
                    'tone' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300',
                    'url' => route('depenses.index'),
                    'date' => $depense->created_at,
                ]);
            });

        return $events->sortByDesc('date')->take($limit)->values();
    }

    /**
     * @return array<int, array{type: string, label: string, total: float}>
     */
    public function repartitionPaiements(?string $from = null, ?string $to = null): array
    {
        $atelierId = $this->atelierId;
        $from = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfMonth();
        $to = $to ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfMonth();

        $rows = Paiement::forAtelier($atelierId)
            ->whereBetween('date_paiement', [$from, $to])
            ->get()
            ->groupBy(fn (Paiement $p) => $p->typeLabel());

        $out = [];

        foreach ($rows as $label => $items) {
            $out[] = [
                'type' => (string) $items->first()->typeLabel(),
                'label' => $label,
                'total' => (float) $items->sum('montant'),
            ];
        }

        return $out;
    }

    /**
     * Répartition par méthode de paiement.
     *
     * @return array<int, array{label: string, total: float, icon: string}>
     */
    public function repartitionMethodes(?string $from = null, ?string $to = null): array
    {
        $atelierId = $this->atelierId;
        $from = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfMonth();
        $to = $to ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfMonth();

        $rows = Paiement::forAtelier($atelierId)
            ->whereBetween('date_paiement', [$from, $to])
            ->get()
            ->groupBy(fn (Paiement $p) => $p->methodeLabel());

        $out = [];

        foreach ($rows as $label => $items) {
            $out[] = [
                'label' => $label,
                'total' => (float) $items->sum('montant'),
                'icon' => $items->first()->methodeIcon(),
            ];
        }

        return $out;
    }
}
