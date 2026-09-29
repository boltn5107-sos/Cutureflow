<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\Atelier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le rappel d'installation de l'application (PWA) doit revenir à chaque
 * ouverture tant que l'installation n'est pas menée à bien, et les messages
 * de notification doivent s'ouvrir dans un popup central plutôt que dans
 * des bannières fugaces en coin.
 */
class PwaRappelTest extends TestCase
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
            'nom' => 'Atelier Pwa',
            'ville' => 'Dakar',
        ]);

        $this->atelier = $usager->fresh();
    }

    public function test_le_rappel_d_installation_revient_a_chaque_ouverture_tant_que_l_application_n_est_pas_installee(): void
    {
        $this->actingAs($this->atelier);

        $this->get('/tableau-de-bord')
            ->assertOk()
            ->assertSee('data-installation-prompt', false)
            ->assertSee('Installer Couture Flow');

        // Une autre page ouverte plus tard retrouve le même rappel : rien
        // n'est mémorisé d'une session à l'autre tant que ce n'est pas fait.
        $this->get('/clients')
            ->assertOk()
            ->assertSee('data-installation-prompt', false)
            ->assertSee('Plus tard', false);
    }

    public function test_les_etapes_d_installation_ios_sont_preposees_en_popup(): void
    {
        $this->actingAs($this->atelier);

        $this->get('/tableau-de-bord')
            ->assertOk()
            ->assertSee('data-installation-etapes', false)
            ->assertSee('Sur l\'écran d\'accueil', false);
    }

    public function test_les_messages_de_notification_s_ouvrent_dans_un_popup_central(): void
    {
        $this->actingAs($this->atelier);

        $this->get('/tableau-de-bord')
            ->assertOk()
            ->assertSee('data-notifications-popup', false)
            ->assertSee('$store.notifications.messages', false)
            // Les anciennes bannières fugaces en coin ont disparu.
            ->assertDontSee('pointer-events-none fixed top-20 right-4');
    }
}
