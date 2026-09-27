<?php

namespace App\Services;

use App\Models\Commande;
use App\Notifications\CommandeEnRetardNotification;
use App\Notifications\CommandePreteNotification;
use App\Notifications\LivraisonProcheNotification;
use App\Notifications\PaiementRecuNotification;
use App\Notifications\SoldeRestantNotification;

/**
 * Notifications internes de l'atelier (canal base de données uniquement).
 */
class NotificationService
{
    public function __construct(private readonly ?int $atelierId = null) {}

    /**
     * Notifie le responsable de l'atelier concerné.
     */
    private function notifyUser(Commande $commande, object $notification): void
    {
        $user = $commande->atelier?->user;

        $user?->notify($notification);
    }

    public function commandePrete(Commande $commande): void
    {
        $this->notifyUser($commande, new CommandePreteNotification(
            $commande->id,
            $commande->numero,
            $commande->client?->nom ?? 'client',
        ));
    }

    public function commandeEnRetard(Commande $commande): void
    {
        $this->notifyUser($commande, new CommandeEnRetardNotification(
            $commande->id,
            $commande->numero,
            $commande->joursDeRetard(),
        ));
    }

    public function livraisonProche(Commande $commande): void
    {
        $jours = (int) today()->diffInDays($commande->date_livraison_prevue, false);

        $this->notifyUser($commande, new LivraisonProcheNotification(
            $commande->id,
            $commande->numero,
            $jours,
        ));
    }

    public function soldeRestant(Commande $commande): void
    {
        if ((float) $commande->solde <= 0) {
            return;
        }

        $this->notifyUser($commande, new SoldeRestantNotification(
            $commande->id,
            $commande->numero,
            (float) $commande->solde,
        ));
    }

    public function paiementRecu(Commande $commande, float $montant): void
    {
        $this->notifyUser($commande, new PaiementRecuNotification(
            null,
            $montant,
            $commande->numero,
        ));
    }

    public function commandeMiseAJour(Commande $commande, string $action): void
    {
        if ($commande->estEnRetard()) {
            $this->commandeEnRetard($commande);
        } elseif ($commande->prochainPaiement() !== null) {
            $this->soldeRestant($commande);
        }
    }

    /**
     * Vérifie les commandes à livrer prochainement et en retard.
     * Appelé depuis la commande `coutureflow:notifier`.
     */
    public function analyserEcheances(): int
    {
        $dueSoon = (int) config('coutureflow.reminders.due_soon_days', 2);
        $envoyees = 0;

        Commande::query()
            ->whereIn('statut', [
                \App\Enums\CommandeStatut::EnAttente->value,
                \App\Enums\CommandeStatut::EnProduction->value,
            ])
            ->whereNotNull('date_livraison_prevue')
            ->whereDate('date_livraison_prevue', '<=', today()->addDays($dueSoon))
            ->whereDate('date_livraison_prevue', '>=', today())
            ->with(['client', 'atelier.user'])
            ->get()
            ->each(function (Commande $commande) use (&$envoyees) {
                if (! $this->dejaNotifie($commande, 'livraison-proche', $dueSoon)) {
                    $this->livraisonProche($commande);
                    $envoyees++;
                }
            });

        Commande::query()
            ->enRetard()
            ->with(['client', 'atelier.user'])
            ->get()
            ->each(function (Commande $commande) use (&$envoyees) {
                if (! $this->dejaNotifie($commande, 'commande-retard', 1)) {
                    $this->commandeEnRetard($commande);
                    $envoyees++;
                }
            });

        return $envoyees;
    }

    private function dejaNotifie(Commande $commande, string $cle, int $jours): bool
    {
        $user = $commande->atelier?->user;

        if (! $user) {
            return false;
        }

        return $user->notifications()
            ->where('created_at', '>=', now()->subDays(max($jours, 1))->startOfDay())
            ->get()
            ->contains(function ($notification) use ($commande, $cle) {
                $data = $notification->data;

                return ($data['cle'] ?? null) === $cle
                    && ($data['commande_id'] ?? null) === $commande->id;
            });
    }
}
