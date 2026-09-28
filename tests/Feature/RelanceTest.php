<?php

namespace Tests\Feature;

use App\Enums\CommandeStatut;
use App\Enums\UserStatus;
use App\Models\Atelier;
use App\Models\Client;
use App\Models\ClientRelance;
use App\Models\Commande;
use App\Models\Mesure;
use App\Models\User;
use App\Services\RelanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RelanceTest extends TestCase
{
    use RefreshDatabase;

    private User $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alice = $this->makeAtelier('Atelier Alice');
    }

    public function test_un_visiteur_est_renvoye_vers_la_connexion(): void
    {
        $this->get(route('relances.index'))->assertRedirect(route('login'));
    }

    public function test_la_page_liste_les_pieces_pretes_non_retirees(): void
    {
        $client = $this->client();

        // La pièce est « prête » depuis plus de sept jours. Passé par la
        // base : lecture seule sur le modèle, rien ne serait persisté.
        $this->commande($client, ['statut' => CommandeStatut::Prete->value]);
        $this->figerStatut($client, Carbon::now()->subDays(10));

        $this->actingAs($this->alice)
            ->get(route('relances.index'))
            ->assertOk()
            ->assertSee($client->nom)
            ->assertSee('Pièces prêtes non retirées');
    }

    public function test_une_piece_prete_de_vene_n_est_pas_proposee(): void
    {
        $client = $this->client();

        $this->commande($client, ['statut' => CommandeStatut::Prete->value]);
        $this->figerStatut($client, Carbon::now()->subDay());

        $this->actingAs($this->alice)
            ->get(route('relances.index'))
            ->assertOk()
            ->assertDontSee('Pièces prêtes non retirées');
    }

    public function test_les_clients_avec_mesures_sans_commande_sont_proposes(): void
    {
        $client = $this->client();
        $this->mesure($client, Carbon::now()->subDays(30));

        $this->actingAs($this->alice)
            ->get(route('relances.index'))
            ->assertOk()
            ->assertSee('Mesures relevées, aucune commande')
            ->assertSee($client->nom);
    }

    public function test_un_client_avec_mesures_et_commande_n_est_pas_proposee(): void
    {
        $client = $this->client();
        $this->commande($client);

        $this->mesure($client, Carbon::now()->subDays(30));

        $this->actingAs($this->alice)
            ->get(route('relances.index'))
            ->assertOk()
            ->assertDontSee('Mesures relevées, aucune commande');
    }

    public function test_une_commande_annulee_ne_compte_pour_l_inactivite(): void
    {
        $client = $this->client();
        $commande = $this->commande($client, [
            'statut' => CommandeStatut::Annulee->value,
            'date_commande' => Carbon::now()->subDays(200),
        ]);

        $this->actingAs($this->alice)
            ->get(route('relances.index'))
            ->assertOk()
            ->assertDontSee($client->nom);
    }

    public function test_le_client_inactif_depend_du_filtre(): void
    {
        /*
         * « Inactif depuis N jours » = aucune commande depuis N jours. La
         * fenêtre est donc un seuil de qualification : plus elle est large,
         * plus la liste est courte. Un client commandé il y a 45 jours est
         * inactif depuis 30 jours, pas encore depuis 60.
         */
        $recent = $this->client(nom: 'Awa Recente');
        $this->commande($recent, ['date_commande' => Carbon::now()->subDays(45)]);

        $ancien = $this->client(nom: 'Awa Ancienne');
        $this->commande($ancien, ['date_commande' => Carbon::now()->subDays(100)]);

        // Fenêtre courte : le client de 45 jours y entre, celui de 100 aussi.
        $this->actingAs($this->alice)
            ->get(route('relances.index', ['jours' => 30]))
            ->assertOk()
            ->assertSee($recent->nom)
            ->assertSee($ancien->nom);

        // Fenêtre large : seul le client de 100 jours est vraiment inactif.
        $this->actingAs($this->alice)
            ->get(route('relances.index', ['jours' => 60]))
            ->assertOk()
            ->assertDontSee($recent->nom)
            ->assertSee($ancien->nom);
    }

    public function test_une_valeur_de_filtre_aberrante_est_bornee(): void
    {
        $this->actingAs($this->alice)
            ->get(route('relances.index', ['jours' => 0]))
            ->assertOk();
    }

    public function test_un_client_relance_disparait_de_la_liste(): void
    {
        $client = $this->client();
        $this->commande($client, [
            'statut' => CommandeStatut::Prete->value,
            'date_commande' => Carbon::now()->subDays(200),
        ]);
        $this->figerStatut($client, Carbon::now()->subDays(10));

        $this->actingAs($this->alice)
            ->get(route('relances.index'))
            ->assertOk()
            ->assertSee($client->nom);

        $this->actingAs($this->alice)
            ->post(route('relances.store'), ['client_id' => $client->id, 'motif' => 'pretes'])
            ->assertRedirect();

        $this->assertDatabaseHas('client_relances', ['client_id' => $client->id]);

        $this->actingAs($this->alice)
            ->get(route('relances.index'))
            ->assertOk()
            ->assertDontSee($client->nom);
    }

    public function test_un_client_relance_deux_fois_n_est_duplique_pas(): void
    {
        $client = $this->client();

        ClientRelance::create([
            'client_id' => $client->id,
            'atelier_id' => $client->atelier_id,
            'user_id' => $this->alice->id,
            'motif' => 'manuel',
        ]);

        $this->actingAs($this->alice)
            ->post(route('relances.store'), ['client_id' => $client->id])
            ->assertRedirect();

        $this->assertSame(2, ClientRelance::where('client_id', $client->id)->count());
    }

    public function test_on_ne_peut_pas_relancer_un_client_d_un_autre_atelier(): void
    {
        $autre = $this->makeAtelier('Atelier Autre');
        $clientEtranger = $this->client($autre);

        $this->actingAs($this->alice)
            ->post(route('relances.store'), ['client_id' => $clientEtranger->id])
            ->assertSessionHasErrors('client_id');

        $this->assertDatabaseCount('client_relances', 0);
    }

    public function test_les_relances_ne_fuient_pas_vers_un_autre_atelier(): void
    {
        $autre = $this->makeAtelier('Atelier Autre');
        $clientEtranger = $this->client($autre);

        $this->commande($clientEtranger, ['statut' => CommandeStatut::Prete->value]);
        $this->figerStatut($clientEtranger, Carbon::now()->subDays(10));

        $this->actingAs($this->alice)
            ->get(route('relances.index'))
            ->assertOk()
            ->assertDontSee($clientEtranger->nom);
    }

    public function test_un_administrateur_est_refuse(): void
    {
        $admin = User::factory()->create([
            'status' => UserStatus::Valide,
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->post(route('relances.store'), ['client_id' => $this->client()->id])
            ->assertForbidden();
    }

    public function test_le_tableau_de_bord_annonce_le_nombre_de_relances(): void
    {
        config(['coutureflow.relances_actives' => true]);

        $client = $this->client();
        $this->commande($client, ['statut' => CommandeStatut::Prete->value]);
        $this->figerStatut($client, Carbon::now()->subDays(10));

        $this->actingAs($this->alice)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('1 client(s) à relancer');
    }

    public function test_les_relances_sont_masquees_tant_qu_elles_ne_sont_pas_activees(): void
    {
        $client = $this->client();
        $this->commande($client, ['statut' => CommandeStatut::Prete->value]);
        $this->figerStatut($client, Carbon::now()->subDays(10));

        // Par défaut la fonction est masquée : ni dans le menu, ni en
        // bandeau, même quand des clients seraient à relancer.
        $this->actingAs($this->alice)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('relances.index'))
            ->assertDontSee('client(s) à relancer');

        // Le lien de navigation suit le même drapeau.
        $html = (string) $this->actingAs($this->alice)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Relances', $html);

        config(['coutureflow.relances_actives' => true]);

        $this->actingAs($this->alice)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Relances');
    }

    public function test_le_service_classe_un_client_par_un_seul_motif(): void
    {
        $client = $this->client();

        // Prête depuis longtemps ET client inactif : il ne doit apparaître
        // qu'une fois, dans le groupe le plus urgent.
        $this->commande($client, [
            'statut' => CommandeStatut::Prete->value,
            'date_commande' => Carbon::now()->subDays(200),
        ]);
        $this->figerStatut($client, Carbon::now()->subDays(10));

        $this->mesure($client, Carbon::now()->subDays(30));

        $groupes = RelanceService::for($this->alice)->suggestions();

        $ids = collect($groupes)->flatMap(fn (array $g) => $g['clients']->pluck('client_id'));

        $this->assertSame(1, $ids->filter(fn ($id) => $id === $client->id)->count());
        $this->assertSame(1, RelanceService::for($this->alice)->compteur());
    }

    public function test_la_page_ne_mentionne_aucun_montant(): void
    {
        $client = $this->client();
        $this->commande($client, [
            'statut' => CommandeStatut::Prete->value,
            'prix_total' => 125000,
        ]);
        $this->figerStatut($client, Carbon::now()->subDays(10));

        $contenu = $this->actingAs($this->alice)
            ->get(route('relances.index'))
            ->assertOk()
            ->getContent();

        // L'application ne gère pas de transaction client : la page ne doit
        // afficher ni montant total, ni solde restant.
        $this->assertStringNotContainsString('125000', $contenu);
        $this->assertStringNotContainsString('Reste à payer', $contenu);
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

    private function client(?User $user = null, string $nom = 'Awa Diop'): Client
    {
        return Client::create([
            'atelier_id' => ($user ?? $this->alice)->atelierId(),
            'nom' => $nom,
            'telephone' => '77 000 11 22',
        ]);
    }

    private function commande(Client $client, array $attributes = []): Commande
    {
        return Commande::create(array_merge([
            'atelier_id' => $client->atelier_id,
            'client_id' => $client->id,
            'modele_id' => null,
            'numero' => Commande::genererNumero($client->atelier_id),
            'date_commande' => now(),
            'statut' => CommandeStatut::EnAttente->value,
            'prix_total' => 0,
            'avance' => 0,
            'solde' => 0,
            'creator_id' => $this->alice->id,
        ], $attributes));
    }

    /**
     * Recule la date de dernier changement des commandes d'un client : c'est
     * updated_at qui dit depuis quand une pièce est « prête ».
     */
    private function figerStatut(Client $client, Carbon $quand): void
    {
        Commande::where('client_id', $client->id)->update(['updated_at' => $quand]);
    }

    private function mesure(Client $client, ?Carbon $date = null): Mesure
    {
        return Mesure::create([
            'client_id' => $client->id,
            'commande_id' => null,
            'code' => 'tour_poitrine',
            'libelle' => 'Tour de poitrine',
            'categorie' => 'Haut du corps',
            'valeur' => 92,
            'unite' => 'cm',
            'date_mesure' => ($date ?? now())->toDateString(),
        ]);
    }
}
