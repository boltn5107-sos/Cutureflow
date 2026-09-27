<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Empêche l'accès à l'application métier si l'abonnement de l'atelier
 * n'est pas validé (statut En Attente, Rejeté ou Bloqué).
 */
class EnsureAtelierValide
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        $status = $user->status ?? UserStatus::EnAttente;

        if ($status->allowsAccess()) {
            return $next($request);
        }

        return match ($status) {
            UserStatus::EnAttente => redirect()->route('pending'),
            UserStatus::Rejete, UserStatus::Bloque => redirect()->route('acces.refuse'),
        };
    }
}
