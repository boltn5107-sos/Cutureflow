<?php

namespace Tests\Feature;

use App\Enums\CommandeStatut;
use App\Enums\UserStatus;
use App\Models\Atelier;
use App\Models\Client;
use App\Models\Commande;
use App\Models\User;
use App\Notifications\CommandePreteNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le son et la pastille des notifications reposent sur un point d'état
 * interrogé périodiquement par le navigateur : sans lui, rien ne se passe
 * dans un onglet déjà ouvert.
 *
 * Ces tests verrouillent le contrat de ce point d'état — et le fait qu'il
 * ne doit surtout rien marquer comme lu.
 */
class NotificationEtatTest extends TestCase
{
    use RefreshDatabase;

    private User $alice;

    private User $bob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alice = $this->makeAtelier('Atelier Alice');
        $this->bob = $this->makeAtelier('Atelier Bob');
    }

    private function makeAtelier(string $nom): User
    {
        $user = User::factory()->create([
            'status' => UserStatus::Valide,
            'role' => 'atelier',
        ]);

        Atelier::create([
            'user_id' => $user->id,
            'nom' => $nom,
            'ville' => 'Dakar',
        ]);

        return $user->fresh();
    }

    private function client(User $atelier, string $nom = 'Awa Diop'): Client
    {
        return Client::create([
            'atelier_id' => $atelier->atelier->id,
            'nom' => $nom,
            'telephone' => '77 000 11 22',
        ]);
    }

    private function commande(Client $client, User $atelier): Commande
    {
        return Commande::create([
            'client_id' => $client->id,
            'atelier_id' => $atelier->atelier->id,
            'numero' => 'CMD-'.uniqid(),
            'statut' => CommandeStatut::EnAttente,
            'prix_total' => 50000,
            'date_commande' => now()->toDateString(),
        ]);
    }

    private function notifierPrete(Commande $commande): void
    {
        $this->alice->notify(new CommandePreteNotification(
            $commande->id,
            $commande->numero,
            $commande->client?->nom ?? 'Client',
        ));
    }

    public function test_un_visiteur_est_renvoye_vers_la_connexion(): void
    {
        $this->getJson(route('notifications.etat'))->assertUnauthorized();
    }

    public function test_le_point_d_etat_renvoie_le_compteur_et_la_derniere_notification(): void
    {
        $this->notifierPrete($this->commande($this->client($this->alice), $this->alice));

        $reponse = $this->actingAs($this->alice)
            ->getJson(route('notifications.etat'))
            ->assertOk()
            ->assertJsonStructure(['nonLues', 'dernier' => ['id', 'titre', 'message', 'url']]);

        $this->assertSame(1, $reponse->json('nonLues'));
        $this->assertSame('Commande prête', $reponse->json('dernier.titre'));
    }

    public function test_un_compte_sans_notification_repond_une_liste_vide(): void
    {
        $this->actingAs($this->alice)
            ->getJson(route('notifications.etat'))
            ->assertOk()
            ->assertExactJson(['nonLues' => 0, 'dernier' => null]);
    }

    public function test_le_point_d_etat_ne_marque_rien_comme_lu(): void
    {
        $this->notifierPrete($this->commande($this->client($this->alice), $this->alice));

        $this->actingAs($this->alice)->getJson(route('notifications.etat'))->assertOk();

        // Le navigateur interroge en boucle : marquer ici ferait disparaître
        // la pastille et le son de la notification qu'on vient de notifier.
        $this->assertSame(1, $this->alice->fresh()->unreadNotifications()->count());
    }

    public function test_le_point_d_etat_ne_fuit_rien_vers_un_autre_atelier(): void
    {
        $this->notifierPrete($this->commande($this->client($this->alice), $this->alice));

        $reponse = $this->actingAs($this->bob)->getJson(route('notifications.etat'))->assertOk();

        $this->assertSame(0, $reponse->json('nonLues'));
        $this->assertNull($reponse->json('dernier'));
    }

    public function test_le_point_d_etat_reste_ouvert_aux_administrateurs(): void
    {
        $admin = User::factory()->create([
            'status' => UserStatus::Valide,
            'role' => 'admin',
        ]);

        /*
         * Un administrateur reçoit des notifications mais n'a pas accès à
         * /notifications, barré par le filtre d'abonnement. Son point d'état
         * doit rester interrogeable, sinon son navigateur ne sait rien.
         */
        $this->actingAs($admin)
            ->getJson(route('notifications.etat'))
            ->assertOk()
            ->assertExactJson(['nonLues' => 0, 'dernier' => null]);
    }

    public function test_l_entete_expose_l_adresse_du_point_d_etat_et_la_derniere_notification(): void
    {
        $this->notifierPrete($this->commande($this->client($this->alice), $this->alice));

        $html = (string) $this->actingAs($this->alice)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        /*
         * Sans cet identifiant, la première interrogation comparerait la
         * notification déjà affichée à elle-même — ou Worse, le sonnerait.
         */
        $this->assertStringContainsString('data-notifications="'.route('notifications.etat').'"', $html);
        $this->assertStringContainsString('data-dernier-id="'.$this->alice->fresh()->notifications()->latest()->first()->id.'"', $html);
    }

    public function test_le_partage_promouvoit_l_application_sur_le_tableau_de_bord(): void
    {
        $html = (string) $this->actingAs($this->alice)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('x-data="partage(', $html);
        $this->assertStringContainsString('Couture Flow', $html);

        // Les canaux sont rendus par la feuille ; les adresses, elles,
        // sont construites par le composant au moment du clic.
        $this->assertStringContainsString('WhatsApp', $html);
        $this->assertStringContainsString('Copier le lien', $html);
    }

    public function test_le_partage_ignore_le_sms(): void
    {
        $html = (string) $this->actingAs($this->alice)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        // Le SMS a été écarté : la feuille de partage ne doit pas l'annoncer.
        $this->assertStringNotContainsString('>SMS<', $html);
        $this->assertStringNotContainsString('lienSms', $html);
    }

    public function test_le_partage_ne_transmet_pas_de_donnee_client(): void
    {
        $client = $this->client($this->alice);
        $commande = $this->commande($client, $this->alice);

        // Le partage promeut l'application, il n'envoie pas la fiche d'un
        // client : ni sur sa fiche, ni sur celle de sa commande.
        foreach (['clients.show' => route('clients.show', $client), 'commandes.show' => route('commandes.show', $commande)] as $route) {
            $html = (string) $this->actingAs($this->alice)
                ->get($route)
                ->assertOk()
                ->getContent();

            $this->assertStringNotContainsString('x-data="partage(', $html, $route);
        }
    }
}
