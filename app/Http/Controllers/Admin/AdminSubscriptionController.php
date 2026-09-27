<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewSubscriptionRequest;
use App\Models\Subscription;
use App\Services\AccountStatusService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminSubscriptionController extends Controller
{
    public function __construct(private readonly AccountStatusService $statuses) {}

    public function index(Request $request): View
    {
        $subscriptions = Subscription::with(['user.atelier', 'reviewer'])
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')->toString()))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q')->toString();
                $q->where(function ($sub) use ($term) {
                    $sub->where('wave_number_used', 'like', "%{$term}%")
                        ->orWhere('wave_reference', 'like', "%{$term}%")
                        ->orWhereHas('user', fn ($u) => $u->search($term));
                });
            })
            // CASE plutôt que FIELD() : la fonction n'existe que sur MySQL.
            ->orderByRaw("CASE statut WHEN 'en_attente' THEN 1 WHEN 'valide' THEN 2 WHEN 'rejete' THEN 3 WHEN 'bloque' THEN 4 ELSE 5 END")
            ->latest('date_paiement')
            ->paginate(12)
            ->withQueryString();

        $counts = Subscription::selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->all();

        return view('admin.paiements.index', [
            'subscriptions' => $subscriptions,
            'filters' => $request->only('q', 'statut'),
            'statusOptions' => UserStatus::options(),
            'counts' => [
                UserStatus::EnAttente->value => $counts[UserStatus::EnAttente->value] ?? 0,
                UserStatus::Valide->value => $counts[UserStatus::Valide->value] ?? 0,
                UserStatus::Rejete->value => $counts[UserStatus::Rejete->value] ?? 0,
                UserStatus::Bloque->value => $counts[UserStatus::Bloque->value] ?? 0,
            ],
        ]);
    }

    public function show(Subscription $subscription): View
    {
        $this->authorize('view', $subscription);

        $subscription->load(['user.atelier', 'reviewer']);

        return view('admin.paiements.show', [
            'subscription' => $subscription,
            'statusOptions' => UserStatus::options(),
        ]);
    }

    public function review(ReviewSubscriptionRequest $request, Subscription $subscription): RedirectResponse
    {
        $this->authorize('review', $subscription);

        $statut = $request->statut();
        $raison = $request->input('raison');

        $user = $this->statuses->apply(
            $subscription,
            $statut,
            $request->user(),
            $raison,
            $request->input('valid_from'),
            $request->input('valid_until'),
        );

        $this->statuses->notifier($user, $statut, $raison);

        return redirect()
            ->route('admin.paiements.index')
            ->with('success', "{$user->displayName()} — statut mis à jour : {$statut->label()}.");
    }

    /**
     * Sert la preuve de paiement. Le fichier est stocké hors de public/ :
     * aucune URL publique ne permet d'y accéder sans passer par ce controller.
     *
     * Les images sont servies en « inline » afin de s'afficher directement dans
     * la page. Les PDF restent en téléchargement : les afficher dans l'origine
     * autoriserait le lecteur PDF du navigateur sur un fichier téléversé.
     */
    public function proof(Request $request, Subscription $subscription): StreamedResponse
    {
        $this->authorize('viewProof', $subscription);

        abort_unless($subscription->hasProof(), 404);

        $disk = Storage::disk($subscription->proof_disk ?: config('coutureflow.proof.disk'));
        abort_unless($disk->exists($subscription->proof_path), 404);

        $name = $subscription->proof_original_name ?: 'preuve-paiement';
        $mime = $subscription->proof_mime ?: $disk->mimeType($subscription->proof_path);
        $estImage = $subscription->isImage();

        $headers = [
            'Content-Type' => $mime ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Security-Policy' => $estImage
                ? "default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; sandbox"
                : "default-src 'none'; object-src 'none'; sandbox",
        ];

        // ?telecharger=1 force le téléchargement d'une image déjà affichable.
        $telecharger = $request->boolean('telecharger');

        if ($estImage && ! $telecharger) {
            return $disk->response($subscription->proof_path, $name, $headers, 'inline');
        }

        return $disk->download($subscription->proof_path, $name, $headers);
    }
}
