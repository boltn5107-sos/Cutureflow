<?php

namespace App\Http\Middleware;

use App\Support\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirige vers l'assistant d'installation tant que l'application n'est pas
 * configurée. Sans cette étape, un premier lancement sur une base vide
 * proposerait des pages qui échouent sur des tables inexistantes.
 */
class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (InstallationState::installee()) {
            return $next($request);
        }

        if ($request->routeIs('installation.*')) {
            return $next($request);
        }

        return redirect()->route('installation.index');
    }
}
