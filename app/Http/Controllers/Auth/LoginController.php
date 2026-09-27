<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials['email'] = mb_strtolower(trim($credentials['email']));

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Ces identifiants ne correspondent pas à nos enregistrements.',
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->save();

        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        if (! $user->hasValidatedAccess()) {
            return redirect()->route($user->status->value === 'en_attente' ? 'pending' : 'acces.refuse');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('accueil');
    }
}
