<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Artists cannot use the platform until their own address is on file. Blocks every
 * page except the account form itself, so there is no way to book or browse the
 * dashboard with an empty profile.
 */
class EnsureProfileIsComplete
{

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isArtist() && ! $user->hasCompleteAddress()) {
            return redirect()->route('account.edit');
        }

        return $next($request);
    }
}
