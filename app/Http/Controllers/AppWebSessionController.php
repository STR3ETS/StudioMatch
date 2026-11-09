<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
}
