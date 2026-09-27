<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class MesurePolicy extends AtelierScopedPolicy
{
    /**
     * Une mesure appartient à un client : l'atelier est résolu via le client.
     */
    protected function atelierId(\Illuminate\Database\Eloquent\Model $record): ?int
    {
        return $record instanceof \App\Models\Mesure ? $record->client?->atelier_id : null;
    }
}
