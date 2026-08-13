<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{

    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if ($user->role->value !== $role && ! $user->isAdmin()) {
            return redirect()->route($user->role->dashboardRoute());
        }

        return $next($request);
    }
}
