@props(['title'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <meta name="theme-color" content="#ad0924">

        <title>{{ $title }} · {{ config('app.name', 'StudioMatch') }}</title>

        <link rel="icon" type="image/png" href="/logos/sm-sub-mark-logo-blauw.png">
        <link rel="apple-touch-icon" href="/logos/sm-sub-mark-logo-blauw.png">

        <link rel="preload" href="{{ asset('fontawesome/css/all.min.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
        <noscript><link rel="stylesheet" href="{{ asset('fontawesome/css/all.min.css') }}"></noscript>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="flex min-h-screen">
            {{-- Formulier --}}
            <div class="flex w-full flex-col px-6 py-8 sm:px-12 lg:w-1/2 xl:w-[45%]">
                <div class="flex items-center justify-between">
                    <a href="{{ route('home') }}"><img src="/logos/sm-primary-logo-blauw.png" alt="StudioMatch" class="h-9 w-auto"></a>
                    <a href="{{ route('home') }}" class="flex items-center gap-1.5 text-sm font-semibold text-prussian-blue/50 transition hover:text-prussian-blue">
                        <i class="fa-solid fa-arrow-left fa-xs"></i> {{ __('auth.back_home') }}
                    </a>
                </div>

                <div data-reveal class="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center py-12">
                    {{ $slot }}
                </div>

                <div class="flex items-center justify-between text-xs text-prussian-blue/40">
                    <span>&copy; {{ date('Y') }} StudioMatch</span>
                    <div class="flex items-center gap-1">
                        @foreach (config('localization.supported') as $code => $label)
                            <a href="{{ route('language.switch', $code) }}"
                               @class([
                                   'rounded-full px-2.5 py-1 font-bold uppercase transition',
                                   'bg-prussian-blue/10 text-prussian-blue' => app()->getLocale() === $code,
                                   'text-prussian-blue/40 hover:text-prussian-blue' => app()->getLocale() !== $code,
                               ])>{{ $code }}</a>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Merkpaneel --}}
            <div class="relative hidden overflow-hidden bg-ruby-red lg:flex lg:w-1/2 xl:w-[55%]">
                <x-floating-icons />
                <div class="relative z-10 m-auto max-w-md px-10 text-center">
                    <img src="/logos/sm-primary-logo-wit.png" alt="StudioMatch" class="mx-auto h-12 w-auto">
                    <p class="mt-8 text-3xl font-bold leading-snug text-white">{{ __('auth.side.tagline') }}</p>
                    <p class="mt-4 text-white/70">{{ __('auth.side.text') }}</p>
                </div>
            </div>
        </div>
    </body>
</html>
