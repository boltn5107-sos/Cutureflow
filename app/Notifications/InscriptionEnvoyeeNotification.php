<?php

namespace App\Notifications;

use App\Models\User;

class InscriptionEnvoyeeNotification extends BaseNotification
{
    public function title(): string
    {
        return 'Inscription reçue';
    }

    public function message(): string
    {
        return 'Nous avons bien reçu votre preuve de paiement. Votre abonnement sera actif dès validation.';
    }

    public function url(): string
    {
        return route('pending');
    }

    public function icon(): string
    {
        return 'fa-solid fa-paper-plane';
    }

    public function tone(): string
    {
        return 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300';
    }
}
