@extends('layouts.auth')

@section('title', 'Connexion')

@section('content')
    <div class="cf-card overflow-hidden">
        <div class="border-b border-brand-200/70 px-6 py-6 text-center dark:border-white/10">
            <h1 class="font-serif text-2xl font-semibold tracking-tight">Connexion</h1>
            <p class="mt-1.5 text-sm text-brand-600 dark:text-brand-300">
                Accédez à votre espace atelier.
            </p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-5 p-6">
            @csrf

            <x-form.text
                name="email"
                label="Adresse email"
                type="email"
                autocomplete="email"
                placeholder="atelier@exemple.com"
                required
                autofocus
            />

            <x-form.text
                name="password"
                label="Mot de passe"
                type="password"
                autocomplete="current-password"
                placeholder="••••••••"
                required
            />

            <label class="flex cursor-pointer items-center gap-2.5 text-sm text-brand-700 dark:text-brand-200">
                <input
                    type="checkbox"
                    name="remember"
                    value="1"
                    class="size-4 rounded border-brand-300 text-brand-700 focus:ring-brand-500 dark:border-white/20 dark:bg-white/5"
                >
                Se souvenir de moi
            </label>

            <button type="submit" class="cf-btn-primary w-full">
                <i class="fa-solid fa-arrow-right-to-bracket" aria-hidden="true"></i>
                Se connecter
            </button>
        </form>

        <div class="border-t border-brand-200/70 bg-brand-50/60 px-6 py-4 text-center text-sm dark:border-white/10 dark:bg-white/[0.02]">
            Vous n'avez pas encore d'atelier ?
            <a href="{{ route('register') }}" class="cf-link">Créer un compte</a>
        </div>
    </div>
@endsection
