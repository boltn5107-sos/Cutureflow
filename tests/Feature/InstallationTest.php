<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Assistant d'installation.
 *
 * Sans RefreshDatabase, la base en mémoire ne contient aucune table :
 * l'application est donc considérée comme non installée, ce qui est
 * exactement la situation d'un premier lancement.
 */
class InstallationTest extends TestCase
{
    public function test_une_application_non_installee_redirige_vers_l_assistant(): void
    {
        $this->get('/')->assertRedirect(route('installation.index'));
    }

    public function test_l_assistant_d_installation_est_propose_au_premier_lancement(): void
    {
        $response = $this->get('/installation');

        $response->assertOk();
        $response->assertSee('Installer Couture Flow', false);
        $response->assertSee('Vérifications', false);
        $response->assertSee('Compte administrateur', false);
    }

    public function test_l_assistant_refuse_un_formulaire_incomplet(): void
    {
        // Sans RefreshDatabase, aucune table n'existe : l'application est
        // non installée, ce qui est la situation d'un premier lancement.
        $this->post('/installation', [])->assertSessionHasErrors([
            'app_nom',
            'app_url',
            'db_host',
            'db_database',
            'db_username',
            'admin_nom',
            'admin_email',
            'admin_password',
        ]);

        $this->assertGuest();
        $this->assertFalse(\App\Support\InstallationState::verrouExiste());
    }

    public function test_le_test_de_connexion_renvoie_vers_la_page(): void
    {
        $this->post('/installation', [
            'action' => 'tester',
            'db_driver' => 'mysql',
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
            'db_database' => 'base_inexistante_test',
            'db_username' => 'root',
            'db_password' => '',
        ])->assertRedirect();

        $this->assertGuest();
    }
}
