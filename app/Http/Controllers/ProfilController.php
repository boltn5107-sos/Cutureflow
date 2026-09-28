<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Http\Requests\ProfileRequest;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\NouvelleInscriptionNotification;
use App\Notifications\StatutCompteChangeNotification;
use App\Services\FileStorageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfilController extends Controller
{
    public function __construct(
        private readonly FileStorageService $files,
    ) {}

    public function edit(Request $request): View
    {
        $user = $request->user()->load('atelier');

        return view('profil.edit', [
            'user' => $user,
            'atelier' => $user->atelier,
        ]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $atelier = $user->atelier;

        $user->update([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'phone' => $request->string('phone')->toString(),
        ]);

        /*
         * Le téléphone de l'atelier est un champ à part : il était
         * écrasé par le numéro personnel du responsable, qui le rendait
         * impossible à maintenir depuis le profil.
         *
         * La colonne n'est touchée que si le champ est réellement présent
         * dans la requête : un envoi partiel ne doit pas effacer une
         * information, alors qu'un champ volontairement vidé doit rester
         * effaçable.
         */
        $atelierDonnees = [
            'nom' => $request->string('atelier_nom')->toString(),
            'adresse' => $request->input('adresse'),
            'ville' => $request->input('ville'),
            'ninea' => $request->input('ninea'),
        ];

        if ($request->has('atelier_telephone')) {
            $atelierDonnees['telephone'] = $request->input('atelier_telephone');
        }

        $atelier?->update($atelierDonnees);

        return back()->with('success', 'Votre profil a été mis à jour.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password_actuel' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], messages: [
            'password_actuel.required' => 'Votre mot de passe actuel est obligatoire.',
            'password.min' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation ne correspond pas au nouveau mot de passe.',
        ]);

        if (! Hash::check($validated['password_actuel'], $request->user()->password)) {
            throw ValidationException::withMessages([
                'password_actuel' => 'Le mot de passe actuel est incorrect.',
            ]);
        }

        $request->user()->update(['password' => $validated['password']]);

        return back()->with('success', 'Votre mot de passe a été modifié.');
    }

    public function abonnement(Request $request): View
    {
        $user = $request->user();

        return view('abonnement.index', [
            'user' => $user,
            'atelier' => $user->atelier,
            'subscriptions' => $user->subscriptions()->with('reviewer')->get(),
            'wave' => config('coutureflow.wave'),
            'config' => config('coutureflow.subscription'),
        ]);
    }

    /**
     * Dépose un renouvellement d'abonnement (paiement Wave manuel).
     *
     * Autorisé pour tout compte d'atelier, y compris non validé : c'est
     * précisément le moyen de régulariser un compte bloqué ou en attente.
     * Un administrateur n'a pas d'abonnement à renouveler.
     */
    public function storeAbonnement(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $validated = $request->validate([
            'date_paiement' => ['required', 'date', 'before_or_equal:today', 'after:2020-01-01'],
            'wave_number_used' => ['required', 'string', 'min:6', 'max:40'],
            'wave_reference' => ['nullable', 'string', 'max:80'],
            'preuve' => [
                'required',
                'file',
                'mimes:'.implode(',', config('coutureflow.proof.mimes')),
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf',
                'max:'.config('coutureflow.proof.max_kb'),
            ],
        ], messages: [
            'preuve.mimes' => 'La preuve doit être une image (JPG, PNG, WEBP) ou un PDF.',
            'preuve.max' => 'La preuve ne doit pas dépasser '.round(config('coutureflow.proof.max_kb') / 1024).' Mo.',
            'wave_number_used.required' => 'Indiquez le numéro Wave utilisé pour le paiement.',
        ], attributes: [
            'wave_number_used' => 'numéro Wave utilisé',
            'preuve' => 'preuve de paiement',
        ]);

        $stored = $this->files->storePrivate(
            $request->file('preuve'),
            config('coutureflow.proof.directory').'/'.$user->id
        );

        $subscription = DB::transaction(fn () => Subscription::create([
            'user_id' => $user->id,
            'libelle' => config('coutureflow.subscription.label').' — renouvellement',
            'montant' => config('coutureflow.subscription.amount'),
            'duree_mois' => config('coutureflow.subscription.months'),
            'statut' => UserStatus::EnAttente,
            'date_paiement' => $validated['date_paiement'],
            'wave_number_used' => $validated['wave_number_used'],
            'wave_reference' => $validated['wave_reference'] ?? null,
            'proof_disk' => $stored['disk'],
            'proof_path' => $stored['path'],
            'proof_original_name' => $stored['original_name'],
            'proof_mime' => $stored['mime'],
            'proof_size' => $stored['size'],
        ]));

        // L'admin est prévenu du renouvellement à vérifier.
        User::where('role', 'admin')->get()
            ->each(fn (User $admin) => $admin->notify(new NouvelleInscriptionNotification($user)));

        $user->notify(new StatutCompteChangeNotification(
            UserStatus::EnAttente,
            'Renouvellement reçu — en attente de vérification.'
        ));

        return redirect()
            ->route('abonnement.index')
            ->with('success', 'Votre preuve de paiement a été transmise. Elle sera vérifiée sous 24 à 48 heures.');
    }

    public function subscriptionProof(Subscription $subscription)
    {
        $this->authorize('viewProof', $subscription);

        abort_unless($subscription->hasProof(), 404);

        $disk = Storage::disk($subscription->proof_disk ?: config('coutureflow.proof.disk'));
        abort_unless($disk->exists($subscription->proof_path), 404);

        return $disk->response($subscription->proof_path, basename($subscription->proof_path), [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
