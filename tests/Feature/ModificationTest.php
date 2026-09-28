<?php

namespace Tests\Feature;

use App\Enums\CommandeStatut;
use App\Enums\UserStatus;
use App\Models\Atelier;
use App\Models\Client;
use App\Models\Commande;
use App\Models\Depense;
use App\Models\Modele;
use App\Models\Paiement;
use App\Models\RendezVous;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Les routes de modification (PUT/PATCH) sont le pendant exact des pages
 * d'édition : ce que le formulaire d'édition autorise à voir, la route
 * d'écriture doit l'autoriser à modifier.
 *
 * Chaque test vérifie donc deux choses : la modification enregistre bien les
 * nouvelles valeurs, et un atelier ne peut pas modifier la fiche d'un autre
 * atelier. Cette seconde moitié est celle qui manquait : edit() et destroy()
 * appelaient authorize(), update() l'oubliait partout sauf sur les mesures.
 */
class ModificationTest extends TestCase
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

    private function atelierId(User $user): int
    {
        return $user->atelier->id;
    }

    /* Clients */

    public function test_un_client_peut_etre_modifie(): void
    {
        $client = Client::create([
            'atelier_id' => $this->atelierId($this->alice),
            'nom' => 'Awa Ndiaye',
            'telephone' => '77 000 00 00',
        ]);

        $this->actingAs($this->alice)->put(route('clients.update', $client), [
            'nom' => 'Awa Ndiaye Diop',
            'telephone' => '77 111 11 11',
            'email' => 'awa@example.test',
            'is_active' => '1',
        ])->assertRedirect(route('clients.show', $client));

        $this->assertSame('Awa Ndiaye Diop', $client->fresh()->nom);
        $this->assertSame('77 111 11 11', $client->fresh()->telephone);
        $this->assertSame('awa@example.test', $client->fresh()->email);
        $this->assertTrue((bool) $client->fresh()->is_active);
    }

    public function test_un_client_d_un_autre_atelier_ne_peut_pas_etre_modifie(): void
    {
        $client = Client::create([
            'atelier_id' => $this->atelierId($this->alice),
            'nom' => 'Awa Ndiaye',
            'telephone' => '77 000 00 00',
        ]);

        $this->actingAs($this->bob)->put(route('clients.update', $client), [
            'nom' => 'Pirate',
            'telephone' => '77 999 99 99',
        ])->assertForbidden();

        $this->assertSame('Awa Ndiaye', $client->fresh()->nom);
    }

    /* Commandes */

    public function test_une_commande_peut_etre_modifiee_et_solde_recalcule(): void
    {
        $commande = Commande::create([
            'atelier_id' => $this->atelierId($this->alice),
            'client_id' => $this->clientId($this->alice),
            'numero' => 'CMD-2026-0001',
            'type' => 'Robe',
            'prix_total' => 100000,
            'avance' => 30000,
            'date_commande' => now()->toDateString(),
            'statut' => CommandeStatut::EnAttente,
        ]);

        $this->actingAs($this->alice)->put(route('commandes.update', $commande), [
            'client_id' => $commande->client_id,
            'prix_total' => 150000,
            'avance' => 50000,
            'date_commande' => now()->toDateString(),
            'statut' => CommandeStatut::EnProduction->value,
        ])->assertRedirect(route('commandes.show', $commande));

        $commande = $commande->fresh();

        $this->assertSame(150000.0, (float) $commande->prix_total);
        $this->assertSame(50000.0, (float) $commande->avance);

        // Le solde est un champ calculé : il suit le prix et l'avance.
        $this->assertSame(100000.0, (float) $commande->solde);
        $this->assertSame(CommandeStatut::EnProduction, $commande->statut);
    }

    public function test_une_commande_d_un_autre_atelier_ne_peut_pas_etre_modifiee(): void
    {
        $commande = Commande::create([
            'atelier_id' => $this->atelierId($this->alice),
            'client_id' => $this->clientId($this->alice),
            'numero' => 'CMD-2026-0002',
            'prix_total' => 100000,
            'avance' => 0,
            'date_commande' => now()->toDateString(),
            'statut' => CommandeStatut::EnAttente,
        ]);

        $this->actingAs($this->bob)->put(route('commandes.update', $commande), [
            'client_id' => $commande->client_id,
            'prix_total' => 1,
            'avance' => 0,
            'date_commande' => now()->toDateString(),
            'statut' => CommandeStatut::EnProduction->value,
        ])->assertForbidden();

        $this->assertSame(100000.0, (float) $commande->fresh()->prix_total);
    }

    /* Dépenses */

    public function test_une_depense_peut_etre_modifiee(): void
    {
        $depense = Depense::create([
            'atelier_id' => $this->atelierId($this->alice),
            'libelle' => 'Wax 5 mètres',
            'categorie' => 'tissu',
            'montant' => 25000,
            'date_depense' => now()->toDateString(),
            'created_by' => $this->alice->id,
        ]);

        $this->actingAs($this->alice)->put(route('depenses.update', $depense), [
            'libelle' => 'Wax 8 mètres',
            'categorie' => 'tissu',
            'montant' => 40000,
            'date_depense' => now()->toDateString(),
        ])->assertRedirect(route('depenses.index'));

        $this->assertSame('Wax 8 mètres', $depense->fresh()->libelle);
        $this->assertSame(40000.0, (float) $depense->fresh()->montant);
    }

    public function test_une_depense_d_un_autre_atelier_ne_peut_pas_etre_modifiee(): void
    {
        $depense = Depense::create([
            'atelier_id' => $this->atelierId($this->alice),
            'libelle' => 'Aiguilles',
            'categorie' => 'machine',
            'montant' => 5000,
            'date_depense' => now()->toDateString(),
            'created_by' => $this->alice->id,
        ]);

        $this->actingAs($this->bob)->put(route('depenses.update', $depense), [
            'libelle' => 'Piraté',
            'categorie' => 'machine',
            'montant' => 1,
            'date_depense' => now()->toDateString(),
        ])->assertForbidden();

        $this->assertSame('Aiguilles', $depense->fresh()->libelle);
    }

    /* Modèles du catalogue */

    public function test_un_modele_peut_etre_modifie(): void
    {
        $modele = Modele::create([
            'atelier_id' => $this->atelierId($this->alice),
            'nom' => 'Robe wax',
            'categorie' => 'femme',
            'prix_indicatif' => 45000,
            'is_active' => true,
        ]);

        $this->actingAs($this->alice)->put(route('modeles.update', $modele), [
            'nom' => 'Robe wax brodée',
            'categorie' => 'femme',
            'prix_indicatif' => 55000,
            'is_active' => '0',
        ])->assertRedirect(route('modeles.show', $modele));

        $modele = $modele->fresh();

        $this->assertSame('Robe wax brodée', $modele->nom);
        $this->assertSame(55000.0, (float) $modele->prix_indicatif);
        $this->assertFalse((bool) $modele->is_active);
    }

    public function test_un_modele_d_un_autre_atelier_ne_peut_pas_etre_modifie(): void
    {
        $modele = Modele::create([
            'atelier_id' => $this->atelierId($this->alice),
            'nom' => 'Robe wax',
            'categorie' => 'femme',
            'is_active' => true,
        ]);

        $this->actingAs($this->bob)->put(route('modeles.update', $modele), [
            'nom' => 'Piraté',
            'categorie' => 'femme',
        ])->assertForbidden();

        $this->assertSame('Robe wax', $modele->fresh()->nom);
    }

    /* Paiements */

    public function test_un_paiement_peut_etre_modifie(): void
    {
        $commande = $this->commandeId($this->alice);

        $paiement = Paiement::create([
            'atelier_id' => $this->atelierId($this->alice),
            'client_id' => $this->clientId($this->alice),
            'commande_id' => $commande,
            'type' => 'avance',
            'methode' => 'especes',
            'montant' => 30000,
            'date_paiement' => now()->toDateString(),
            'created_by' => $this->alice->id,
        ]);

        $this->actingAs($this->alice)->put(route('caisse.paiements.update', $paiement), [
            'client_id' => $paiement->client_id,
            'commande_id' => $commande,
            'type' => 'solde',
            'methode' => 'wave',
            'montant' => 40000,
            'date_paiement' => now()->toDateString(),
        ])->assertRedirect(route('caisse.paiements.show', $paiement));

        $paiement = $paiement->fresh();

        $this->assertSame('solde', $paiement->type->value);
        $this->assertSame('wave', $paiement->methode->value);
        $this->assertSame(40000.0, (float) $paiement->montant);
    }

    public function test_un_paiement_d_un_autre_atelier_ne_peut_pas_etre_modifie(): void
    {
        $paiement = Paiement::create([
            'atelier_id' => $this->atelierId($this->alice),
            'client_id' => $this->clientId($this->alice),
            'type' => 'avance',
            'methode' => 'especes',
            'montant' => 30000,
            'date_paiement' => now()->toDateString(),
            'created_by' => $this->alice->id,
        ]);

        $this->actingAs($this->bob)->put(route('caisse.paiements.update', $paiement), [
            'client_id' => $paiement->client_id,
            'type' => 'avance',
            'methode' => 'especes',
            'montant' => 1,
            'date_paiement' => now()->toDateString(),
        ])->assertForbidden();

        $this->assertSame(30000.0, (float) $paiement->fresh()->montant);
    }

    /* Planning */

    public function test_un_rendez_vous_peut_etre_modifie(): void
    {
        $rendezVous = RendezVous::create([
            'atelier_id' => $this->atelierId($this->alice),
            'client_id' => $this->clientId($this->alice),
            'titre' => 'Essayage',
            'type' => 'essayage',
            'date_debut' => now()->addDays(3)->toDateString(),
            'heure_debut' => '10:00',
        ]);

        $this->actingAs($this->alice)->put(route('planning.update', $rendezVous), [
            'client_id' => $rendezVous->client_id,
            'titre' => 'Essayage final',
            'type' => 'essayage',
            'date_debut' => now()->addDays(5)->toDateString(),
            'heure_debut' => '15:00',
        ])->assertRedirect(route('planning.index', ['mois' => now()->addDays(5)->format('Y-m')]));

        $this->assertSame('Essayage final', $rendezVous->fresh()->titre);
    }

    public function test_un_rendez_vous_d_un_autre_atelier_ne_peut_pas_etre_modifie(): void
    {
        $rendezVous = RendezVous::create([
            'atelier_id' => $this->atelierId($this->alice),
            'client_id' => $this->clientId($this->alice),
            'titre' => 'Essayage',
            'type' => 'essayage',
            'date_debut' => now()->addDays(3)->toDateString(),
            'heure_debut' => '10:00',
        ]);

        $this->actingAs($this->bob)->put(route('planning.update', $rendezVous), [
            'client_id' => $rendezVous->client_id,
            'titre' => 'Piraté',
            'type' => 'essayage',
            'date_debut' => now()->addDays(3)->toDateString(),
            'heure_debut' => '10:00',
        ])->assertForbidden();

        $this->assertSame('Essayage', $rendezVous->fresh()->titre);
    }

    /* Profil */

    public function test_le_profil_et_la_fiche_atelier_se_mettent_a_jour(): void
    {
        $this->actingAs($this->alice)->put(route('profil.update'), [
            'name' => 'Awa Diop',
            'email' => 'awa@example.test',
            'phone' => '77 222 22 22',
            'atelier_nom' => 'Atelier Awa couture',
            'atelier_telephone' => '33 800 00 00',
            'adresse' => 'Rue 10',
            'ville' => 'Thiès',
            'ninea' => 'NINEA-123',
        ])->assertSessionHas('success');

        $this->assertSame('Awa Diop', $this->alice->fresh()->name);
        $this->assertSame('awa@example.test', $this->alice->fresh()->email);
        $this->assertSame('Atelier Awa couture', $this->alice->fresh()->atelier->nom);
        $this->assertSame('Thiès', $this->alice->fresh()->atelier->ville);
    }

    public function test_le_telephone_de_l_atelier_ne_reprend_plus_le_numero_du_responsable(): void
    {
        /*
         * Régression : le numéro personnel du responsable écrasait le
         * téléphone de l'atelier à chaque enregistrement du profil.
         */
        $this->alice->atelier->update(['telephone' => '33 811 11 11']);

        $this->actingAs($this->alice)->put(route('profil.update'), [
            'name' => 'Awa Diop',
            'email' => 'awa@example.test',
            'phone' => '77 999 88 88',
            'atelier_nom' => 'Atelier Awa couture',
            'adresse' => 'Rue 10',
            'ville' => 'Thiès',
            'ninea' => 'NINEA-123',
        ])->assertSessionHas('success');

        $this->assertSame('77 999 88 88', $this->alice->fresh()->phone);
        $this->assertSame('33 811 11 11', $this->alice->fresh()->atelier->telephone);
    }

    public function test_le_telephone_de_l_atelier_se_modifie_depuis_son_propre_champ(): void
    {
        $this->actingAs($this->alice)->put(route('profil.update'), [
            'name' => 'Awa Diop',
            'email' => 'awa@example.test',
            'phone' => '77 999 88 88',
            'atelier_nom' => 'Atelier Awa couture',
            'atelier_telephone' => '30 100 20 30',
            'adresse' => 'Rue 10',
            'ville' => 'Thiès',
            'ninea' => 'NINEA-123',
        ])->assertSessionHas('success');

        $this->assertSame('30 100 20 30', $this->alice->fresh()->atelier->telephone);
    }

    public function test_le_mot_de_passe_ne_change_qu_avec_le_bon_mot_de_passe_actuel(): void
    {
        $this->actingAs($this->alice)->put(route('profil.password'), [
            'password_actuel' => 'mauvais',
            'password' => 'nouveau-mot-de-passe',
            'password_confirmation' => 'nouveau-mot-de-passe',
        ])->assertSessionHasErrors('password_actuel');

        $this->actingAs($this->alice)->put(route('profil.password'), [
            'password_actuel' => 'password',
            'password' => 'nouveau-mot-de-passe',
            'password_confirmation' => 'nouveau-mot-de-passe',
        ])->assertSessionHas('success');

        $this->assertTrue(
            Hash::check('nouveau-mot-de-passe', $this->alice->fresh()->password),
        );
    }

    private function clientId(User $user): int
    {
        return Client::create([
            'atelier_id' => $this->atelierId($user),
            'nom' => 'Client de '.$user->atelier->nom,
            'telephone' => '77 000 00 00',
        ])->id;
    }

    /**
     * Une commande de 100 000 F, sans avance : son solde reste de 100 000 F,
     * ce qui laisse le champ libre pour les règlements partiels.
     */
    private function commandeId(User $user): int
    {
        return Commande::create([
            'atelier_id' => $this->atelierId($user),
            'client_id' => $this->clientId($user),
            'numero' => 'CMD-'.now()->year.'-'.random_int(1000, 9999),
            'type' => 'Robe',
            'prix_total' => 100000,
            'avance' => 0,
            'date_commande' => now()->toDateString(),
            'statut' => CommandeStatut::EnAttente,
        ])->id;
    }
}
