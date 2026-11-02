<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Déconnecte immédiatement un utilisateur dont le compte a été désactivé
 * pendant qu'il était connecté (indépendant du driver de session).
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->status !== 'actif') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Votre compte a été désactivé. Contactez le directeur.');
        }

        return $next($request);
    }
}
