<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Taal voor de app-API (api/app/*). Daar is geen sessie: de taal van het account gaat voor,
 * anders (bij het inloggen) de taal die de app meestuurt in Accept-Language.
 */
class SetAppLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('localization.supported', []));
        $locale = $request->user('sanctum')?->locale;

        if (! in_array($locale, $supported, true)) {
            $locale = $request->getPreferredLanguage($supported);
        }

        if (in_array($locale, $supported, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
