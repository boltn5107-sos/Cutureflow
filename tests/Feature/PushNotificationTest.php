<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\Atelier;
use App\Models\PushSubscription;
use App\Models\User;
use App\Notifications\CommandePreteNotification;
use App\Services\PushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Les notifications « database » ne se voient que dans l'application
 * ouverte. L'abonnement push (Web Push) les prolonge jusqu'aux navigateurs,
 * même application fermée : le code côté navigateur doit pouvoir déposer son
 * abonnement, et le serveur l'oublier quand la permission est révoquée.
 */
class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $atelier;

    protected function setUp(): void
    {
        parent::setUp();

        $usager = User::factory()->create([
            'status' => UserStatus::Valide,
            'role' => 'atelier',
        ]);

        Atelier::create([
            'user_id' => $usager->id,
            'nom' => 'Atelier Push',
            'ville' => 'Dakar',
        ]);

        $this->atelier = $usager->fresh();
    }

    private function endpoint(string $suffix = 'abc'): string
    {
        return 'https://fcm.googleapis.com/fcm/send/'.$suffix;
    }

    public function test_un_visiteur_ne_peut_pas_s_abonner(): void
    {
        $this->postJson(route('notifications.push.subscribe'))
            ->assertUnauthorized();

        $this->deleteJson(route('notifications.push.unsubscribe'))
            ->assertUnauthorized();
    }

    public function test_l_abonnement_du_navigateur_s_enregistre_pour_le_compte(): void
    {
        $this->actingAs($this->atelier)
            ->postJson(route('notifications.push.subscribe'), [
                'endpoint' => $this->endpoint(),
                'keys' => ['p256dh' => 'clap256dh', 'auth' => 'claAuth'],
            ])
            ->assertOk();

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $this->atelier->id,
            'endpoint' => $this->endpoint(),
            'keys_p256dh' => 'clap256dh',
            'keys_auth' => 'claAuth',
        ]);
    }

    public function test_un_meme_endpoint_rejoue_ne_se_duplique_pas(): void
    {
        $route = route('notifications.push.subscribe');

        $this->actingAs($this->atelier)->postJson($route, [
            'endpoint' => $this->endpoint(),
            'keys' => ['p256dh' => 'cle', 'auth' => 'auth'],
        ])->assertOk();

        $this->actingAs($this->atelier)->postJson($route, [
            'endpoint' => $this->endpoint(),
            'keys' => ['p256dh' => 'cle2', 'auth' => 'auth2'],
        ])->assertOk();

        // La clause d'unicité sur l'endpoint tient lieu de déduplication :
        // un navigateur qui rejoue sa souscription remplace l'ancienne.
        $this->assertSame(1, PushSubscription::where('endpoint', $this->endpoint())->count());
        $this->assertSame('cle2', PushSubscription::where('endpoint', $this->endpoint())->firstOrFail()->keys_p256dh);
    }

    public function test_on_peut_se_desabonner_sans_toucher_aux_autres_comptes(): void
    {
        $autre = User::factory()->create(['role' => 'atelier', 'status' => UserStatus::Valide]);

        PushSubscription::create([
            'user_id' => $this->atelier->id,
            'endpoint' => $this->endpoint('moi'),
            'keys_p256dh' => 'cle',
            'keys_auth' => 'auth',
        ]);
        PushSubscription::create([
            'user_id' => $autre->id,
            'endpoint' => $this->endpoint('eux'),
            'keys_p256dh' => 'cle',
            'keys_auth' => 'auth',
        ]);

        $this->actingAs($this->atelier)
            ->deleteJson(route('notifications.push.unsubscribe'), ['endpoint' => $this->endpoint('moi')])
            ->assertOk();

        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => $this->endpoint('moi')]);
        $this->assertDatabaseHas('push_subscriptions', ['endpoint' => $this->endpoint('eux')]);
    }

    public function test_un_endpoint_invalide_est_refuse(): void
    {
        $this->actingAs($this->atelier)
            ->postJson(route('notifications.push.subscribe'), [
                'endpoint' => 'pas-une-url',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint', 'keys.p256dh', 'keys.auth']);
    }

    public function test_les_abonnements_push_ne_font_rien_sans_cles_vapid(): void
    {
        PushSubscription::create([
            'user_id' => $this->atelier->id,
            'endpoint' => $this->endpoint('fantome'),
            'keys_p256dh' => 'cle',
            'keys_auth' => 'auth',
        ]);

        config(['services.webpush.vapid.public_key' => null, 'services.webpush.vapid.private_key' => null]);

        // L'envoi d'une notification ne doit ni échouer ni toucher au réseau :
        // sans clés, la tentative de push est simplement abandonnée.
        $this->atelier->notify(new CommandePreteNotification(1, 'CMD-1', 'Awa Diop'));

        $this->assertSame(1, PushSubscription::where('endpoint', $this->endpoint('fantome'))->count());
    }

    public function test_l_entete_expose_la_cle_vapid_et_l_adresse_d_abonnement(): void
    {
        $html = (string) $this->actingAs($this->atelier)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        // Le navigateur a besoin de l'URL d'abonnement et de la clé publique
        // pour demander une souscription push.
        $this->assertStringContainsString('data-notifications-push="'.route('notifications.push.subscribe').'"', $html);
        $this->assertStringContainsString('name="cf-vapid-public-key"', $html);
    }

    public function test_une_notification_pousse_vers_les_abonnes(): void
    {
        PushSubscription::create([
            'user_id' => $this->atelier->id,
            'endpoint' => $this->endpoint('fantome'),
            'keys_p256dh' => 'cle',
            'keys_auth' => 'auth',
        ]);

        // L'écouteur du canal « database » doit confier l'envoi au service
        // PushService dès qu'une notification est enregistrée pour un
        // utilisateur — sans quoi rien ne partirait vers les navigateurs.
        $mock = $this->mock(PushService::class);
        $mock->shouldReceive('envoyer')
            ->once()
            ->with(Mockery::on(fn ($user) => $user->id === $this->atelier->id), Mockery::type('array'));

        $this->atelier->notify(new CommandePreteNotification(1, 'CMD-1', 'Awa Diop'));
    }
}
