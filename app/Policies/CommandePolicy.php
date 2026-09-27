<?php

namespace App\Policies;

use App\Models\Commande;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CommandePolicy extends AtelierScopedPolicy
{
    protected function atelierId(Model $record): ?int
    {
        return $record instanceof Commande ? $record->atelier_id : null;
    }
}
