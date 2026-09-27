<?php

namespace App\Policies;

use App\Models\Paiement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PaiementPolicy extends AtelierScopedPolicy
{
    protected function atelierId(Model $record): ?int
    {
        return $record instanceof Paiement ? $record->atelier_id : null;
    }
}
