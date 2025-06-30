<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermissions
{
    public function handle(Request $request, Closure $next, string $modulo, string $azione): Response
{
    $user = auth()->user();

    if (!$user) {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Non autenticato'], 401);
        }
        return redirect()->route('login');
    }

    if (!$user->isAttivo()) {
        auth()->logout();
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Account disattivato'], 403);
        }
        abort(403, 'Account disattivato. Contatta l\'amministratore.');
    }

    if (!$user->hasPermission($modulo, $azione)) {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Permesso negato'], 403);
        }
        abort(403, 'Non hai i permessi per accedere a questa sezione.');
    }

    return $next($request);
}
}
