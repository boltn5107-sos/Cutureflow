<?php

use App\Http\Middleware\EnsureAtelierValide;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'atelier.valide' => EnsureAtelierValide::class,
            'admin' => EnsureUserIsAdmin::class,
        ]);

        // Tant que l'application n'est pas installée, tout le site renvoie
        // vers l'assistant. Le middleware est placé en tête pour s'exécuter
        // avant la session, qui nécessite déjà une clé d'application.
        $middleware->prependToGroup('web', EnsureInstalled::class);

        // L'assistant doit rester accessible même sans clé d'application :
        // c'est lui qui la génère. Sans cela, aucune session ni jeton CSRF
        // ne pourrait être créé sur une installation vierge.
        $middleware->validateCsrfTokens(except: [
            'installation',
            'installation/*',
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
