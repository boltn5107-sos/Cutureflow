<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesPubliquesTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_page_d_accueil_est_accessible(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Couture Flow', false);
    }

    public function test_l_assistant_d_installation_est_inaccessible_une_fois_l_application_installee(): void
    {
        // La base contient les migrations : l'application est considérée
        // installée et l'assistant doit renvoyer vers l'accueil.
        $this->get('/installation')->assertRedirect(route('accueil'));
    }
}
