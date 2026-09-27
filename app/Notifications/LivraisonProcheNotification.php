<?php

namespace App\Notifications;

use Illuminate\Support\Facades\Route;

class LivraisonProcheNotification extends BaseNotification
{
    public function __construct(
        private readonly int $commandeId,
        private readonly string $numero,
        private readonly int $jours,
    ) {}

    public function title(): string
    {
        return 'Livraison proche';
    }

    public function message(): string
    {
        $delai = $this->jours <= 0
            ? "La livraison est prévue aujourd'hui."
            : "La livraison est prévue dans {$this->jours} jour(s).";

        return "Commande {$this->numero} : {$delai}";
    }

    public function url(): string
    {
        return route('commandes.show', $this->commandeId);
    }

    public function icon(): string
    {
        return 'fa-solid fa-truck-fast';
    }

    public function tone(): string
    {
        return 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300';
    }

    public function context(): array
    {
        return ['cle' => 'livraison-proche', 'commande_id' => $this->commandeId];
    }
}
