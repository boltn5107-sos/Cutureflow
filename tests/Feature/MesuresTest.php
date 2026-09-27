<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\Atelier;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le formulaire propose quatre mesures à l'ouverture. Le tailleur peut les
 * renommer librement, en ajouter d'autres, et laisser des lignes vides.
 */
class MesuresTest extends TestCase
{
    use RefreshDatabase;

    private User $atelier;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->atelier = User::factory()->create([
            'status' => UserStatus::Valide,
            'role' => 'atelier',
        ]);

        Atelier::create(['user_id' => $this->atelier->id, 'nom' => 'Atelier Mesures']);

        $this->client = Client::create([
            'atelier_id' => $this->atelier->atelier->id,
            'nom' => 'Awa Ndiaye',
            'telephone' => '77 000 00 00',
        ]);
    }

    private function lignes(array $mesures, array $extra = []): array
    {
        return array_merge([
            'mesures' => $mesures,
            'date_mesure' => now()->toDateString(),
        ], $extra);
    }

    public function test_la_page_propose_quatre_mesures_modifiables(): void
    {
        $this->actingAs($this->atelier)
            ->get(route('mesures.index', $this->client))
            ->assertOk()
            ->assertSee('Ajouter une mesure', false)
            ->assertSee('mesures[', false);
    }

    public function test_quatre_mesures_sont_enregistrees_en_une_fois(): void
    {
        $this->actingAs($this->atelier)->post(
            route('mesures.store', $this->client),
            $this->lignes([
                ['libelle' => 'Tour de poitrine', 'valeur' => 92, 'unite' => 'cm'],
                ['libelle' => 'Tour de taille', 'valeur' => 74, 'unite' => 'cm'],
                ['libelle' => 'Tour de hanches', 'valeur' => 98, 'unite' => 'cm'],
                ['libelle' => 'Longueur totale', 'valeur' => 142, 'unite' => 'cm'],
            ])
        )->assertRedirect();

        $this->assertDatabaseCount('mesures', 4);
        $this->assertDatabaseHas('mesures', [
            'client_id' => $this->client->id,
            'libelle' => 'Tour de poitrine',
            'unite' => 'cm',
        ]);
    }

    public function test_le_tailleur_peut_renommer_les_mesures_proposees(): void
    {
        $this->actingAs($this->atelier)->post(
            route('mesures.store', $this->client),
            $this->lignes([
                ['libelle' => 'Encoluretailleur', 'valeur' => 38, 'unite' => 'cm'],
                ['libelle' => 'Buste', 'valeur' => 88, 'unite' => 'cm'],
                ['libelle' => 'Mesure personalisee 3', 'valeur' => 61, 'unite' => 'cm'],
                ['libelle' => 'Mesure personalisee 4', 'valeur' => 70, 'unite' => 'cm'],
            ])
        )->assertRedirect();

        $this->assertDatabaseHas('mesures', ['libelle' => 'Encoluretailleur', 'valeur' => 38]);
        $this->assertDatabaseHas('mesures', ['libelle' => 'Buste', 'valeur' => 88]);
    }

    public function test_le_tailleur_peut_ajouter_plus_de_quatre_mesures(): void
    {
        $lignes = [];

        foreach (range(1, 6) as $index) {
            $lignes[] = ['libelle' => 'Mesure '.$index, 'valeur' => 10 * $index, 'unite' => 'cm'];
        }

        $this->actingAs($this->atelier)->post(
            route('mesures.store', $this->client),
            $this->lignes($lignes)
        )->assertRedirect();

        $this->assertDatabaseCount('mesures', 6);
    }

    public function test_les_lignes_entierement_vides_sont_ignorees(): void
    {
        $this->actingAs($this->atelier)->post(
            route('mesures.store', $this->client),
            $this->lignes([
                ['libelle' => 'Tour de poitrine', 'valeur' => 92, 'unite' => 'cm'],
                ['libelle' => '', 'valeur' => '', 'unite' => 'cm'],
                ['libelle' => '', 'valeur' => '', 'unite' => 'cm'],
            ])
        )->assertRedirect();

        $this->assertDatabaseCount('mesures', 1);
    }

    public function test_une_ligne_sans_valeur_est_refusee(): void
    {
        $this->actingAs($this->atelier)->post(
            route('mesures.store', $this->client),
            $this->lignes([
                ['libelle' => 'Tour de poitrine', 'valeur' => 92, 'unite' => 'cm'],
                ['libelle' => 'Tour de taille', 'valeur' => '', 'unite' => 'cm'],
            ])
        )->assertSessionHasErrors('mesures.1.valeur');

        $this->assertDatabaseCount('mesures', 0);
    }

    public function test_une_ligne_sans_intitule_est_refusee(): void
    {
        $this->actingAs($this->atelier)->post(
            route('mesures.store', $this->client),
            $this->lignes([
                ['libelle' => '', 'valeur' => 92, 'unite' => 'cm'],
            ])
        )->assertSessionHasErrors('mesures.0.libelle');

        $this->assertDatabaseCount('mesures', 0);
    }

    public function test_une_erreur_de_validation_est_redigee_en_francais(): void
    {
        $response = $this->actingAs($this->atelier)->post(
            route('mesures.store', $this->client),
            $this->lignes([['libelle' => '', 'valeur' => '']])
        );

        $response->assertSessionHasErrors('mesures');

        $message = session('errors')->first('mesures');

        $this->assertSame('Saisissez au moins une mesure.', $message);
    }

    public function test_la_categorie_courante_est_ajoutee_automatiquement(): void
    {
        $this->actingAs($this->atelier)->post(
            route('mesures.store', $this->client),
            $this->lignes([
                ['libelle' => 'Tour de hanches', 'valeur' => 98, 'unite' => 'cm'],
            ])
        )->assertRedirect();

        $this->assertDatabaseHas('mesures', [
            'libelle' => 'Tour de hanches',
            'categorie' => 'Haut du corps',
        ]);
    }

    public function test_un_atelier_ne_peut_pas_ecrire_dans_les_mesures_d_un_autre(): void
    {
        $bob = User::factory()->create(['status' => UserStatus::Valide, 'role' => 'atelier']);
        Atelier::create(['user_id' => $bob->id, 'nom' => 'Atelier Bob']);

        $this->actingAs($bob)->post(
            route('mesures.store', $this->client),
            $this->lignes([['libelle' => 'Intrusion', 'valeur' => 1, 'unite' => 'cm']])
        )->assertForbidden();

        $this->assertDatabaseCount('mesures', 0);
    }
}
