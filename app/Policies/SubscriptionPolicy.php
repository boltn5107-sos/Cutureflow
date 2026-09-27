<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Subscription $subscription): bool
    {
        return $user->isAdmin() || $user->id === $subscription->user_id;
    }

    public function review(User $user, Subscription $subscription): bool
    {
        return $user->isAdmin();
    }

    /** La preuve n'est servie qu'à l'admin et au propriétaire de l'abonnement. */
    public function viewProof(User $user, Subscription $subscription): bool
    {
        return $user->isAdmin() || $user->id === $subscription->user_id;
    }
}
