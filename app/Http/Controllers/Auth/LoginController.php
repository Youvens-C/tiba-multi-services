<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = [
            'username' => $request->username,
            'password' => $request->password,
            'status' => 'actif',
        ];

        // Pas de "remember me" : la session expire après 30 min d'inactivité
        // (CDC 4.1 / 6.5), quel que soit le choix du navigateur de mémoriser
        // ou non les identifiants côté client.
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();
            $user->last_login_at = now();
            $user->save();

            \App\Models\AuditLog::log(
                $user->id,
                'connexion',
                'utilisateur',
                $user->id,
                "Connexion réussie"
            );

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'username' => 'Identifiant ou mot de passe incorrect.',
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            \App\Models\AuditLog::log(
                Auth::id(),
                'deconnexion',
                'utilisateur',
                Auth::id(),
                "Déconnexion"
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
