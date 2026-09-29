@props(['title' => null, 'description' => null, 'schema' => null, 'image' => null, 'type' => 'website', 'robots' => 'index, follow'])

@php
    $siteName = config('app.name', 'StudioMatch');
    $pageTitle = $title ? $title . ' · ' . $siteName : $siteName . ' · Every sound deserves a studio';
    $metaDescription = $description ?? __('seo.default_description');
    $currentUrl = url()->current();
    $ogImage = $image ?? url('/temp-studio-1.webp');
    $ogLocale = ['nl' => 'nl_NL', 'en' => 'en_GB'][app()->getLocale()] ?? 'nl_NL';

    $structuredData = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $siteName,
            'url' => url('/'),
            'logo' => url('/logos/sm-primary-logo-blauw.png'),
            'slogan' => 'Every sound deserves a studio',
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $siteName,
            'url' => url('/'),
            'inLanguage' => app()->getLocale(),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => url('/studios') . '?location={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ],
    ];

    if ($schema !== null) {
        $structuredData[] = $schema;
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="{{ $robots }}">
        <meta name="theme-color" content="#101529">

        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $metaDescription }}">
        <link rel="canonical" href="{{ $currentUrl }}">

        <meta property="og:type" content="{{ $type }}">
        <meta property="og:site_name" content="{{ $siteName }}">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:url" content="{{ $currentUrl }}">
        <meta property="og:image" content="{{ $ogImage }}">
        <meta property="og:locale" content="{{ $ogLocale }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $pageTitle }}">
        <meta name="twitter:description" content="{{ $metaDescription }}">
        <meta name="twitter:image" content="{{ $ogImage }}">

        <link rel="icon" type="image/png" href="/logos/sm-mark-rood.png">
        <link rel="apple-touch-icon" href="/logos/sm-mark-rood.png">

        <link rel="preload" href="{{ asset('fontawesome/css/all.min.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
        <noscript><link rel="stylesheet" href="{{ asset('fontawesome/css/all.min.css') }}"></noscript>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @foreach ($structuredData as $schema)
            <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
        @endforeach
    </head>
    {{--
        Kolom over de volle schermhoogte, zodat de footer bij een korte pagina onderaan
        het scherm blijft plakken in plaats van halverwege te eindigen met wit eronder.
        dvh in plaats van vh: op mobiel is 100vh groter dan het zichtbare venster, wat
        op korte pagina's een overbodige scrollbalk oplevert.
    --}}
    <body class="flex min-h-dvh flex-col">
        <x-header />

        {{-- Kolom, zodat een korte pagina zoals een foutmelding zich kan uitrekken tot de footer. --}}
        <main class="flex flex-1 flex-col">
            {{ $slot }}
        </main>

        <x-footer />

        <x-cookie-banner />
    </body>
</html>
