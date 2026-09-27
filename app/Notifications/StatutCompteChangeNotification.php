<?php

namespace App\Notifications;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class StatutCompteChangeNotification extends BaseNotification
{
    public function __construct(
        private readonly UserStatus $status,
        private readonly ?string $raison,
    ) {}

    public function title(): string
    {
        return match ($this->status) {
            UserStatus::Valide => 'Abonnement validé',
            UserStatus::Rejete => 'Inscription rejetée',
            UserStatus::Bloque => 'Compte bloqué',
            UserStatus::EnAttente => 'Compte remis en attente',
        };
    }

    public function message(): string
    {
        if (filled($this->raison)) {
            return $this->raison;
        }

        return $this->status->description();
    }

    public function url(): string
    {
        return match ($this->status) {
            UserStatus::Valide => route('dashboard'),
            UserStatus::EnAttente => route('pending'),
            default => route('acces.refuse'),
        };
    }

    public function icon(): string
    {
        return $this->status->icon();
    }

    public function tone(): string
    {
        return match ($this->status) {
            UserStatus::Valide => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
            UserStatus::Rejete => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300',
            UserStatus::Bloque => 'bg-brand-200 text-brand-900 dark:bg-brand-accent/20 dark:text-brand-accent',
            UserStatus::EnAttente => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
        };
    }
}
