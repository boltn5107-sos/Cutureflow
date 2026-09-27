<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Atelier;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\InscriptionEnvoyeeNotification;
use App\Notifications\NouvelleInscriptionNotification;
use App\Services\FileStorageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    public function __construct(private readonly FileStorageService $files) {}

    public function create(): View
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register', [
            'wave' => config('coutureflow.wave'),
            'subscription' => config('coutureflow.subscription'),
            'mimes' => config('coutureflow.proof.mimes'),
            'maxKb' => config('coutureflow.proof.max_kb'),
        ]);
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->string('nom')->toString(),
                'email' => $request->string('email')->toString(),
                'phone' => $request->string('telephone')->toString(),
                'password' => $request->string('password')->toString(),
                'role' => 'atelier',
                'status' => UserStatus::EnAttente,
                'status_updated_at' => now(),
            ]);

            Atelier::create([
                'user_id' => $user->id,
                'nom' => $request->string('atelier_nom')->toString(),
                'telephone' => $request->string('telephone')->toString(),
                'adresse' => $request->input('adresse'),
                'ville' => $request->input('ville'),
            ]);

            $stored = $this->files->storePrivate(
                $request->file('preuve'),
                config('coutureflow.proof.directory').'/'.$user->id
            );

            Subscription::create([
                'user_id' => $user->id,
                'libelle' => config('coutureflow.subscription.label'),
                'montant' => config('coutureflow.subscription.amount'),
                'duree_mois' => config('coutureflow.subscription.months'),
                'statut' => UserStatus::EnAttente,
                // Le formulaire ne demande plus ces informations : la date est
                // relevée à l'inscription, le numéro et la référence restent vides.
                'date_paiement' => now()->toDateString(),
                'wave_number_used' => null,
                'wave_reference' => null,
                'proof_disk' => $stored['disk'],
                'proof_path' => $stored['path'],
                'proof_original_name' => $stored['original_name'],
                'proof_mime' => $stored['mime'],
                'proof_size' => $stored['size'],
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        $user->notify(new InscriptionEnvoyeeNotification());

        User::where('role', 'admin')->get()
            ->each(fn (User $admin) => $admin->notify(new NouvelleInscriptionNotification($user)));

        return redirect()->route('pending')
            ->with('success', 'Votre inscription a bien été envoyée. Elle est en attente de vérification par notre équipe.');
    }
}
