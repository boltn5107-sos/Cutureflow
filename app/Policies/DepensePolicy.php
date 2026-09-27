<?php

namespace App\Policies;

use App\Models\Depense;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DepensePolicy extends AtelierScopedPolicy
{
    protected function atelierId(Model $record): ?int
    {
        return $record instanceof Depense ? $record->atelier_id : null;
    }
}
