<?php

namespace Tests\Feature;

use App\Enums\CommandeStatut;
use App\Enums\DepenseCategorie;
use App\Enums\UserStatus;
use App\Models\Atelier;
use App\Models\Client;
use App\Models\Commande;
use App\Models\Depense;
use App\Models\Mesure;
use App\Models\Modele;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les pages métier doivent s'ouvrir pour un atelier validé, et les
 * données d'un atelier ne doivent jamais fuiter vers un autre atelier.
 */
class AtelierWorkspaceTest extends TestCase
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

    public function test_toutes_les_pages_metier_s_ouvrent_pour_un_atelier_valide(): void
    {
        $this->actingAs($this->alice);

        foreach ([
            '/tableau-de-bord',
            '/clients',
            '/clients/create',
            '/commandes',
            '/commandes/create',
            '/caisse',
            '/caisse/paiements',
            '/depenses',
            '/depenses/create',
            '/planning',
            '/planning/creer',
            '/notifications',
            '/catalogue',
            '/catalogue/creer',
            '/profil',
            '/abonnement',
        ] as $path) {
            $this->get($path)->assertOk("La page {$path} ne devrait pas renvoyer d'erreur.");
        }
    }

    public function test_les_pages_administration_s_ouvrent_pour_un_administrateur(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => UserStatus::Valide,
        ]);

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/utilisateurs')->assertOk();
        $this->actingAs($admin)->get('/admin/paiements')->assertOk();
    }

    public function test_les_clients_sont_isoles_par_atelier(): void
    {
        Client::create([
            'atelier_id' => $this->alice->atelier->id,
            'nom' => 'Cliente Alpha',
            'telephone' => '77 000 00 01',
        ]);

        Client::create([
            'atelier_id' => $this->bob->atelier->id,
            'nom' => 'Cliente Beta',
            'telephone' => '77 000 00 02',
        ]);

        $this->actingAs($this->alice)->get('/clients')
            ->assertOk()
            ->assertSee('Cliente Alpha')
            ->assertDontSee('Cliente Beta');
    }

    public function test_un_client_d_un_autre_atelier_est_interdit(): void
    {
        $clientBeta = Client::create([
            'atelier_id' => $this->bob->atelier->id,
            'nom' => 'Cliente Beta',
            'telephone' => '77 000 00 02',
        ]);

        $this->actingAs($this->alice)->get('/clients/'.$clientBeta->id)->assertForbidden();
    }

    public function test_une_mesure_ne_peut_pas_etre_ajoutee_a_un_client_d_un_autre_atelier(): void
    {
        $clientBeta = Client::create([
            'atelier_id' => $this->bob->atelier->id,
            'nom' => 'Cliente Beta',
            'telephone' => '77 000 00 02',
        ]);

        $this->actingAs($this->alice)
            ->post('/clients/'.$clientBeta->id.'/mesures', [
                'tour_poitrine' => 90,
                'tour_taille' => 70,
                'tour_hipp' => 95,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('mesures', 0);
    }

    public function test_les_commandes_sont_isolees_par_atelier(): void
    {
        $clientBeta = Client::create([
            'atelier_id' => $this->bob->atelier->id,
            'nom' => 'Cliente Beta',
            'telephone' => '77 000 00 02',
        ]);

        Commande::create([
            'atelier_id' => $this->bob->atelier->id,
            'client_id' => $clientBeta->id,
            'numero' => 'CMD-BOB',
            'statut' => CommandeStatut::EnAttente,
            'date_commande' => now(),
            'prix_total' => 15000,
        ]);

        $this->actingAs($this->alice)->get('/commandes')
            ->assertOk()
            ->assertDontSee('CMD-BOB');
    }

    public function test_une_commande_d_un_autre_atelier_est_interdite(): void
    {
        $clientBeta = Client::create([
            'atelier_id' => $this->bob->atelier->id,
            'nom' => 'Cliente Beta',
            'telephone' => '77 000 00 02',
        ]);

        $commande = Commande::create([
            'atelier_id' => $this->bob->atelier->id,
            'client_id' => $clientBeta->id,
            'numero' => 'CMD-BOB',
            'statut' => CommandeStatut::EnAttente,
            'date_commande' => now(),
            'prix_total' => 15000,
        ]);

        $this->actingAs($this->alice)->get('/commandes/'.$commande->id)->assertForbidden();
    }

    public function test_les_depenses_sont_isolees_par_atelier(): void
    {
        Depense::create([
            'atelier_id' => $this->bob->atelier->id,
            'libelle' => 'Achat tissu Bob',
            'montant' => 25000,
            'categorie' => DepenseCategorie::Tissu,
            'date_depense' => now(),
        ]);

        $this->actingAs($this->alice)->get('/depenses')
            ->assertOk()
            ->assertDontSee('Achat tissu Bob');
    }

    public function test_le_catalogue_est_isole_par_atelier(): void
    {
        Modele::create([
            'atelier_id' => $this->bob->atelier->id,
            'nom' => 'Robe sur mesure',
            'categorie' => 'femme',
            'prix_indicatif' => 20000,
        ]);

        // Le catalogue est propre à chaque atelier.
        $this->actingAs($this->alice)->get('/catalogue')
            ->assertOk()
            ->assertDontSee('Robe sur mesure');

        $this->actingAs($this->bob)->get('/catalogue')
            ->assertOk()
            ->assertSee('Robe sur mesure');
    }

    public function test_un_administrateur_peut_changer_le_statut_d_un_compte(): void
    {
        $cible = $this->makeAtelier('Atelier Cible');

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => UserStatus::Valide,
        ]);

        $this->actingAs($admin)
            ->patch('/admin/utilisateurs/'.$cible->id.'/statut', [
                'status' => UserStatus::Bloque->value,
                'status_reason' => 'Abonnement non renouvelé.',
            ])
            ->assertRedirect();

        $this->assertSame(UserStatus::Bloque, $cible->fresh()->status);
    }

    public function test_un_administrateur_peut_consulter_la_fiche_d_un_atelier(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => UserStatus::Valide,
        ]);

        $this->actingAs($admin)
            ->get('/admin/utilisateurs/'.$this->alice->id)
            ->assertOk()
            ->assertSee('Atelier Alice');
    }
}
