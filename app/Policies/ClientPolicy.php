<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ClientPolicy extends AtelierScopedPolicy
{
    protected function atelierId(Model $record): ?int
    {
        return $record instanceof Client ? $record->atelier_id : null;
    }

    public function viewPhoto(User $user, Client $client): bool
    {
        return $this->owns($user, $client);
    }

    public function restore(User $user, Client $client): bool
    {
        return $this->owns($user, $client);
    }

    public function delete(User $user, Model $record): bool
    {
        // Un client ayant des commandes est conservé (soft delete) : l'historique
        // doit rester consultable. La suppression reste possible visuellement.
        return $this->owns($user, $record);
    }
}
