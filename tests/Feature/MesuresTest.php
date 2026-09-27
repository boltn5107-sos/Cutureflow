<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\Atelier;
use App\Models\Client;
use App\Models\Mesure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le formulaire ne propose plus des champs à remplir : le tailleur clique sur
 * l'icône d'une mesure du catalogue, et elle s'ajoute à son relevé. Aucune
 * mesure n'est pré-remplie ni obligatoire, et le code choisi détermine
 * l'intitulé et la zone du corps enregistrés.
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

    /**
     * Une ligne telle que le formulaire la produit : le libellé et le code
     * viennent du catalogue, la valeur est saisie par le tailleur.
     */
    private function mesure(string $code, string|int|float $valeur, string $unite = 'cm'): array
    {
        $entree = Mesure::entreeParCode($code);

        return [
            'code' => $code,
            'libelle' => $entree['libelle'] ?? '',
            'valeur' => $valeur,
            'unite' => $unite,
        ];
    }

    public function test_la_page_affiche_la_palette_des_icones_du_catalogue(): void
    {
        $this->actingAs($this->atelier)
            ->get(route('mesures.index', $this->client))
            ->assertOk()
            ->assertSee('Ajouter des mesures')
            ->assertSee('Mesures sélectionnées')
            ->assertSee('Choisissez les mesures que vous allez prendre');
    }

    public function test_le_catalogue_expose_les_trois_zones_du_corps(): void
    {
        $this->actingAs($this->atelier)
            ->get(route('mesures.index', $this->client))
            ->assertOk()
            ->assertSee('Haut du corps')
            ->assertSee('Longueurs')
            ->assertSee('Bas du corps');
    }

    public function test_le_catalogue_est_complet_et_sans_doublon(): void
    {
        $catalogue = Mesure::catalogue();

        $this->assertSame(['Haut du corps', 'Longueurs', 'Bas du corps'], array_keys($catalogue));

        $codes = [];
        $libelles = [];
        $icones = [];
        $total = 0;

        foreach ($catalogue as $entrees) {
            $total += count($entrees);

            foreach ($entrees as $entree) {
                $this->assertArrayHasKey('code', $entree);
                $this->assertArrayHasKey('libelle', $entree);
                $this->assertArrayHasKey('icone', $entree);
                $this->assertStringStartsWith('fa-', $entree['icone']);

                $codes[] = $entree['code'];
                $libelles[] = mb_strtolower($entree['libelle']);
                $icones[] = $entree['icone'];
            }
        }

        $this->assertSame(23, $total);
        $this->assertSame($codes, array_unique($codes));
        $this->assertSame($libelles, array_unique($libelles));
        $this->assertSame($icones, array_unique($icones));
    }

    public function test_les_mesures_selectionnees_sont_enregistrees_avec_leur_code_et_leur_zone(): void
    {
        $this->actingAs($this->atelier)->post(
            route('mesures.store', $this->client),
            $this->lignes([
                $this->mesure('tour_poitrine', 92),
                $this->mesure('tour_taille', 74),
                $this->mesure('tour_cuisse', 58),
                $this->mesure('entrejambe', 78),
            ])
        )->assertRedirect();

        $this->assertDatabaseCount('mesures', 4);

        $this->assertDatabaseHas('mesures', [
            'client_id' => $this->client->id,
            'code' => 'tour_poitrine',
            'libelle' => 'Tour de poitrine',
            'categorie' => 'Haut du corps',
            'unite' => 'cm',
        ]);

        $this->assertDatabaseHas('mesures', [
            'code' => 'tour_cuisse',
            'libelle' => 'Tour de cuisse',
            'categorie' => 'Bas du corps',
        ]);

        $this->assertDatabaseHas('mesures', [
            'code' => 'entrejambe',
            'libelle' => 'Entrejambe',
            'categorie' => 'Longueurs',
        ]);
    }

    public function test_le_libelle_et_la_zone_sont_deduits_du_code_et_non_du_formulaire(): void
    {
        $this->actingAs($this->atelier)->post(
            route('mesures.store', $this->client),
            $this->lignes([
                [
                    'code' => 'tour_poitrine',
                    // Un champ manipulé ne doit pas changer l'intitulé ni la zone.
                    'libelle' => 'Tour de hacked',
                    'valeur' => 92,
                    'unite' => 'cm',
                ],
            ])
        )->assertRedirect();

        $this->assertDatabaseHas('mesures', [
            'code' => 'tour_poitrine',
            'libelle' => 'Tour de poitrine',
            'categorie' => 'Haut du corps',
        ]);

        $this->assertDatabaseMissing('mesures', ['libelle' => 'Tour de hacked']);
    }

    public function test_une_mesure_hors_catalogue_est_acceptee_sans_zone(): void
    {
        $this->actingAs($this->atelier)->post(
            route('mesures.store', $this->client),
            $this->lignes([
                ['code' => null, 'libelle' => 'Encolure tailleur', 'valeur' => 38, 'unite' => 'cm'],
            ])
        )->assertRedirect();

        $this->assertDatabaseHas('mesures', [
            'client_id' => $this->client->id,
            'code' => null,
            'libelle' => 'Encolure tailleur',
            'categorie' => null,
        ]);
    }

    public function test_toute_la_poignee_de_mesures_du_catalogue_est_enregistrable(): void
    {
        $lignes = [];

        foreach (array_keys(Mesure::catalogueParCode()) as $code) {
            $lignes[] = $this->mesure($code, 50);
        }

        $this->actingAs($this->atelier)->post(
            route('mesures.store', $this->client),
            $this->lignes($lignes)
        )->assertRedirect();

        $this->assertDatabaseCount('mesures', 23);
        $this->assertSame(0, Mesure::whereNull('code')->count());
    }

    public function test_les_lignes_entierement_vides_sont_ignorees(): void
    {
        $this->actingAs($this->atelier)->post(
            route('mesures.store', $this->client),
            $this->lignes([
                $this->mesure('tour_poitrine', 92),
                ['code' => '', 'libelle' => '', 'valeur' => '', 'unite' => 'cm'],
                ['code' => '', 'libelle' => '', 'valeur' => '', 'unite' => 'cm'],
            ])
        )->assertRedirect();

        $this->assertDatabaseCount('mesures', 1);
    }

    public function test_une_mesure_choisie_sans_valeur_est_refusee(): void
    {
        $this->actingAs($this->atelier)->post(
            route('mesures.store', $this->client),
            $this->lignes([
                $this->mesure('tour_poitrine', 92),
                $this->mesure('tour_taille', ''),
            ])
        )->assertSessionHasErrors('mesures.1.valeur');

        $this->assertDatabaseCount('mesures', 0);
    }

    public function test_une_ligne_sans_intitule_est_refusee(): void
    {
        $this->actingAs($this->atelier)->post(
            route('mesures.store', $this->client),
            $this->lignes([
                ['code' => '', 'libelle' => '', 'valeur' => 92, 'unite' => 'cm'],
            ])
        )->assertSessionHasErrors('mesures.0.libelle');

        $this->assertDatabaseCount('mesures', 0);
    }

    public function test_enregistrer_sans_choisir_une_mesure_est_refuse(): void
    {
        $response = $this->actingAs($this->atelier)->post(
            route('mesures.store', $this->client),
            $this->lignes([])
        );

        $response->assertSessionHasErrors('mesures');

        $message = session('errors')->first('mesures');

        $this->assertSame('Choisissez au moins une mesure à enregistrer.', $message);
        $this->assertDatabaseCount('mesures', 0);
    }

    public function test_une_unite_inconnue_est_refusee(): void
    {
        $this->actingAs($this->atelier)->post(
            route('mesures.store', $this->client),
            $this->lignes([
                $this->mesure('tour_poitrine', 92, 'toise'),
            ])
        )->assertSessionHasErrors('mesures.0.unite');

        $this->assertDatabaseCount('mesures', 0);
    }

    public function test_une_date_dans_le_futur_est_refusee(): void
    {
        $this->actingAs($this->atelier)->post(
            route('mesures.store', $this->client),
            $this->lignes(
                [$this->mesure('tour_poitrine', 92)],
                ['date_mesure' => now()->addDay()->toDateString()],
            )
        )->assertSessionHasErrors('date_mesure');

        $this->assertDatabaseCount('mesures', 0);
    }

    public function test_une_mesure_peut_etre_rattachee_a_une_autre_entree_du_catalogue(): void
    {
        $mesure = Mesure::create([
            'client_id' => $this->client->id,
            'code' => 'tour_poitrine',
            'libelle' => 'Tour de poitrine',
            'categorie' => 'Haut du corps',
            'valeur' => 92,
            'unite' => 'cm',
            'date_mesure' => now()->toDateString(),
        ]);

        $this->actingAs($this->atelier)->put(
            route('mesures.update', $mesure),
            [
                'code' => 'tour_hanches',
                'valeur' => 98,
                'unite' => 'cm',
                'date_mesure' => now()->toDateString(),
            ]
        )->assertRedirect();

        $this->assertDatabaseHas('mesures', [
            'id' => $mesure->id,
            'code' => 'tour_hanches',
            'libelle' => 'Tour de hanches',
            'categorie' => 'Haut du corps',
            'valeur' => 98,
        ]);
    }

    public function test_une_mesure_hors_catalogue_reste_modifiable_en_clair(): void
    {
        $mesure = Mesure::create([
            'client_id' => $this->client->id,
            'code' => null,
            'libelle' => 'Épaule',
            'categorie' => 'Haut du corps',
            'valeur' => 41,
            'unite' => 'cm',
            'date_mesure' => now()->toDateString(),
        ]);

        $this->actingAs($this->atelier)->put(
            route('mesures.update', $mesure),
            [
                'code' => '',
                'libelle' => 'Épaule',
                'valeur' => 42,
                'unite' => 'cm',
                'date_mesure' => now()->toDateString(),
            ]
        )->assertRedirect();

        $this->assertDatabaseHas('mesures', [
            'id' => $mesure->id,
            'code' => null,
            'libelle' => 'Épaule',
            'valeur' => 42,
        ]);
    }

    public function test_une_mesure_hors_catalogue_affiche_une_icone_de_repli(): void
    {
        $mesure = new Mesure(['libelle' => 'Mesure inconnue']);

        $this->assertSame(Mesure::ICONE_REPLI, $mesure->icone());

        $connue = new Mesure(['code' => 'tour_poitrine', 'libelle' => 'Tour de poitrine']);

        $this->assertSame('fa-shirt', $connue->icone());
    }

    public function test_un_atelier_ne_peut_pas_ecrire_dans_les_mesures_d_un_autre(): void
    {
        $bob = User::factory()->create(['status' => UserStatus::Valide, 'role' => 'atelier']);
        Atelier::create(['user_id' => $bob->id, 'nom' => 'Atelier Bob']);

        $this->actingAs($bob)->post(
            route('mesures.store', $this->client),
            $this->lignes([
                ['code' => null, 'libelle' => 'Intrusion', 'valeur' => 1, 'unite' => 'cm'],
            ])
        )->assertForbidden();

        $this->assertDatabaseCount('mesures', 0);
    }
}
