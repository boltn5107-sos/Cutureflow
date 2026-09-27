<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeUserStatusRequest;
use App\Models\User;
use App\Services\AccountStatusService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function __construct(private readonly AccountStatusService $statuses) {}

    public function index(Request $request): View
    {
        $users = User::roleAtelier()
            ->with('atelier', 'latestSubscription')
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->toString()))
            ->when($request->filled('statut'), fn ($q) => $q->where('status', $request->string('statut')->toString()))
            // CASE plutôt que FIELD() : la fonction n'existe que sur MySQL.
            ->orderByRaw("CASE status WHEN 'en_attente' THEN 1 WHEN 'valide' THEN 2 WHEN 'bloque' THEN 3 WHEN 'rejete' THEN 4 ELSE 5 END")
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = User::roleAtelier()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return view('admin.utilisateurs.index', [
            'users' => $users,
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

    public function show(User $user): View
    {
        $user->load(['atelier', 'subscriptions.reviewer']);

        return view('admin.utilisateurs.show', [
            'user' => $user,
            'statusOptions' => UserStatus::options(),
        ]);
    }

    public function updateStatus(ChangeUserStatusRequest $request, User $user): RedirectResponse
    {
        $this->authorize('changeStatus', $user);

        $statut = UserStatus::from($request->string('status')->toString());
        $raison = $request->input('status_reason');

        $updated = $this->statuses->setUserStatus($user, $statut, $request->user(), $raison);
        $this->statuses->notifier($updated, $statut, $raison);

        return back()->with('success', "Le statut de {$updated->displayName()} est maintenant « {$statut->label()} ».");
    }
}
