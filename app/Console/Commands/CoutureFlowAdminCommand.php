<?php

namespace App\Console\Commands;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Crée le premier administrateur d'un déploiement neuf.
 *
 * Sur un hébergeur comme Render, le site d'installation est inopérant :
 * les variables d'environnement sont injectées par la plateforme et le
 * fichier .env écrit par l'assistant disparaît au redémarrage. Cette
 * commande remplit le même rôle depuis le shell ou le dashboard.
 */
class CoutureFlowAdminCommand extends Command
{
    protected $signature = 'coutureflow:admin
                            {email : Adresse e-mail de l\'administrateur}
                            {--nom= : Nom affiché, « Administrateur » par défaut}
                            {--password= : Mot de passe, généré s\'il est absent}
                            {--telephone= : Numéro de téléphone}
                            {--force : Ne pas demander confirmation si le compte existe}';

    protected $description = 'Crée ou réinitialise un compte administrateur validé.';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Adresse e-mail invalide : {$email}");

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();
        $password = $this->option('password');
        $genere = blank($password);

        // Un mot de passe déjà connu n'est jamais écrasé par surprise : sur
        // un compte existant sans --password, le mot de passe est conservé.
        $applique = ! $user || ! $genere;

        if ($genere && $user) {
            $this->line('Le compte existe déjà : son mot de passe est conservé.');
            $this->line('Utilisez --password pour le changer.');
        }

        if ($genere) {
            $password = Str::password(16, letters: true, numbers: true, symbols: false, spaces: false);
        }

        if (mb_strlen((string) $password) < 8) {
            $this->error('Le mot de passe doit contenir au moins 8 caractères.');

            return self::FAILURE;
        }

        if ($user && ! $this->option('force')) {
            $this->warn("Un compte existe déjà pour {$email} : il passera administrateur validé.");

            // Hors mode interactif (cron, CI), confirm() renvoie false :
            // l'opération est annulée plutôt que d'écraser un compte.
            if (! $this->confirm('Continuer ?')) {
                $this->line('Opération annulée.');

                return self::FAILURE;
            }
        }

        $attributs = [
            'role' => 'admin',
            'status' => UserStatus::Valide,
            'status_updated_at' => now(),
            'validated_at' => now(),
        ];

        if ($user) {
            // Promouvoir un atelier existant ne doit pas renommer son
            // titulaire : l'identité n'est écrasée que sur demande.
            if ($this->option('nom')) {
                $attributs['name'] = $this->option('nom');
            }

            if ($this->option('telephone')) {
                $attributs['phone'] = $this->option('telephone');
            }
        } else {
            $attributs['name'] = (string) ($this->option('nom') ?: 'Administrateur');
            $attributs['phone'] = $this->option('telephone') ?: null;
        }

        $user = User::updateOrCreate(['email' => $email], $attributs + ($applique ? ['password' => $password] : []));

        // email_verified_at est volontairement hors du $fillable du modèle :
        // on l'écrit explicitement plutôt que d'ouvrir le champ à toute
        // affectation de masse.
        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $this->info(sprintf(
            'Administrateur %s : %s (#%d).',
            $user->wasRecentlyCreated ? 'créé' : 'mis à jour',
            $user->email,
            $user->id
        ));

        // Le mot de passe n'est affiché que s'il a réellement été enregistré.
        if ($genere && $applique) {
            $this->newLine();
            $this->line("Mot de passe : <options=bold>{$password}</>");
            $this->comment('Notez-le maintenant : il ne sera plus jamais affiché.');
        }

        return self::SUCCESS;
    }
}
