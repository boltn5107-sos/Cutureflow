<?php

use App\Http\Controllers\AccountStatusController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminSubscriptionController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CaisseController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepenseController;
use App\Http\Controllers\InstallationController;
use App\Http\Controllers\MesureController;
use App\Http\Controllers\ModeleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\RelanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pages publiques
|--------------------------------------------------------------------------
*/

Route::view('/', 'accueil')->name('accueil');

/*
|--------------------------------------------------------------------------
| Installation initiale
|--------------------------------------------------------------------------
|
| Ces routes redirigent vers l'accueil dès que l'application est installée :
| le contrôleur refuse l'accès au besoin.
|
*/

Route::get('/installation', [InstallationController::class, 'index'])->name('installation.index');
Route::post('/installation', [InstallationController::class, 'store'])->name('installation.store');

/*
|--------------------------------------------------------------------------
| Authentification
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/inscription', [RegisterController::class, 'create'])->name('register');
    Route::post('/inscription', [RegisterController::class, 'store'])->middleware('throttle:6,1');

    Route::get('/connexion', [LoginController::class, 'create'])->name('login');
    Route::post('/connexion', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

Route::post('/deconnexion', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Statut d'accès (utilisateur connecté non validé)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/en-attente', [AccountStatusController::class, 'pending'])->name('pending');
    Route::get('/acces-refuse', [AccountStatusController::class, 'refused'])->name('acces.refuse');

    Route::get('/abonnement', [ProfilController::class, 'abonnement'])->name('abonnement.index');
    Route::post('/abonnement/renouvellement', [ProfilController::class, 'storeAbonnement'])
        ->middleware('throttle:6,1')
        ->name('abonnement.store');
    Route::get('/abonnement/preuve/{subscription}', [ProfilController::class, 'subscriptionProof'])
        ->name('abonnement.preuve');

    /*
     * Point d'état des notifications interrogé par le navigateur, pour la
     * pastille et le son. Volontairement hors du groupe « atelier.valide » :
     * un administrateur reçoit aussi des notifications et n'a pas accès à
     * /notifications.
     */
    Route::get('/notifications/etat', [NotificationController::class, 'etat'])->name('notifications.etat');

    /*
     * Abonnements push (Web Push) : le navigateur y dépose l'extrémité à
     * laquelle les notifications seront envoyées, même application fermée.
     * Même motif que l'état : hors « atelier.valide » pour couvrir les
     * administrateurs.
     */
    Route::post('/notifications/push/abonner', [NotificationController::class, 'abonnerPush'])
        ->middleware('throttle:30,1')
        ->name('notifications.push.subscribe');
    Route::delete('/notifications/push/desabonner', [NotificationController::class, 'desabonnerPush'])
        ->middleware('throttle:30,1')
        ->name('notifications.push.unsubscribe');
});

