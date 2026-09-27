<?php

namespace App\Notifications;

use Illuminate\Support\Facades\Route;

class AbonnementExpireNotification extends BaseNotification
{
    public function __construct(
        private readonly string $date,
        private readonly bool $expire,
    ) {}

    public function title(): string
    {
        return $this->expire ? 'Abonnement arrivé à échéance' : 'Abonnement bientôt expiré';
    }

    public function message(): string
    {
        return $this->expire
            ? "Votre abonnement a expiré le {$this->date}. Merci de le renouveler pour continuer."
            : "Votre abonnement expire le {$this->date}. Prévoyez le renouvellement.";
    }

    public function url(): string
    {
        return route('abonnement.index');
    }

    public function icon(): string
    {
        return 'fa-regular fa-calendar-xmark';
    }

    public function tone(): string
    {
        return 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300';
    }
}
