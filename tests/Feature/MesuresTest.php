<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\Atelier;
use App\Models\Client;
use App\Models\Mesure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
     * La page de relevé servie, découpée en deux moitiés : la palette d'icônes,
     * puis tout ce qui suit le catalogue latéral. Les deux offrent les mêmes
     * commandes, il faut donc les séparer pour les compter séparément.
     *
     * @return array{0: string, 1: string}
     */
    private function paletteEtCatalogue(): array
    {
        $html = (string) $this->actingAs($this->atelier)
            ->get(route('mesures.index', $this->client))
            ->assertOk()
            ->getContent();

        $position = strpos($html, 'id="catalogue-mesures"');

        $this->assertNotFalse($position, 'Le catalogue latéral est introuvable.');

        return [substr($html, 0, $position), substr($html, $position)];
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

    public function test_la_fiche_client_resume_les_mesures_carte_par_releve(): void
    {
        // Deux séances de prise, une par date.
        $this->actingAs($this->atelier)->post(
            route('mesures.store', $this->client),
            $this->lignes([
                $this->mesure('tour_poitrine', 92),
                $this->mesure('fourche_devant', 27),
            ], ['date_mesure' => now()->subWeek()->toDateString()])
        )->assertRedirect();

        $this->actingAs($this->atelier)->post(
            route('mesures.store', $this->client),
            $this->lignes([$this->mesure('tour_taille', 74)])
        )->assertRedirect();

        $html = (string) $this->actingAs($this->atelier)
            ->get(route('clients.show', $this->client))
            ->assertOk()
            ->getContent();

        // Une seule section « Résumé des mesures », qui inaugure l'histoire.
        $this->assertSame(1, substr_count($html, 'data-mesures-resume'));
        $this->assertSame(1, substr_count($html, 'Résumé des mesures'));

        // ... et une carte par jour de prise.
        $this->assertStringContainsString('Relevé du '.now()->format('d/m/Y'), $html);
        $this->assertStringContainsString('Relevé du '.now()->subWeek()->format('d/m/Y'), $html);

        // Chaque carte porte ses propres mesures.
        $this->assertStringContainsString('Tour de poitrine', $html);
        $this->assertStringContainsString('92', $html);
        $this->assertStringContainsString('Fourche devant', $html);
        $this->assertStringContainsString('Tour de taille', $html);
        $this->assertStringContainsString('74', $html);
    }

    public function test_les_releves_se_groupent_par_date_de_prise(): void
    {
        $ceMois = now()->toDateString();
        $moisDernier = now()->subMonth()->toDateString();

        $this->actingAs($this->atelier)->post(
            route('mesures.store', $this->client),
            $this->lignes([$this->mesure('tour_poitrine', 90)], ['date_mesure' => $moisDernier])
        )->assertRedirect();

        $this->actingAs($this->atelier)->post(
            route('mesures.store', $this->client),
            $this->lignes([
                $this->mesure('tour_poitrine', 94),
                $this->mesure('tour_taille', 76),
            ])
        )->assertRedirect();

        // Trois valeurs en base : deux jours de prise, donc deux relevés.
        $this->assertDatabaseCount('mesures', 3);

        $releves = $this->client->relevesMesures();

        $this->assertCount(2, $releves);
        $this->assertCount(1, $releves->get($moisDernier));
        $this->assertCount(2, $releves->get($ceMois));

        // L'historique se lit du relevé le plus récent au plus ancien.
        $this->assertSame($ceMois, $releves->keys()->first());
    }

    public function test_une_mesure_retiree_du_catalogue_reste_affichee_avec_son_icone_de_repli(): void
    {
        /*
         * Retirer une mesure du catalogue ne doit pas effacer l'historique :
         * un atelier qui a noté un tour de mollet doit continuer à le lire sur
         * la fiche, avec une icône générique.
         */
        Mesure::create([
            'client_id' => $this->client->id,
            'code' => 'tour_mollet',
            'libelle' => 'Tour de mollet',
            'categorie' => 'Bas du corps',
            'valeur' => 36,
            'unite' => 'cm',
            'date_mesure' => now()->toDateString(),
        ]);

        $releve = Mesure::where('code', 'tour_mollet')->firstOrFail();

        $html = (string) $this->actingAs($this->atelier)
            ->get(route('clients.show', $this->client))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Tour de mollet', $html);
        $this->assertStringContainsString('36', $html);

        // Le code n'est plus au catalogue : icone() doit retomber sur son
        // repli, sans laisser la tuile vide.
        $this->assertStringContainsString($releve->icone(), $html);
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

    public function test_la_palette_est_une_grille_plate_sans_intitule_de_zone(): void
    {
        $response = $this->actingAs($this->atelier)->get(route('mesures.index', $this->client));

        $response->assertOk();

        $html = (string) $response->getContent();

        /*
         * Les tuiles sont dans une grille unique : plus de <fieldset> ni de
         * <legend> par zone, qui alourdissaient la lecture sur téléphone. Le
         * total des tuiles reste bien celui du catalogue.
         */
        $this->assertStringNotContainsString('<fieldset', $html);
        $this->assertStringNotContainsString('<legend', $html);

        // Le code est un littéral dans la palette, une variable dans le
        // bouton « Retirer » : le guillemet droit les distingue.
        [$palette] = $this->paletteEtCatalogue();

        $this->assertSame(count(Mesure::catalogueParCode()), substr_count($palette, 'x-on:click="basculer(\''));
    }

    public function test_le_regroupement_par_zone_est_dans_un_encart_repliable(): void
    {
        $this->actingAs($this->atelier)
            ->get(route('mesures.index', $this->client))
            ->assertOk()
            ->assertSee('Catalogue des mesures')
            ->assertSee('aria-controls="catalogue-mesures"', false)
            ->assertSee('x-bind:aria-expanded', false)
            ->assertSee('x-data="{ ouvert: false }"', false)
            ->assertSee('Haut du corps')
            ->assertSee('Bas du corps')
            ->assertDontSee('Longueurs');
    }

    public function test_chaque_tuile_ajoute_la_mesure_et_amene_le_curseur_sur_son_champ(): void
    {
        $html = (string) $this->actingAs($this->atelier)
            ->get(route('mesures.index', $this->client))
            ->assertOk()
            ->getContent();

        /*
         * La palette a une ancre, et chaque tuile ne fait qu'appeler basculer() :
         * c'est le composant Alpine qui ajoute la ligne puis amène le curseur
         * sur son champ. Une ancre posée dans le HTML cherchait un id
         * « valeur-<code> » alors que les champs portent « valeur-<index> »,
         * et n'aurait jamais trouvé sa cible.
         */
        $this->assertStringContainsString('id="palette-mesures"', $html);
        $this->assertStringNotContainsString('getElementById', $html);

        [$palette] = $this->paletteEtCatalogue();

        $this->assertSame(count(Mesure::catalogueParCode()), substr_count($palette, 'x-on:click="basculer(\''));
    }

    public function test_un_bouton_renvoie_vers_la_palette_apres_la_derniere_mesure(): void
    {
        $html = (string) $this->actingAs($this->atelier)
            ->get(route('mesures.index', $this->client))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('x-on:click="allerAuxIcones()"', $html);
        $this->assertStringContainsString('Autre mesure', $html);
    }

    public function test_le_bouton_enregistrer_et_annuler_suivent_le_commentaire(): void
    {
        $html = (string) $this->actingAs($this->atelier)
            ->get(route('mesures.index', $this->client))
            ->assertOk()
            ->getContent();

        /*
         * Le bouton d'enregistrement vit dans le formulaire, juste sous le
         * commentaire : c'est là que l'on terminé sa saisie. Plus de carte
         * « Enregistrer » dans la barre latérale qui faisait balayer la page.
         */
        $this->assertStringContainsString('id="form-mesures"', $html);
        $this->assertStringNotContainsString('form="form-mesures"', $html);
        $this->assertStringContainsString('Enregistrer les ', $html);

        $commentaire = (int) strpos($html, 'id="nouvelle-mesure-commentaire"');
        preg_match(
            '/<button\s+type="submit"/',
            $html,
            $bouton,
            PREG_OFFSET_CAPTURE,
            $commentaire
        );

        // Le bouton d'enregistrement est bien après le commentaire, dedans le <form>.
        $this->assertSame(1, preg_match('/<button\s+type="submit"/u', $html), 'Le formulaire devrait avoir un bouton d\'enregistrement.');
        $this->assertNotSame([], $bouton, 'Le bouton d\'enregistrement devrait suivre le commentaire.');
        $this->assertLessThan(
            (int) strpos($html, '</form>', $commentaire),
            $bouton[0][1],
            'Le bouton d\'enregistrement devrait être dans le formulaire.',
        );

        // L'annulation vide la sélection et remonte à la palette.
        $this->assertStringContainsString('x-on:click="vider()"', $html);
        $this->assertStringContainsString('Annuler', $html);
    }

    public function test_le_releve_des_mesures_separe_les_dates_par_carte(): void
    {
        $ilYaUnMois = now()->subMonth()->toDateString();

        $this->actingAs($this->atelier)->post(
            route('mesures.store', $this->client),
            $this->lignes([
                $this->mesure('tour_poitrine', 92),
                $this->mesure('tour_taille', 74),
            ])
        )->assertRedirect();

        $this->actingAs($this->atelier)->post(
            route('mesures.store', $this->client),
            $this->lignes([$this->mesure('fourche_devant', 27)], ['date_mesure' => $ilYaUnMois])
        )->assertRedirect();

        $html = (string) $this->actingAs($this->atelier)
            ->get(route('mesures.index', $this->client))
            ->assertOk()
            ->getContent();

        // La section permanent remplace le reçu éphémère et garde son nom.
        $this->assertStringContainsString('Relevé des mesures', $html);
        $this->assertStringNotContainsString('Relevé enregistré', $html);

        // Une carte par jour de prise, la plus récente en premier.
        $dateRecente = now()->format('d/m/Y');
        $dateAncienne = Carbon::parse($ilYaUnMois)->format('d/m/Y');

        $this->assertStringContainsString('Relevé du '.$dateRecente, $html);
        $this->assertStringContainsString('Relevé du '.$dateAncienne, $html);
        $this->assertLessThan(
            strpos($html, 'Relevé du '.$dateAncienne),
            strpos($html, 'Relevé du '.$dateRecente),
            'Le relevé le plus récent devrait être affiché en premier.',
        );

        // Chaque carte détaille ses mesures.
        $this->assertStringContainsString('Tour de poitrine', $html);
        $this->assertStringContainsString('92 cm', $html);
        $this->assertStringContainsString('Fourche devant', $html);
        $this->assertStringContainsString('27 cm', $html);
    }

    public function test_chaque_mesure_du_releve_garde_modifier_et_supprimer(): void
    {
        $this->actingAs($this->atelier)->post(
            route('mesures.store', $this->client),
            $this->lignes([$this->mesure('tour_poitrine', 92)])
        )->assertRedirect();

        $mesure = $this->client->mesures()->firstOrFail();

        $html = (string) $this->actingAs($this->atelier)
            ->get(route('mesures.index', $this->client))
            ->assertOk()
            ->getContent();

        // L'édition en ligne et la suppression restent accessibles, ligne par ligne.
        $this->assertStringContainsString(route('mesures.update', $mesure), $html);
        $this->assertStringContainsString(route('mesures.destroy', $mesure), $html);
        $this->assertStringContainsString('x-on:click="editing = !editing"', $html);
    }

    public function test_un_enregistrement_ne_montre_plus_de_recu_ephemere(): void
    {
        $this->actingAs($this->atelier)
            ->post(
                route('mesures.store', $this->client),
                $this->lignes([$this->mesure('tour_poitrine', 92)])
            )
            ->assertRedirect()
            ->assertSessionMissing('releve');

        // La prise retombe sur le carnet permanent, sans flash éphémère.
        $html = (string) $this->actingAs($this->atelier)
            ->get(route('mesures.index', $this->client))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Relevé enregistré', $html);
    }

    public function test_le_catalogue_lateral_propose_les_memes_commandes_que_la_palette(): void
    {
        $html = (string) $this->actingAs($this->atelier)
            ->get(route('mesures.index', $this->client))
            ->assertOk()
            ->getContent();

        /*
         * Le catalogue latéral est cliquable : autant d'entrées que de
         * tuiles, qui appellent le même basculer() et reflètent l'état
         * sélectionné. On compte chaque moitié séparément, le total des deux
         * ferait 28.
         */
        [$palette, $catalogue] = $this->paletteEtCatalogue();

        $this->assertSame(count(Mesure::catalogueParCode()), substr_count($catalogue, 'x-on:click="basculer(\''));
        $this->assertSame(count(Mesure::catalogueParCode()), substr_count($catalogue, 'x-bind:aria-pressed="contient('));
        $this->assertSame(count(Mesure::catalogueParCode()), substr_count($palette, 'x-on:click="basculer(\''));
        $this->assertSame(count(Mesure::catalogueParCode()), substr_count($palette, 'x-bind:aria-pressed="contient('));
    }

    public function test_le_catalogue_est_complet_et_sans_doublon(): void
    {
        $catalogue = Mesure::catalogue();

        $this->assertSame(['Haut du corps', 'Bas du corps'], array_keys($catalogue));

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

        $this->assertSame(count(Mesure::catalogueParCode()), $total);
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
                $this->mesure('fourche_devant', 27),
                $this->mesure('fourche_dos', 30),
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
            'code' => 'fourche_devant',
            'libelle' => 'Fourche devant',
            'categorie' => 'Bas du corps',
        ]);
    }

    public function test_le_catalogue_ne_propose_plus_les_mesures_retirees(): void
    {
        $codes = array_keys(Mesure::catalogueParCode());

        /*
         * Les mesures retirées ne doivent plus être proposées à la saisie.
         * Les relevés déjà enregistrés gardent leur code en base et restent
         * consultables : c'est le catalogue, pas la table, qui a été réduit.
         */
        $retirees = [
            'carrure',
            'tour_cheville',
            'tour_cuisse',
            'longueur_chemise',
            'longueur_veste',
            'longueur_robe',
            'longueur_jupe',
            'longueur_pantalon',
            'entrejambe',
            'tour_bras',
            'tour_genou',
            'tour_mollet',
        ];

        foreach ($retirees as $code) {
            $this->assertNotContains($code, $codes);
        }

        $this->assertSame([
            'hauteur_corps',
            'tour_poitrine',
            'tour_taille',
            'tour_hanches',
            'largeur_epaules',
            'tour_cou',
            'longueur_epaule',
            'longueur_manche',
            'tour_poignet',
            'fourche_devant',
            'fourche_dos',
        ], $codes);
    }

    public function test_chaque_icone_du_catalogue_existe_dans_le_font_awesome_installe(): void
    {
        $css = (string) file_get_contents(
            base_path('node_modules/@fortawesome/fontawesome-free/css/fontawesome.css')
        );

        /*
         * Font Awesome 7 déclare chaque icône par une variable --fa
         * (et non plus par un contenu :before) : une classe absente du
         * fichier s'afficherait en carré vide, silencieusement.
         */
        foreach (Mesure::catalogueParCode() as $entree) {
            $this->assertMatchesRegularExpression(
                '/\.'.preg_quote($entree['icone'], '/').'\s*\{\s*--fa:/',
                $css,
                "L'icône {$entree['icone']} ({$entree['libelle']}) est absente du CSS installé."
            );
        }
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

        $this->assertDatabaseCount('mesures', count(Mesure::catalogueParCode()));
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
