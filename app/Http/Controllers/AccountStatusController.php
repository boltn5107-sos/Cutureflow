<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Pages affichées aux utilisateurs dont le compte n'est pas « Validé ».
 */
class AccountStatusController extends Controller
{
    public function pending(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->isAdmin() || $user->hasValidatedAccess()) {
            return redirect()->route('dashboard');
        }

        if ($user->status !== UserStatus::EnAttente) {
            return redirect()->route('acces.refuse');
        }

        return view('auth.pending', [
            'user' => $user,
            'subscription' => $user->subscriptions()->first(),
            'wave' => config('coutureflow.wave'),
        ]);
    }

    public function refused(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->isAdmin() || $user->hasValidatedAccess()) {
            return redirect()->route('dashboard');
        }

        return view('auth.refused', [
            'user' => $user,
            'status' => $user->status,
            'subscription' => $user->subscriptions()->first(),
            'wave' => config('coutureflow.wave'),
        ]);
    }
}
