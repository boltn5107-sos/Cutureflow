<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Commande;
use App\Models\Depense;
use App\Models\Mesure;
use App\Models\Modele;
use App\Models\ModelePhoto;
use App\Models\Paiement;
use App\Models\RendezVous;
use App\Models\Subscription;
use App\Models\User;
use App\Policies\ClientPolicy;
use App\Policies\CommandePolicy;
use App\Policies\DepensePolicy;
use App\Policies\MesurePolicy;
use App\Policies\ModelePolicy;
use App\Policies\PaiementPolicy;
use App\Policies\RendezVousPolicy;
use App\Policies\SubscriptionPolicy;
use App\Policies\UserPolicy;
use App\Services\PushService;
use App\View\Components\StatusBadge;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Subscription::class, SubscriptionPolicy::class);
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(Mesure::class, MesurePolicy::class);
        Gate::policy(Commande::class, CommandePolicy::class);
        Gate::policy(Paiement::class, PaiementPolicy::class);
        Gate::policy(Depense::class, DepensePolicy::class);
        Gate::policy(Modele::class, ModelePolicy::class);
        Gate::policy(ModelePhoto::class, ModelePolicy::class);
        Gate::policy(RendezVous::class, RendezVousPolicy::class);

        Blade::component('status-badge', StatusBadge::class);

        // Le compteur de notifications non lues est nécessaire aux en-têtes
        // des deux layouts : il est calculé une seule fois par requête.
        //
        // L'identifiant de la dernière notification sert de point de départ
        // au client qui interroge /notifications/etat : sans lui, la première
        // sonnerie déclencherait pour toutes les notifications déjà à l'écran.
        View::composer(['layouts.app', 'layouts.admin'], function ($view): void {
            $derniere = auth()->user()?->notifications()->latest()->first();

            $view->with('unreadCount', auth()->user()?->unreadNotifications()->count() ?? 0);
            $view->with('dernierNotificationId', $derniere?->id);
        });

        /*
         * Chaque notification « database » enregistrée pour un utilisateur
         * déclenche en parallèle un push vers ses navigateurs abonnés, pour
         * prévenir même application fermée. Le canal « database » est aussi
         * celui des notifications envoyées via NotificationService : le même
         * point de bascule couvre toute l'application.
         */
        Event::listen(NotificationSent::class, function (NotificationSent $evenement): void {
            $notifiable = $evenement->notifiable;

            if (! $notifiable instanceof User || $evenement->channel !== 'database') {
                return;
            }

            $notification = $evenement->notification;
            $donnees = method_exists($notification, 'toDatabase')
                ? $notification->toDatabase($notifiable)
                : (method_exists($notification, 'toArray') ? $notification->toArray($notifiable) : []);

            if (filled($donnees)) {
                app(PushService::class)->envoyer($notifiable, $donnees);
            }
        });
    }
}
