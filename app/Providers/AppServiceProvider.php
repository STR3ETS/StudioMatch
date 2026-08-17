<?php

namespace App\Providers;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{

    public function register(): void
    {

    }

    public function boot(): void
    {
        Password::defaults(fn () => Password::min(8)->letters()->mixedCase()->numbers());

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
