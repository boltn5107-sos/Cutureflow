<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Commande de création du premier administrateur.
 *
 * C'est le chemin utilisé sur un hébergeur comme Render, où le site
 * d'installation est neutralisé (variables injectées par la plateforme,
 * fichier .env éphémère).
 */
class CoutureFlowAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_il_cree_un_administrateur_valide(): void
    {
        $code = Artisan::call('coutureflow:admin', [
            'email' => 'Admin@CoutureFlow.app',
            '--nom' => 'Direction générale',
            '--telephone' => '77 000 00 00',
        ]);

        $this->assertSame(Command::SUCCESS, $code);

        // L'adresse est normalisée en minuscules.
        $user = User::where('email', 'admin@coutureflow.app')->sole();

        $this->assertSame('Direction générale', $user->name);
        $this->assertSame('77 000 00 00', $user->phone);
        $this->assertTrue($user->isAdmin());
        $this->assertTrue($user->hasValidatedAccess());
        $this->assertSame(UserStatus::Valide, $user->status);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->status_updated_at);
    }

    public function test_le_mot_de_passe_genere_est_affiche_et_utilisable(): void
    {
        Artisan::call('coutureflow:admin', ['email' => 'admin@coutureflow.app']);

        $sortie = Artisan::output();
        $this->assertMatchesRegularExpression('/Mot de passe : (\S+)/u', $sortie);

        preg_match('/Mot de passe : (\S+)/u', $sortie, $capture);

        $this->assertTrue(
            Hash::check($capture[1], User::where('email', 'admin@coutureflow.app')->sole()->password)
        );
    }

    public function test_une_adresse_invalide_est_refusee(): void
    {
        $code = Artisan::call('coutureflow:admin', ['email' => 'pas-une-adresse']);

        $this->assertSame(Command::FAILURE, $code);
        $this->assertStringContainsString('Adresse e-mail invalide', Artisan::output());
        $this->assertSame(0, User::count());
    }

    public function test_un_mot_de_passe_trop_court_est_refuse(): void
    {
        $code = Artisan::call('coutureflow:admin', [
            'email' => 'admin@coutureflow.app',
            '--password' => 'court',
        ]);

        $this->assertSame(Command::FAILURE, $code);
        $this->assertStringContainsString('au moins 8 caractères', Artisan::output());
        $this->assertSame(0, User::count());
    }

    public function test_un_compte_existant_est_promu_sans_changer_son_mot_de_passe(): void
    {
        User::create([
            'name' => 'Atelier Sénégal',
            'email' => 'atelier@coutureflow.app',
            'password' => 'ancien-mot-de-passe',
        ]);

        $code = Artisan::call('coutureflow:admin', [
            'email' => 'atelier@coutureflow.app',
            '--force' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $code);
        $this->assertSame(1, User::count(), 'le compte ne doit pas être dupliqué.');

        $user = User::sole();

        $this->assertTrue($user->isAdmin());
        $this->assertTrue($user->hasValidatedAccess());
        $this->assertSame('Atelier Sénégal', $user->name);
        $this->assertTrue(Hash::check('ancien-mot-de-passe', $user->password));
        $this->assertStringNotContainsString('Mot de passe', Artisan::output());
    }

    public function test_le_mot_de_passe_est_remplace_lorsqu_il_est_fourni(): void
    {
        User::create([
            'name' => 'Direction',
            'email' => 'admin@coutureflow.app',
            'password' => 'ancien-mot-de-passe',
        ]);

        Artisan::call('coutureflow:admin', [
            'email' => 'admin@coutureflow.app',
            '--password' => 'nouveau-mot-de-passe',
            '--force' => true,
        ]);

        $this->assertTrue(Hash::check('nouveau-mot-de-passe', User::sole()->password));
    }

    public function test_sans_confirmation_le_compte_existant_reste_intact(): void
    {
        User::create([
            'name' => 'Atelier Sénégal',
            'email' => 'atelier@coutureflow.app',
            'password' => 'ancien-mot-de-passe',
        ]);

        // Sans --force et sans terminal interactif, la commande s'annule.
        $code = Artisan::call('coutureflow:admin', ['email' => 'atelier@coutureflow.app']);

        $this->assertSame(Command::FAILURE, $code);
        $this->assertFalse(User::sole()->isAdmin());
        $this->assertFalse(User::sole()->hasValidatedAccess());
    }
}
