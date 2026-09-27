<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class NouvelleInscriptionNotification extends BaseNotification
{
    public function __construct(private readonly User $user) {}

    public function title(): string
    {
        return 'Nouvelle inscription à vérifier';
    }

    public function message(): string
    {
        return "{$this->user->displayName()} ({$this->user->email}) attend la vérification de son paiement.";
    }

    public function url(): string
    {
        return route('admin.paiements.index');
    }

    public function icon(): string
    {
        return 'fa-solid fa-user-plus';
    }

    public function tone(): string
    {
        return 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300';
    }
}
