<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $target): bool
    {
        return $user->isAdmin() || $user->is($target);
    }

    public function update(User $user, User $target): bool
    {
        return $user->isAdmin();
    }

    public function changeStatus(User $user, User $target): bool
    {
        return $user->isAdmin() && ! $user->is($target);
    }

    public function viewProof(User $user, User $target): bool
    {
        return $user->isAdmin() || $user->is($target);
    }
}
