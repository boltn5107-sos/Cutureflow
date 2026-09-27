<?php

namespace App\Notifications;

use Illuminate\Support\Facades\Route;

class SoldeRestantNotification extends BaseNotification
{
    public function __construct(
        private readonly int $commandeId,
        private readonly string $numero,
        private readonly float $solde,
    ) {}

    public function title(): string
    {
        return 'Solde restant';
    }

    public function message(): string
    {
        $solde = number_format($this->solde, 0, ',', ' ').' '.config('coutureflow.currency');

        return "Il reste {$solde} à encaisser sur la commande {$this->numero}.";
    }

    public function url(): string
    {
        return route('commandes.show', $this->commandeId);
    }

    public function icon(): string
    {
        return 'fa-solid fa-hourglass-half';
    }

    public function tone(): string
    {
        return 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300';
    }

    public function context(): array
    {
        return ['cle' => 'solde-restant', 'commande_id' => $this->commandeId];
    }
}
