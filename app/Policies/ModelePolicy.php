<?php

namespace App\Policies;

use App\Models\Modele;
use App\Models\ModelePhoto;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ModelePolicy extends AtelierScopedPolicy
{
    protected function atelierId(Model $record): ?int
    {
        return $record instanceof Modele ? $record->atelier_id : null;
    }

    public function viewPhoto(User $user, ModelePhoto $photo): bool
    {
        return $this->owns($user, $photo->modele);
    }
}
