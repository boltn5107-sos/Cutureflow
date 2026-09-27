<?php

namespace App\Notifications;

use Illuminate\Support\Facades\Route;

class CommandeEnRetardNotification extends BaseNotification
{
    public function __construct(
        private readonly int $commandeId,
        private readonly string $numero,
        private readonly int $jours,
    ) {}

    public function title(): string
    {
        return 'Commande en retard';
    }

    public function message(): string
    {
        return "La commande {$this->numero} est en retard de {$this->jours} jour(s).";
    }

    public function url(): string
    {
        return route('commandes.show', $this->commandeId);
    }

    public function icon(): string
    {
        return 'fa-solid fa-triangle-exclamation';
    }

    public function tone(): string
    {
        return 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300';
    }

    public function context(): array
    {
        return ['cle' => 'commande-retard', 'commande_id' => $this->commandeId];
    }
}
