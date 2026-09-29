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
 * de notification doivent s'ouvrir dans un toast en haut à droite plutôt que
 * dans des bannières fugaces en coin.
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

    public function test_les_messages_de_notification_s_ouvrent_dans_un_toast_en_haut_a_droite(): void
    {
        $html = (string) $this->actingAs($this->atelier)
            ->get('/tableau-de-bord')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-notifications-popup', $html);
        $this->assertStringContainsString('$store.notifications.messages', $html);

        // Le toast glisse depuis la droite, sous l'en-tête.
        $this->assertStringContainsString('fixed top-20 right-4', $html);

        // Les anciens popups centraux en pleine page ont disparu : le conteneur
        // du toast n'a plus rien d'un calque plein écran centré.
        $this->assertDoesNotMatchRegularExpression('/inset-0.{0,80}data-notifications-popup/', $html);
        $this->assertDoesNotMatchRegularExpression('/data-notifications-popup.{0,80}inset-0/', $html);
    }
}
