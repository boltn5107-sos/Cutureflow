<?php

namespace App\Policies;

use App\Models\RendezVous;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RendezVousPolicy extends AtelierScopedPolicy
{
    protected function atelierId(Model $record): ?int
    {
        return $record instanceof RendezVous ? $record->atelier_id : null;
    }
}
