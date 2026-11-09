<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rolscheiding in de app-API: een artiest krijgt nooit gegevens van een verhuurder en andersom.
 * Anders dan op de website (EnsureUserHasRole) geen doorverwijzing en ook geen uitzondering voor
 * de admin; die gebruikt in de app de serverpagina's.
 */
class EnsureAppRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        abort_unless($request->user()?->role->value === $role, 403, __('app.errors.wrong_role'));

        return $next($request);
    }
}
