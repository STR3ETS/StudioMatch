<?php

namespace App\Providers;

use App\Support\InApp;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Password::defaults(fn () => Password::min(8)->letters()->mixedCase()->numbers());

        // App-tokens (api/app): na 60 dagen niet gebruiken opnieuw inloggen. Sanctum's eigen 'expiration'
        // telt vanaf het aanmaken; dit telt vanaf het laatste gebruik, zodat dagelijks gebruik ingelogd blijft.
        Sanctum::authenticateAccessTokensUsing(fn (PersonalAccessToken $token, bool $isValid) => $isValid
            && ($token->last_used_at ?? $token->created_at)->gt(now()->subDays(60)));

        // @inapp ... @else ... @endinapp: een websitepagina die in de iOS/Android-app openstaat.
        Blade::if('inapp', fn () => InApp::active());

        Event::listen(function (MessageSending $event) {
            $html = $event->message->getHtmlBody();

            if (! is_string($html) || ! str_contains($html, '/logos/sm-primary-logo-blauw.png')) {
                return;
            }

            $event->message->embedFromPath(public_path('logos/sm-primary-logo-blauw.png'), 'sm-logo', 'image/png');

            $event->message->html(preg_replace(
                '/https?:\/\/[^"\s]*\/logos\/sm-primary-logo-blauw\.png[^"\s]*/',
                'cid:sm-logo',
                $html,
            ));
        });
    }
}
