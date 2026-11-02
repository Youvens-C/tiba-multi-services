<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Empêche le navigateur de restaurer une page sensible (login, dashboard,
 * transactions, dettes...) depuis son cache de navigation (bfcache) quand
 * l'utilisateur utilise le bouton précédent/suivant ou Alt+Flèche.
 *
 * Sans cela, après une déconnexion, le bouton "précédent" peut réafficher
 * une page authentifiée déjà rendue sans repasser par le serveur — et donc
 * sans revérifier la session.
 */
class PreventBackHistoryCache
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
