<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Politique de base : un utilisateur ne peut agir que sur les enregistrements
 * de son propre atelier. L'admin a un accès complet.
 */
abstract class AtelierScopedPolicy
{
    /** Identifiant d'atelier porté par le modèle. */
    abstract protected function atelierId(Model $record): ?int;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->atelier !== null;
    }

    public function view(User $user, Model $record): bool
    {
        return $this->owns($user, $record);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->atelier !== null;
    }

    public function update(User $user, Model $record): bool
    {
        return $this->owns($user, $record);
    }

    public function delete(User $user, Model $record): bool
    {
        return $this->owns($user, $record);
    }

    protected function owns(User $user, Model $record): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $atelierId = $this->atelierId($record);

        return $atelierId !== null && $atelierId === $user->atelier?->id;
    }
}