/*
|--------------------------------------------------------------------------
| Espace atelier — accessible uniquement si l'abonnement est validé
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'atelier.valide'])->group(function () {
    Route::get('/tableau-de-bord', [DashboardController::class, 'index'])->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | Relances — qui rappeler depuis l'atelier
    |----------------------------------------------------------------------
    | Aucune transaction client, aucun envoi de message : la page liste des
    | suggestions d'appel et enregistre celles qui ont été faites.
    */
    Route::get('/relances', [RelanceController::class, 'index'])->name('relances.index');
    Route::post('/relances', [RelanceController::class, 'store'])->name('relances.store');

    // Profil
    Route::get('/profil', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::put('/profil/mot-de-passe', [ProfilController::class, 'updatePassword'])->name('profil.password');

    /*
    |----------------------------------------------------------------------
    | Clients
    |----------------------------------------------------------------------
    */
    Route::resource('clients', ClientController::class)->except(['destroy']);
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
    Route::get('/clients/{client}/photo', [ClientController::class, 'photo'])->name('clients.photo');

    /*
    |----------------------------------------------------------------------
    | Mesures
    |----------------------------------------------------------------------
    */
    Route::get('/clients/{client}/mesures', [MesureController::class, 'index'])->name('mesures.index');
    Route::post('/clients/{client}/mesures', [MesureController::class, 'store'])->name('mesures.store');
    Route::put('/mesures/{mesure}', [MesureController::class, 'update'])->name('mesures.update');
    Route::delete('/mesures/{mesure}', [MesureController::class, 'destroy'])->name('mesures.destroy');

    /*
    |----------------------------------------------------------------------
    | Commandes
    |----------------------------------------------------------------------
    */
    Route::resource('commandes', CommandeController::class);
    Route::patch('/commandes/{commande}/statut', [CommandeController::class, 'statut'])->name('commandes.statut');

    /*
    |----------------------------------------------------------------------
    | Caisse
    |----------------------------------------------------------------------
    */
    Route::get('/caisse', [CaisseController::class, 'index'])->name('caisse.index');

    Route::get('/caisse/paiements', [CaisseController::class, 'create'])->name('caisse.paiements.create');
    Route::post('/caisse/paiements', [CaisseController::class, 'store'])->name('caisse.paiements.store');
    Route::get('/caisse/paiements/{paiement}', [CaisseController::class, 'show'])->name('caisse.paiements.show');
    Route::get('/caisse/paiements/{paiement}/modifier', [CaisseController::class, 'edit'])->name('caisse.paiements.edit');
    Route::put('/caisse/paiements/{paiement}', [CaisseController::class, 'update'])->name('caisse.paiements.update');
    Route::delete('/caisse/paiements/{paiement}', [CaisseController::class, 'destroy'])->name('caisse.paiements.destroy');

    /*
    |----------------------------------------------------------------------
    | Dépenses
    |----------------------------------------------------------------------
    */
    Route::resource('depenses', DepenseController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    /*
    |----------------------------------------------------------------------
    | Planning
    |----------------------------------------------------------------------
    */
    Route::get('/planning', [PlanningController::class, 'index'])->name('planning.index');
    Route::get('/planning/creer', [PlanningController::class, 'create'])->name('planning.create');
    Route::post('/planning', [PlanningController::class, 'store'])->name('planning.store');
    Route::get('/planning/{rendezVous}/modifier', [PlanningController::class, 'edit'])->name('planning.edit');
    Route::put('/planning/{rendezVous}', [PlanningController::class, 'update'])->name('planning.update');
    Route::delete('/planning/{rendezVous}', [PlanningController::class, 'destroy'])->name('planning.destroy');

    /*
    |----------------------------------------------------------------------
    | Notifications
    |----------------------------------------------------------------------
    */
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/tout-lire', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::delete('/notifications/vider', [NotificationController::class, 'clear'])->name('notifications.clear');
    Route::get('/notifications/{id}', [NotificationController::class, 'read'])->name('notifications.read');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    /*
    |----------------------------------------------------------------------
    | Catalogue
    |----------------------------------------------------------------------
    */
    Route::get('/catalogue', [ModeleController::class, 'index'])->name('catalogue.index');
    Route::get('/catalogue/creer', [ModeleController::class, 'create'])->name('modeles.create');
    Route::post('/catalogue', [ModeleController::class, 'store'])->name('modeles.store');
    Route::get('/catalogue/{modele}', [ModeleController::class, 'show'])->name('modeles.show');
    Route::get('/catalogue/{modele}/modifier', [ModeleController::class, 'edit'])->name('modeles.edit');
    Route::put('/catalogue/{modele}', [ModeleController::class, 'update'])->name('modeles.update');
    Route::delete('/catalogue/{modele}', [ModeleController::class, 'destroy'])->name('modeles.destroy');
    Route::delete('/catalogue/{modele}/photos/{photo}', [ModeleController::class, 'destroyPhoto'])->name('modeles.photos.destroy');
    Route::patch('/catalogue/{modele}/photos/ordre', [ModeleController::class, 'reorderPhotos'])->name('modeles.photos.reorder');
    Route::get('/photos/{photo}', [ModeleController::class, 'photo'])->name('modeles.photo');
});

/*
|--------------------------------------------------------------------------
| Espace administrateur
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/utilisateurs', [AdminUserController::class, 'index'])->name('utilisateurs.index');
        Route::get('/utilisateurs/{user}', [AdminUserController::class, 'show'])->name('utilisateurs.show');
        Route::patch('/utilisateurs/{user}/statut', [AdminUserController::class, 'updateStatus'])->name('utilisateurs.statut');

        Route::get('/paiements', [AdminSubscriptionController::class, 'index'])->name('paiements.index');
        Route::get('/paiements/{subscription}', [AdminSubscriptionController::class, 'show'])->name('paiements.show');
        Route::patch('/paiements/{subscription}', [AdminSubscriptionController::class, 'review'])->name('paiements.review');
        Route::get('/paiements/{subscription}/preuve', [AdminSubscriptionController::class, 'proof'])->name('paiements.proof');
    });
