<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Détermine si Couture+ est prêt à être utilisé et réalise
 * l'installation initiale.
 *
 * L'assistant d'installation n'est accessible que tant que l'application
 * n'est pas installée : une fois le fichier de verrouillage créé, la page
 * renvoie une erreur 404. Cela évite qu'un visiteur puisse réinitialiser
 * la base de production.
 */
class InstallationState
{
    /** Extensions PHP indispensables au fonctionnement de l'application. */
    public const EXTENSIONS_REQUISES = [
        'pdo',
        'mbstring',
        'openssl',
        'tokenizer',
        'xml',
        'ctype',
        'json',
        'fileinfo',
    ];

    public static function cheminVerrou(): string
    {
        return storage_path('app/installe.lock');
    }

    public static function verrouExiste(): bool
    {
        return File::exists(self::cheminVerrou());
    }

    /**
     * L'application est considérée installée dès que la base contient les
     * migrations. C'est le cas des installations existantes, pour lesquelles
     * le fichier de verrouillage n'a pas été créé lors du déploiement.
     */
    public static function installee(): bool
    {
        if (self::verrouExiste()) {
            return true;
        }

        if (! config('database.default')) {
            return false;
        }

        try {
            return Schema::hasTable('migrations')
                && Schema::hasTable('users')
                && ! empty(config('app.key'));
        } catch (Throwable) {
            // Connexion impossible : l'application n'est pas installée.
            return false;
        }
    }

    /**
     * Contrôles d'environnement affichés avant le formulaire.
     *
     * @return array<int, array{libelle: string, ok: bool, detail: string}>
     */
    public static function diagnostics(): array
    {
        $extensions = [];

        foreach (self::EXTENSIONS_REQUISES as $extension) {
            $extensions[] = [
                'libelle' => 'Extension PHP « '.$extension.' »',
                'ok' => extension_loaded($extension),
                'detail' => extension_loaded($extension)
                    ? 'Chargée.'
                    : 'Activez-la dans votre php.ini puis redémarrez le serveur.',
            ];
        }

        return [
            [
                'libelle' => 'PHP '.PHP_VERSION,
                'ok' => version_compare(PHP_VERSION, '8.2', '>='),
                'detail' => version_compare(PHP_VERSION, '8.2', '>=')
                    ? 'Version compatible.'
                    : 'La version 8.2 ou supérieure est requise.',
            ],
            ...$extensions,
            [
                'libelle' => 'Dossier « storage » accessible en écriture',
                'ok' => is_writable(storage_path()),
                'detail' => is_writable(storage_path())
                    ? 'Accessible.'
                    : 'Corrigez les permissions du dossier storage.',
            ],
            [
                'libelle' => 'Dossier « bootstrap/cache » accessible en écriture',
                'ok' => is_writable(base_path('bootstrap/cache')),
                'detail' => is_writable(base_path('bootstrap/cache'))
                    ? 'Accessible.'
                    : 'Corrigez les permissions du dossier bootstrap/cache.',
            ],
        ];
    }

    public static function environnementSatisfait(): bool
    {
        foreach (self::diagnostics() as $controle) {
            if (! $controle['ok']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Teste une connexion base de données avec les identifiants fournis.
     */
    public static function testerConnexion(array $config): array
    {
        $connexion = 'installation';

        config([
            'database.connections.'.$connexion => [
                'driver' => $config['driver'] ?? 'mysql',
                'host' => $config['host'] ?? '127.0.0.1',
                'port' => $config['port'] ?? '3306',
                'database' => $config['database'] ?? '',
                'username' => $config['username'] ?? '',
                'password' => $config['password'] ?? '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
            ],
        ]);

        try {
            DB::purge($connexion);
            DB::connection($connexion)->getPdo();

            return ['ok' => true, 'message' => 'Connexion réussie.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        } finally {
            DB::purge($connexion);
        }
    }

    /**
     * Écrit (ou met à jour) une variable du fichier .env.
     */
    public static function definirVariableEnv(string $cle, ?string $valeur): void
    {
        $chemin = base_path('.env');

        if (! File::exists($chemin)) {
            File::put($chemin, '');

            return;
        }

        $ligne = $cle.'='.$valeur;
        $contenu = File::get($chemin);
        $motif = '/^'.preg_quote($cle, '/').'=.*$/m';

        $contenu = preg_match($motif, $contenu) === 1
            ? preg_replace($motif, $ligne, $contenu)
            : rtrim($contenu, "\r\n").PHP_EOL.$ligne.PHP_EOL;

        File::put($chemin, $contenu);
    }

    /**
     * Installe réellement l'application : variables d'environnement, clé,
     * migrations et verrouillage définitif.
     */
    public static function installer(array $base, array $admin): array
    {
        self::definirVariableEnv('APP_NAME', $base['nom'] ?? config('app.name'));
        self::definirVariableEnv('APP_ENV', 'production');
        self::definirVariableEnv('APP_DEBUG', 'false');
        self::definirVariableEnv('APP_URL', $base['url'] ?? config('app.url'));
        self::definirVariableEnv('DB_CONNECTION', $base['driver'] ?? 'mysql');
        self::definirVariableEnv('DB_HOST', $base['host'] ?? '127.0.0.1');
        self::definirVariableEnv('DB_PORT', (string) ($base['port'] ?? '3306'));
        self::definirVariableEnv('DB_DATABASE', $base['database'] ?? '');
        self::definirVariableEnv('DB_USERNAME', $base['username'] ?? '');
        self::definirVariableEnv('DB_PASSWORD', $base['password'] ?? '');

        Artisan::call('config:clear');

        if (empty(config('app.key'))) {
            Artisan::call('key:generate', ['--force' => true]);
        }

        Artisan::call('migrate', ['--force' => true]);

        \App\Models\User::updateOrCreate(
            ['email' => $admin['email']],
            [
                'name' => $admin['nom'],
                'phone' => $admin['telephone'] ?? null,
                'password' => $admin['password'],
                'role' => 'admin',
                'status' => \App\Enums\UserStatus::Valide,
                'status_updated_at' => now(),
                'email_verified_at' => now(),
            ],
        );

        File::put(self::cheminVerrou(), now()->toIso8601String());

        Artisan::call('config:clear');
        Artisan::call('cache:clear');

        return ['ok' => true];
    }
}
