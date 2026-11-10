<?php

namespace App\Http\Controllers;

use App\Support\InApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Van de iOS/Android-app naar een serverpagina: de eenmalige code (Api\App\SessionController::webCode)
 * logt de gebruiker in de webomgeving in. De sessie onthoudt waar de app draait, zodat de serverpagina
 * een knop terug naar de app toont (x-app-return) en uitloggen op de server ook de app uitlogt.
 */
class AppWebSessionController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = Cache::pull('app-web-code:'.$request->string('code'));

        if ($data === null) {
            return redirect()->route('login');
        }

        Auth::guard('web')->loginUsingId($data['user'], remember: true);
        $request->session()->regenerate();
        $request->session()->put('app_bundle', $data['terug']);
        $request->session()->put('locale', Auth::user()->locale);

        return redirect($data['naar']);
    }

    /**
     * De balk terug naar de app op een pagina zonder sessie (zoals een 404): de sessie weet het adres van
     * de app hier wel, anders het gebruikelijke adres voor dit soort telefoon. Met ?scherm=studios naar dat
     * scherm in de app (alleen een schermnaam, eventueel met een nummer).
     */
    public function back(Request $request): Response
    {
        $screen = (string) $request->query('scherm', '');

        return InApp::toApp(preg_match('/^[a-z-]+(\/\d+)?$/', $screen) === 1 ? '#/'.$screen : '', $request);
    }
}
