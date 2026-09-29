<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Envoi d'une notification à tous les navigateurs abonnés au push d'un
 * utilisateur. Ce canal complète la notification « database » : seul le
 * premier est nécessaire à l'affichage dans l'application ; le second permet
 * de prévenir même lorsque l'application est fermée.
 *
 * Sans clés VAPID configurées, la méthode ne fait rien : c'est une fonction
 * d'agrément, jamais une brique dont dépend le bon fonctionnement de
 * l'atelier.
 */
class PushService
{
    public function envoyer(User $user, array $donnees): void
    {
        $abonnements = $user->pushSubscriptions()->get();

        if ($abonnements->isEmpty() || ! $this->clesConfigurees()) {
            return;
        }

        $this->garantirOpenSslConf();

        try {
            $webPush = new WebPush($this->auth(), [], 20);

            $payload = json_encode([
                'title' => $donnees['title'] ?? 'Couture Flow',
                'message' => $donnees['message'] ?? '',
                'icon' => $donnees['icon'] ?? '/images/icons/icon-192.png',
                'url' => $donnees['url'] ?? '/tableau-de-bord',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            foreach ($abonnements as $abonnement) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $abonnement->endpoint,
                        'publicKey' => $abonnement->keys_p256dh,
                        'authToken' => $abonnement->keys_auth,
                        'contentEncoding' => 'aes128gcm',
                    ]),
                    $payload,
                    ['TTL' => 3600, 'urgency' => 'high'],
                );
            }

            foreach ($webPush->flush() as $rapport) {
                $this->traiterRapport($rapport);
            }
        } catch (\Throwable $e) {
            Log::warning('Notification push non envoyée : '.$e->getMessage());
        }
    }

    private function clesConfigurees(): bool
    {
        return filled(config('services.webpush.vapid.public_key'))
            && filled(config('services.webpush.vapid.private_key'));
    }

    private function auth(): array
    {
        return [
            'VAPID' => [
                'subject' => config('services.webpush.vapid.subject'),
                'publicKey' => config('services.webpush.vapid.public_key'),
                'privateKey' => config('services.webpush.vapid.private_key'),
            ],
        ];
    }

    /**
     * Sur certaines installations Windows (XAMPP…), OpenSSL ne trouve pas sa
     * configuration et refuse de chiffer les clés de chiffrement des push.
     * On la lui indique à partir de l'emplacement du binaire PHP, sans rien
     * casser ailleurs : si la variable est déjà définie, on n'y touche pas.
     */
    private function garantirOpenSslConf(): void
    {
        if (getenv('OPENSSL_CONF')) {
            return;
        }

        $candidats = [
            dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'openssl'.DIRECTORY_SEPARATOR.'openssl.cnf',
            'C:\\xampp\\apache\\conf\\openssl.cnf',
        ];

        foreach ($candidats as $candidat) {
            if (is_file($candidat)) {
                putenv('OPENSSL_CONF='.$candidat);

                return;
            }
        }
    }

    private function traiterRapport(MessageSentReport $rapport): void
    {
        if ($rapport->isSuccess()) {
            return;
        }

        // L'endpoint renvoie 404/410 : le navigateur a purgé l'abonnement
        // (application désinstallée, permission révoquée). Inutile de
        // l'envoyer encore : on oublie cette extrémité.
        if ($rapport->isSubscriptionExpired()) {
            PushSubscription::where('endpoint', $rapport->getEndpoint())->delete();

            return;
        }

        Log::warning('Notification push refusée : '.($rapport->getReason() ?: $rapport->getEndpoint()));
    }
}
