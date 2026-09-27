<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\Atelier;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Contrôle d'accès : un compte non validé (En Attente, Rejeté, Bloqué)
 * ne doit jamais atteindre l'application métier.
 */
class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function atelierUser(UserStatus $status, string $role = 'atelier'): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'status' => $status,
            'phone' => '77 000 00 00',
        ]);

        Atelier::create([
            'user_id' => $user->id,
            'nom' => 'Atelier '.$user->id,
            'ville' => 'Dakar',
        ]);

        return $user->fresh();
    }

    public static function blockedStatuses(): array
    {
        return [
            'en attente' => [UserStatus::EnAttente, 'pending'],
            'rejeté' => [UserStatus::Rejete, 'acces.refuse'],
            'bloqué' => [UserStatus::Bloque, 'acces.refuse'],
        ];
    }

    /** @dataProvider blockedStatuses */
    #[DataProvider('blockedStatuses')]
    public function test_un_compte_non_valide_est_redirige(UserStatus $status, string $route): void
    {
        $user = $this->atelierUser($status);

        $this->actingAs($user)->get('/tableau-de-bord')->assertRedirect(route($route));
    }

    /** @dataProvider blockedStatuses */
    #[DataProvider('blockedStatuses')]
    public function test_un_compte_non_valide_na_accede_a_aucune_route_metier(UserStatus $status): void
    {
        $user = $this->atelierUser($status);

        foreach ([
            '/clients',
            '/commandes',
            '/caisse',
            '/depenses',
            '/planning',
            '/notifications',
            '/catalogue',
            '/profil',
        ] as $path) {
            $response = $this->actingAs($user)->get($path);

            $this->assertTrue(
                $response->isRedirect() || $response->status() === 403,
                "La route {$path} serait accessible pour le statut {$status->value}."
            );
        }
    }

    public function test_un_compte_valide_accede_a_l_application(): void
    {
        $user = $this->atelierUser(UserStatus::Valide);

        $this->actingAs($user)->get('/tableau-de-bord')->assertOk();
        $this->actingAs($user)->get('/clients')->assertOk();
    }

    public function test_un_visiteur_est_redirige_vers_la_connexion(): void
    {
        $this->get('/tableau-de-bord')->assertRedirect(route('login'));
        $this->get('/clients')->assertRedirect(route('login'));
    }

    public function test_un_administrateur_franchit_le_middleware_metier(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => UserStatus::Valide,
        ]);

        // Le middleware atelier.valide laisse passer l'administrateur.
        $this->actingAs($admin)->get('/tableau-de-bord')->assertOk();
    }

    public function test_un_atelier_ne_peut_pas_acceder_a_l_administration(): void
    {
        $user = $this->atelierUser(UserStatus::Valide);

        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->get('/admin/utilisateurs')->assertForbidden();
        $this->actingAs($user)->get('/admin/paiements')->assertForbidden();
    }

    public function test_un_visiteur_ne_peut_pas_atteindre_l_administration(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_un_client_d_un_autelier_ne_voit_pas_les_donnees_d_un_autre(): void
    {
        $alice = $this->atelierUser(UserStatus::Valide);
        $bob = $this->atelierUser(UserStatus::Valide);

        $aliceClient = Client::create([
            'atelier_id' => $alice->atelier->id,
            'nom' => 'Cliente Alice',
            'telephone' => '77 111 11 11',
        ]);

        Client::create([
            'atelier_id' => $bob->atelier->id,
            'nom' => 'Cliente Bob',
            'telephone' => '77 222 22 22',
        ]);

        // La liste de l' atelier d'Alice ne contient que son client.
        $this->actingAs($alice)->get('/clients')
            ->assertOk()
            ->assertSee('Cliente Alice')
            ->assertDontSee('Cliente Bob');

        // La fiche du client de Bob est interdite à Alice.
        $this->actingAs($alice)
            ->get('/clients/'.$bob->atelier->clients()->value('id'))
            ->assertForbidden();
    }
}
