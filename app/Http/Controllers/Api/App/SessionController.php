<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AppMenu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Inloggen in de iOS/Android-app (mobile/app): een token per telefoon in plaats van een sessiecookie,
 * want de app draait op een eigen adres (https://localhost, capacitor://localhost) en niet op de server.
 */
class SessionController extends Controller
{
    /** Adressen waarop de app zelf draait: alleen die mogen de app-API aanroepen (CORS) en alleen daarheen verwijst een serverpagina terug. */
    public const APP_ORIGINS = ['https://localhost', 'http://localhost', 'capacitor://localhost'];

    /** Hoe lang een eenmalige webcode geldig is. */
    public const WEB_CODE_SECONDS = 60;

    public function store(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        // Vanaf nu in de taal van het account, net als op de website na het inloggen.
        if (array_key_exists((string) $user->locale, config('localization.supported', []))) {
            app()->setLocale($user->locale);
        }

        return response()->json([
            'token' => $user->createToken($credentials['device'] ?? 'StudioMatch-app')->plainTextToken,
            ...$this->profile($user),
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json($this->profile($request->user()));
    }

    /**
     * De teksten van de app, ook vóór het inloggen (het inlogscherm). Taal uit ?locale, anders de taal
     * van de telefoon (Accept-Language, via SetAppLocale).
     */
    public function texts(Request $request): JsonResponse
    {
        $locale = $request->query('locale');
        if (is_string($locale) && array_key_exists($locale, config('localization.supported', []))) {
            app()->setLocale($locale);
        }

        return response()->json([
            'locale' => app()->getLocale(),
            'locales' => config('localization.supported', []),
            'ui' => __('app.ui'),
        ]);
    }

    public function destroy(Request $request): Response
    {
        // Deze telefoon krijgt na uitloggen geen pushmeldingen meer.
        if ($request->filled('push_token')) {
            $request->user()->deviceTokens()->where('token', $request->string('push_token'))->delete();
        }

        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    public function locale(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'locale' => ['required', Rule::in(array_keys(config('localization.supported', [])))],
        ]);

        $request->user()->update(['locale' => $validated['locale']]);
        app()->setLocale($validated['locale']);

        return response()->json($this->profile($request->user()));
    }

    /**
     * Eenmalige inlogcode voor een scherm dat (nog) niet in de app zit: de app opent de serverpagina
     * en die logt de gebruiker in (AppWebSessionController). Een minuut geldig, één keer te gebruiken.
     */
    public function webCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'naar' => ['required', 'string', 'max:500', 'starts_with:/', 'not_regex:/^\/\//', 'not_regex:/\\\\/'],
            'terug' => ['required', Rule::in(self::APP_ORIGINS)],
        ]);

        $code = Str::random(48);
        Cache::put("app-web-code:{$code}", [
            'user' => $request->user()->id,
            'naar' => $validated['naar'],
            'terug' => $validated['terug'],
        ], now()->addSeconds(self::WEB_CODE_SECONDS));

        return response()->json(['url' => route('app.web-session', ['code' => $code])]);
    }

    /** @return array<string, mixed> */
    private function profile(User $user): array
    {
        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'first_name' => $user->firstName(),
                'initials' => Str::upper(Str::substr(trim($user->name), 0, 1)),
                'email' => $user->email,
                'role' => $user->role->value,
                'role_label' => __('app.roles.'.$user->role->value),
                'locale' => $user->locale ?? app()->getLocale(),
                'verified' => $user->hasVerifiedEmail(),
            ],
            'locales' => config('localization.supported', []),
            'ui' => __('app.ui'),
            'menu' => AppMenu::for($user),
            'counts' => AppMenu::counts($user),
        ];
    }
}
