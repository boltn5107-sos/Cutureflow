<?php

namespace App\Notifications;

use Illuminate\Support\Facades\Route;

class PaiementRecuNotification extends BaseNotification
{
    public function __construct(
        private readonly ?int $paiementId,
        private readonly float $montant,
        private readonly ?string $commande,
    ) {}

    public function title(): string
    {
        return 'Paiement reçu';
    }

    public function message(): string
    {
        $montant = number_format($this->montant, 0, ',', ' ').' '.config('coutureflow.currency');
        $cible = $this->commande ? " pour la commande {$this->commande}" : '';

        return "{$montant} encaissés{$cible}.";
    }

    public function url(): string
    {
        return $this->paiementId
            ? route('caisse.paiements.show', $this->paiementId)
            : route('caisse.index');
    }

    public function icon(): string
    {
        return 'fa-solid fa-sack-dollar';
    }

    public function tone(): string
    {
        return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300';
    }
}
