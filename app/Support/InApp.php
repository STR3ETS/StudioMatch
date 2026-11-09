<?php

namespace App\Support;

use App\Http\Controllers\Api\App\SessionController;
use Illuminate\Http\Request;

/**
 * Of een websitepagina in de iOS/Android-app openstaat. Dan laat de pagina de eigen navigatie van de
 * website weg (kop, onderbalk, footer, cookiemelding) en staat er één balk terug naar de app (x-app-return).
 *
 * Twee kenmerken: de sessie onthoudt het adres van de app (AppWebSessionController), en de app zet
 * "StudioMatchApp" in de user agent (capacitor.config.ts). Een foutpagina zoals 404 heeft geen sessie,
 * dus daar telt alleen de user agent.
 */
class InApp
{
    public const USER_AGENT = 'StudioMatchApp';

    public static function active(?Request $request = null): bool
    {
        $request ??= request();

        return self::origin($request) !== null || str_contains((string) $request->userAgent(), self::USER_AGENT);
    }

    /** Het adres van de app in deze webview, als de sessie het kent. */
    public static function origin(?Request $request = null): ?string
    {
        $request ??= request();
        $origin = $request->hasSession() ? $request->session()->get('app_bundle') : null;

        return in_array($origin, SessionController::APP_ORIGINS, true) ? $origin : null;
    }

    /**
     * Waar de app draait als de sessie het niet weet: op een iPhone capacitor://localhost, op Android
     * https://localhost (of http, als de server zelf over http loopt, zie androidScheme in capacitor.config.ts).
     */
    public static function guessOrigin(?Request $request = null): string
    {
        $request ??= request();

        if (preg_match('/iPhone|iPad|iPod/', (string) $request->userAgent())) {
            return 'capacitor://localhost';
        }

        return $request->isSecure() ? 'https://localhost' : 'http://localhost';
    }
}
