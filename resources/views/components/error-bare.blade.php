@props(['code', 'heading', 'text'])

{{--
    Foutpagina voor 5xx. Bewust zonder header, footer of gebouwde assets: als de server
    of de database eruit ligt, kan die er zelf niet nog eens over struikelen. De opmaak
    staat daarom hier in de pagina en de enige externe verwijzing is een statisch logo.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <meta name="theme-color" content="#ad0924">

        <title>{{ $heading }} · {{ config('app.name', 'StudioMatch') }}</title>

        <link rel="icon" type="image/png" href="/logos/sm-mark-rood.png">

        <style>
            *, *::before, *::after { box-sizing: border-box; }

            body {
                margin: 0;
                min-height: 100dvh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem 1.5rem;
                background: #ad0924;
                color: #ffffff;
                font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
                text-align: center;
                line-height: 1.6;
            }

            .wrap { width: 100%; max-width: 34rem; }

            .logo { height: 2.75rem; width: auto; margin-bottom: 2.5rem; }

            .code {
                margin: 0;
                font-size: clamp(5rem, 22vw, 9rem);
                font-weight: 900;
                line-height: 1;
                color: transparent;
                -webkit-text-stroke: 2px rgb(255 255 255 / 0.2);
                user-select: none;
            }

            h1 { margin: 0.5rem 0 0; font-size: 1.75rem; font-weight: 700; }

            p.text { margin: 1rem 0 0; color: rgb(255 255 255 / 0.65); }

            .button {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                margin-top: 2.25rem;
                padding: 0.8rem 1.6rem;
                border-radius: 9999px;
                background: #ffffff;
                color: #ad0924;
                font-size: 0.875rem;
                font-weight: 600;
                text-decoration: none;
                transition: opacity 0.15s ease;
            }

            .button:hover { opacity: 0.9; }

            @media (prefers-reduced-motion: reduce) {
                .button { transition: none; }
            }
        </style>
    </head>
    <body>
        <div class="wrap">
            <img src="/logos/sm-primary-logo-wit.png" alt="{{ config('app.name', 'StudioMatch') }}" class="logo">

            <p class="code">{{ $code }}</p>
            <h1>{{ $heading }}</h1>
            <p class="text">{{ $text }}</p>

            <a href="{{ url('/') }}" class="button">{{ __('errors.home') }}</a>
        </div>
    </body>
</html>
