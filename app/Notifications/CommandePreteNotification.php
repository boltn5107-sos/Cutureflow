<?php

namespace App\Notifications;

use Illuminate\Support\Facades\Route;

class CommandePreteNotification extends BaseNotification
{
    public function __construct(
        private readonly int $commandeId,
        private readonly string $numero,
        private readonly string $client,
    ) {}

    public function title(): string
    {
        return 'Commande prête';
    }

    public function message(): string
    {
        return "La commande {$this->numero} de {$this->client} est prête pour la livraison.";
    }

    public function url(): string
    {
        return route('commandes.show', $this->commandeId);
    }

    public function icon(): string
    {
        return 'fa-solid fa-box-open';
    }

    public function tone(): string
    {
        return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300';
    }

    public function context(): array
    {
        return ['cle' => 'commande-prete', 'commande_id' => $this->commandeId];
    }
}
