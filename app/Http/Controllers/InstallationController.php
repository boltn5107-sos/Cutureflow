<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\User;
use App\Support\InstallationState;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InstallationController extends Controller
{
    /**
     * L'assistant est inaccessible dès que l'application est installée :
     * impossible de réinitialiser une base en production.
     */
    public function index(): View|RedirectResponse
    {
        if (InstallationState::installee()) {
            return redirect()->route('accueil');
        }

        return view('installation.index', [
            'diagnostics' => InstallationState::diagnostics(),
            'pret' => InstallationState::environnementSatisfait(),
            'test' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if(InstallationState::installee(), 404);

        // Le bouton « Tester la connexion » utilise le même formulaire :
        // seuls les champs de base de données sont alors validés.
        if ($request->input('action') === 'tester') {
            $base = $request->validate([
                'db_driver' => ['required', 'in:mysql'],
                'db_host' => ['required', 'string', 'max:120'],
                'db_port' => ['required', 'string', 'max:6'],
                'db_database' => ['required', 'string', 'max:120'],
                'db_username' => ['required', 'string', 'max:120'],
                'db_password' => ['nullable', 'string', 'max:120'],
            ], attributes: [
                'db_database' => 'nom de la base de données',
                'db_username' => 'identifiant MySQL',
                'db_password' => 'mot de passe MySQL',
            ]);

            return back()
                ->withInput($request->except('db_password'))
                ->with('test', InstallationState::testerConnexion([
                    'driver' => $base['db_driver'],
                    'host' => $base['db_host'],
                    'port' => $base['db_port'],
                    'database' => $base['db_database'],
                    'username' => $base['db_username'],
                    'password' => $base['db_password'] ?? '',
                ]));
        }

        $valide = $request->validate([
            'app_nom' => ['required', 'string', 'max:80'],
            'app_url' => ['required', 'url', 'max:190'],
            'db_driver' => ['required', 'in:mysql'],
            'db_host' => ['required', 'string', 'max:120'],
            'db_port' => ['required', 'string', 'max:6'],
            'db_database' => ['required', 'string', 'max:120'],
            'db_username' => ['required', 'string', 'max:120'],
            'db_password' => ['nullable', 'string', 'max:120'],
            'admin_nom' => ['required', 'string', 'min:3', 'max:150'],
            'admin_email' => ['required', 'email', 'max:190'],
            'admin_telephone' => ['nullable', 'string', 'max:40'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'db_database.required' => 'Indiquez le nom de la base de données.',
            'db_username.required' => 'Indiquez l\'identifiant MySQL.',
            'admin_password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ], attributes: [
            'app_nom' => 'nom de l\'application',
            'app_url' => 'URL de l\'application',
            'db_database' => 'nom de la base de données',
            'db_username' => 'identifiant MySQL',
            'db_password' => 'mot de passe MySQL',
            'admin_nom' => 'nom de l\'administrateur',
            'admin_email' => 'adresse email de l\'administrateur',
            'admin_telephone' => 'téléphone de l\'administrateur',
            'admin_password' => 'mot de passe de l\'administrateur',
        ]);

        if (! InstallationState::environnementSatisfait()) {
            return back()
                ->withInput($request->except('db_password', 'admin_password', 'admin_password_confirmation'))
                ->withErrors([
                    'environnement' => 'Corrigez les points en rouge de la section « Vérifications » avant de continuer.',
                ]);
        }

        $test = InstallationState::testerConnexion([
            'driver' => $valide['db_driver'],
            'host' => $valide['db_host'],
            'port' => $valide['db_port'],
            'database' => $valide['db_database'],
            'username' => $valide['db_username'],
            'password' => $valide['db_password'] ?? '',
        ]);

        if (! $test['ok']) {
            return back()
                ->withInput($request->except('db_password', 'admin_password', 'admin_password_confirmation'))
                ->withErrors([
                    'db_database' => 'Connexion impossible : '.$test['message'],
                ]);
        }

        InstallationState::installer(
            [
                'nom' => $valide['app_nom'],
                'url' => $valide['app_url'],
                'driver' => $valide['db_driver'],
                'host' => $valide['db_host'],
                'port' => $valide['db_port'],
                'database' => $valide['db_database'],
                'username' => $valide['db_username'],
                'password' => $valide['db_password'] ?? '',
            ],
            [
                'nom' => $valide['admin_nom'],
                'email' => mb_strtolower($valide['admin_email']),
                'telephone' => $valide['admin_telephone'] ?? null,
                'password' => $valide['admin_password'],
            ],
        );

        $admin = User::where('email', mb_strtolower($valide['admin_email']))->firstOrFail();

        Auth::login($admin);
        request()->session()->regenerate();

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Installation terminée. Votre compte administrateur est prêt.');
    }
}
