<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Contracts\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $counts = [
            'en_attente' => User::roleAtelier()->status(UserStatus::EnAttente)->count(),
            'valide' => User::roleAtelier()->status(UserStatus::Valide)->count(),
            'rejete' => User::roleAtelier()->status(UserStatus::Rejete)->count(),
            'bloque' => User::roleAtelier()->status(UserStatus::Bloque)->count(),
        ];

        $paiementsEnAttente = Subscription::enAttente()
            ->with('user.atelier')
            ->orderBy('date_paiement')
            ->limit(5)
            ->get();

        $dernieresInscriptions = User::roleAtelier()
            ->with('atelier', 'latestSubscription')
            ->latest()
            ->limit(5)
            ->get();

        $abonnementsExpires = User::roleAtelier()
            ->status(UserStatus::Valide)
            ->whereHas('atelier', fn ($q) => $q->whereNotNull('valid_until')->where('valid_until', '<=', now()->addDays(7)))
            ->with('atelier')
            ->get();

        $revenusMois = (float) Subscription::where('statut', UserStatus::Valide->value)
            ->whereYear('date_paiement', now()->year)
            ->whereMonth('date_paiement', now()->month)
            ->sum('montant');

        return view('admin.dashboard.index', [
            'counts' => $counts,
            'total' => array_sum($counts),
            'paiementsEnAttente' => $paiementsEnAttente,
            'dernieresInscriptions' => $dernieresInscriptions,
            'abonnementsExpires' => $abonnementsExpires,
            'totalPaiementsEnAttente' => Subscription::enAttente()->count(),
            'revenusMois' => $revenusMois,
            'statusLabels' => UserStatus::labels(),
        ]);
    }
}
