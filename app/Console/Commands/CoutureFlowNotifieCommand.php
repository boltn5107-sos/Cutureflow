<?php

namespace App\Console\Commands;

use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\AbonnementExpireNotification;
use App\Notifications\NouvelleInscriptionNotification;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CoutureFlowNotifieCommand extends Command
{
    protected $signature = 'coutureflow:notifier
                            {--days=7 : Nombre de jours d\'avance pour l\'alerte d\'expiration}';

    protected $description = 'Analyse les échéances (livraisons proches, commandes en retard, abonnements qui expirent).';

    public function handle(NotificationService $notifications): int
    {
        $days = max(1, (int) $this->option('days'));

        $commandes = $notifications->analyserEcheances();
        $this->info("Notifications d'échéance envoyées : {$commandes}.");

        $expires = $this->notifierAbonnements($days);

        $this->info("Alertes d'abonnement envoyées : {$expires}.");

        return self::SUCCESS;
    }

    private function notifierAbonnements(int $days): int
    {
        $users = User::roleAtelier()
            ->status(UserStatus::Valide)
            ->whereHas('atelier', fn ($q) => $q->whereNotNull('valid_until')->whereBetween('valid_until', [now()->startOfDay(), now()->addDays($days)->endOfDay()]))
            ->with('atelier')
            ->get();

        foreach ($users as $user) {
            $expire = $user->atelier->valid_until;

            if (! $expire) {
                continue;
            }

            $dejaAlerte = $user->notifications()
                ->where('type', AbonnementExpireNotification::class)
                ->where('created_at', '>=', $expire->copy()->subDays(30))
                ->exists();

            if ($dejaAlerte) {
                continue;
            }

            $user->notify(new AbonnementExpireNotification($expire->format('d/m/Y'), false));
        }

        return $users->count();
    }
}
