<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\Atelier;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Parcours d'inscription : le compte créé est « En Attente » et le paiement
 * Wave est examiné par l'administrateur avant l'ouverture de l'accès.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'atelier_nom' => 'Atelier Diop',
            'nom' => 'Fatou Diop',
            'telephone' => '77 555 00 11',
            'email' => 'fatou@example.test',
            'adresse' => 'Rue 12, Dakar',
            'ville' => 'Dakar',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
            'preuve' => UploadedFile::fake()->image('recu.jpg'),
            'conditions' => '1',
        ], $overrides);
    }

    public function test_le_formulaire_d_inscription_ne_demande_plus_les_informations_de_paiement(): void
    {
        $response = $this->get('/inscription');

        $response->assertOk();
        $response->assertDontSee('name="date_paiement"', false);
        $response->assertDontSee('name="wave_number_used"', false);
        $response->assertDontSee('name="wave_reference"', false);
        $response->assertDontSee('name="ninea"', false);
    }

    public function test_le_formulaire_d_inscription_est_accessible(): void
    {
        $this->get('/inscription')
            ->assertOk()
            ->assertSee('Créer mon atelier', false);
    }

    public function test_une_inscription_cree_un_compte_en_attente_avec_son_atelier(): void
    {
        $this->post('/inscription', $this->payload());

        $user = User::where('email', 'fatou@example.test')->first();

        $this->assertNotNull($user);
        $this->assertSame('atelier', $user->role);
        $this->assertSame(UserStatus::EnAttente, $user->status);
        $this->assertSame('Atelier Diop', $user->atelier->nom);
        $this->assertFalse($user->hasValidatedAccess());
    }

    public function test_une_inscription_cree_une_demande_de_paiement_en_attente(): void
    {
        $this->post('/inscription', $this->payload());

        $user = User::where('email', 'fatou@example.test')->first();
        $subscription = Subscription::where('user_id', $user->id)->firstOrFail();

        $this->assertSame(UserStatus::EnAttente, $subscription->statut);

        // Le formulaire ne demande plus ces informations : elles restent
        // vides et la date de paiement est relevée automatiquement.
        $this->assertNull($subscription->wave_reference);
        $this->assertNull($subscription->wave_number_used);
        $this->assertSame(now()->toDateString(), $subscription->date_paiement->toDateString());
    }

    public function test_une_inscription_ignore_les_anciens_champs_de_paiement(): void
    {
        $this->post('/inscription', $this->payload([
            'date_paiement' => now()->subYear()->toDateString(),
            'wave_number_used' => '77 000 00 00',
            'wave_reference' => 'WAVE-123',
            'ninea' => '1234567890',
        ]));

        $user = User::where('email', 'fatou@example.test')->first();
        $subscription = Subscription::where('user_id', $user->id)->firstOrFail();

        $this->assertNull($subscription->wave_reference);
        $this->assertNull($subscription->wave_number_used);
        $this->assertNull($user->atelier->ninea);
        $this->assertSame(now()->toDateString(), $subscription->date_paiement->toDateString());
    }

    public function test_le_mot_de_passe_est_stocke_hache_et_non_en_clair(): void
    {
        $this->post('/inscription', $this->payload());

        $user = User::where('email', 'fatou@example.test')->first();

        $this->assertNotSame('motdepasse123', $user->password);
        $this->assertTrue(password_verify('motdepasse123', $user->password));
    }

    public function test_une_inscription_valide_redirige_vers_la_page_en_attente(): void
    {
        $this->post('/inscription', $this->payload())
            ->assertRedirect(route('pending'));
    }

    public function test_apres_inscription_l_utilisateur_est_connecte(): void
    {
        $this->post('/inscription', $this->payload());

        $this->assertAuthenticated();
    }

    public function test_un_email_deja_utilise_est_refuse(): void
    {
        $this->post('/inscription', $this->payload());

        // La première inscription connecte l'utilisateur : on referme la
        // session pour tester une seconde inscription depuis un navigateur vierge.
        $this->post('/deconnexion');

        $this->post('/inscription', $this->payload(['atelier_nom' => 'Autre atelier']))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_une_inscription_incomplete_est_refusee(): void
    {
        $this->post('/inscription', $this->payload([
            'nom' => '',
            'password' => 'court',
            'password_confirmation' => 'different',
        ]))->assertSessionHasErrors(['nom', 'password']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_les_conditions_doivent_etre_acceptees(): void
    {
        $this->post('/inscription', $this->payload(['conditions' => null]))
            ->assertSessionHasErrors('conditions');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_une_preuve_est_obligatoire_a_l_inscription(): void
    {
        $payload = $this->payload();
        unset($payload['preuve']);

        $this->post('/inscription', $payload)->assertSessionHasErrors('preuve');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_la_deconnexion_fonctionne(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Valide]);

        $this->actingAs($user)->post('/deconnexion')->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_la_connexion_ignore_la_casse_de_l_adresse_email(): void
    {
        $user = User::factory()->create([
            'email' => 'fatou@example.test',
            'password' => bcrypt('motdepasse123'),
            'status' => UserStatus::Valide,
        ]);

        // PostgreSQL distingue les majuscules, contrairement a MySQL : la
        // connexion doit normaliser l'adresse avant de la comparer.
        $this->post('/connexion', [
            'email' => 'Fatou@Example.Test',
            'password' => 'motdepasse123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_un_mauvais_mot_de_passe_est_toujours_refuse(): void
    {
        User::factory()->create([
            'email' => 'fatou@example.test',
            'password' => bcrypt('motdepasse123'),
            'status' => UserStatus::Valide,
        ]);

        $this->post('/connexion', [
            'email' => 'FATOU@example.test',
            'password' => 'mauvais-mot-de-passe',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_un_atelier_peut_deposer_une_preuve_de_paiement(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'status' => UserStatus::Valide,
            'role' => 'atelier',
        ]);

        Atelier::create(['user_id' => $user->id, 'nom' => 'Atelier Test']);

        $response = $this->actingAs($user->fresh())->post('/abonnement/renouvellement', [
            'date_paiement' => now()->toDateString(),
            'wave_number_used' => '77 000 00 00',
            'wave_reference' => 'WAVE-123',
            'preuve' => UploadedFile::fake()->image('recu.jpg'),
        ]);

        $response->assertRedirect(route('abonnement.index'));

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'wave_reference' => 'WAVE-123',
            'statut' => UserStatus::EnAttente->value,
        ]);
    }

    public function test_un_compte_bloque_peut_regulariser_en_deposant_une_preuve(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'status' => UserStatus::Bloque,
            'role' => 'atelier',
        ]);

        Atelier::create(['user_id' => $user->id, 'nom' => 'Atelier Bloque']);

        $this->actingAs($user->fresh())->post('/abonnement/renouvellement', [
            'date_paiement' => now()->toDateString(),
            'wave_number_used' => '77 000 00 00',
            'preuve' => UploadedFile::fake()->image('recu.jpg'),
        ])->assertRedirect(route('abonnement.index'));

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'statut' => UserStatus::EnAttente->value,
        ]);
    }

    public function test_une_preuve_est_exigee_pour_deposer_une_demande(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['status' => UserStatus::Valide, 'role' => 'atelier']);
        Atelier::create(['user_id' => $user->id, 'nom' => 'Atelier Test']);

        $this->actingAs($user->fresh())->post('/abonnement/renouvellement', [
            'date_paiement' => now()->toDateString(),
            'wave_number_used' => '77 000 00 00',
        ])->assertSessionHasErrors('preuve');

        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_un_administrateur_ne_peut_pas_deposer_d_abonnement(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => UserStatus::Valide,
        ]);

        $this->actingAs($admin)->post('/abonnement/renouvellement', [
            'date_paiement' => now()->toDateString(),
            'wave_number_used' => '77 000 00 00',
            'preuve' => UploadedFile::fake()->image('recu.jpg'),
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_la_page_abonnement_est_accessible_meme_a_un_compte_bloque(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Bloque, 'role' => 'atelier']);
        Atelier::create(['user_id' => $user->id, 'nom' => 'Atelier Bloque']);

        $this->actingAs($user->fresh())->get('/abonnement')->assertOk();
    }

    public function test_un_administrateur_peut_valider_un_paiement_et_ouvrir_l_acces(): void
    {
        Notification::fake();

        $user = User::factory()->create(['status' => UserStatus::EnAttente, 'role' => 'atelier']);
        Atelier::create(['user_id' => $user->id, 'nom' => 'Atelier Validation']);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'libelle' => 'Abonnement 1 mois',
            'montant' => 5000,
            'duree_mois' => 1,
            'statut' => UserStatus::EnAttente,
            'date_paiement' => now()->subDay(),
            'wave_number_used' => '77 000 00 00',
        ]);

        $admin = User::factory()->create(['role' => 'admin', 'status' => UserStatus::Valide]);

        $this->actingAs($admin)->patch('/admin/paiements/'.$subscription->id, [
            'statut' => UserStatus::Valide->value,
            'raison' => 'Paiement reçu et vérifié.',
        ])->assertRedirect(route('admin.paiements.index'));

        $subscription->refresh();
        $user->refresh();

        $this->assertSame(UserStatus::Valide, $subscription->statut);
        $this->assertSame(UserStatus::Valide, $user->status);
        $this->assertTrue($user->hasValidatedAccess());
        $this->assertNotNull($subscription->reviewed_at);
    }

    public function test_rejeter_un_paiement_exige_une_raison(): void
    {
        $user = User::factory()->create(['status' => UserStatus::EnAttente, 'role' => 'atelier']);
        Atelier::create(['user_id' => $user->id, 'nom' => 'Atelier Rejet']);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'libelle' => 'Abonnement 1 mois',
            'montant' => 5000,
            'duree_mois' => 1,
            'statut' => UserStatus::EnAttente,
            'date_paiement' => now()->subDay(),
            'wave_number_used' => '77 000 00 00',
        ]);

        $admin = User::factory()->create(['role' => 'admin', 'status' => UserStatus::Valide]);

        // Sans raison : la requête doit échouer.
        $this->actingAs($admin)->patch('/admin/paiements/'.$subscription->id, [
            'statut' => UserStatus::Rejete->value,
        ])->assertSessionHasErrors('raison');

        // Avec une raison : le compte est rejeté.
        $this->actingAs($admin)->patch('/admin/paiements/'.$subscription->id, [
            'statut' => UserStatus::Rejete->value,
            'raison' => 'Aucune preuve de paiement exploitable.',
        ])->assertRedirect(route('admin.paiements.index'));

        $this->assertSame(UserStatus::Rejete, $user->fresh()->status);
    }

    public function test_un_atelier_ne_peut_pas_revoir_son_propre_paiement(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Valide, 'role' => 'atelier']);
        Atelier::create(['user_id' => $user->id, 'nom' => 'Atelier Limite']);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'libelle' => 'Abonnement 1 mois',
            'montant' => 5000,
            'duree_mois' => 1,
            'statut' => UserStatus::EnAttente,
            'date_paiement' => now()->subDay(),
            'wave_number_used' => '77 000 00 00',
        ]);

        $this->actingAs($user->fresh())->patch('/admin/paiements/'.$subscription->id, [
            'statut' => UserStatus::Valide->value,
        ])->assertForbidden();
    }
}
