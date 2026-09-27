<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\StatutCompteChangeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Centralise les changements de statut d'un atelier et de son abonnement
 * afin de garder `users.status` et `subscriptions.statut` cohérents.
 */
class AccountStatusService
{
    public function apply(Subscription $subscription, UserStatus $statut, User $admin, ?string $raison = null, ?string $validFrom = null, ?string $validUntil = null): User
    {
        return DB::transaction(function () use ($subscription, $statut, $admin, $raison, $validFrom, $validUntil) {
            $user = $subscription->user;

            $subscription->forceFill([
                'statut' => $statut,
                'reason' => $raison,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
                'valid_from' => $validFrom ?: ($statut === UserStatus::Valide ? now() : $subscription->valid_from),
                'valid_until' => $validUntil ?: $this->calculerValidite($subscription, $validFrom),
            ])->save();

            $user->forceFill([
                'status' => $statut,
                'status_reason' => $raison,
                'status_updated_at' => now(),
                'validated_at' => $statut === UserStatus::Valide ? now() : $user->validated_at,
                'validated_by' => $statut === UserStatus::Valide ? $admin->id : $user->validated_by,
            ])->save();

            if ($statut === UserStatus::Valide) {
                $user->atelier?->forceFill([
                    'valid_from' => $user->atelier->valid_from ?? now(),
                    'valid_until' => $this->calculerValidite($subscription, $validFrom),
                ])->save();
            }

            return $user->refresh();
        });
    }

    /**
     * Bloque / débloque un atelier sans changer l'historique des abonnements.
     */
    public function setUserStatus(User $user, UserStatus $statut, User $admin, ?string $raison = null): User
    {
        DB::transaction(function () use ($user, $statut, $admin, $raison) {
            $user->forceFill([
                'status' => $statut,
                'status_reason' => $raison,
                'status_updated_at' => now(),
                'validated_at' => $statut === UserStatus::Valide ? now() : $user->validated_at,
                'validated_by' => $statut === UserStatus::Valide ? $admin->id : $user->validated_by,
            ])->save();
        });

        return $user->refresh();
    }

    public function notifier(User $user, UserStatus $statut, ?string $raison = null): void
    {
        Notification::send($user, new StatutCompteChangeNotification($statut, $raison));
    }

    private function calculerValidite(Subscription $subscription, ?string $validFrom): ?string
    {
        $start = $validFrom ? \Illuminate\Support\Carbon::parse($validFrom) : now();

        return $start->copy()->addMonths(max($subscription->duree_mois, 1))->endOfDay();
    }
}
