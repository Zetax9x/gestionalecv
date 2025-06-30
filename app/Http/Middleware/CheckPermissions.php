<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $modulo, string $azione): Response
    {
        $user = auth()->user();

        // Se l'utente non è autenticato, reindirizza al login
        if (!$user) {
            return redirect()->route('login');
        }

        // Se l'utente non è attivo, blocca l'accesso
        if (!$user->isAttivo()) {
            abort(403, 'Account disattivato. Contatta l\'amministratore.');
        }

        // Verifica i permessi per il modulo e azione specifica
        if (!$user->hasPermission($modulo, $azione)) {
            abort(403, 'Non hai i permessi per accedere a questa sezione.');
        }

        return $next($request);
    }
}