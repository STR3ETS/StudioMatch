@props(['title' => null, 'description' => null, 'schema' => null])

@php
    $siteName = config('app.name', 'StudioMatch');
    $pageTitle = $title ? $title . ' · ' . $siteName : $siteName . ' · Every sound deserves a studio';
    $metaDescription = $description ?? __('seo.default_description');
    $currentUrl = url()->current();
    $ogImage = url('/temp-studio-1.webp');
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
        <meta name="robots" content="index, follow">
        <meta name="theme-color" content="#101529">

        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $metaDescription }}">
        <link rel="canonical" href="{{ $currentUrl }}">

        <meta property="og:type" content="website">
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

        <link rel="icon" type="image/png" href="/logos/sm-sub-mark-logo-blauw.png">
        <link rel="apple-touch-icon" href="/logos/sm-sub-mark-logo-blauw.png">

        <link rel="preload" href="{{ asset('fontawesome/css/all.min.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
        <noscript><link rel="stylesheet" href="{{ asset('fontawesome/css/all.min.css') }}"></noscript>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @foreach ($structuredData as $schema)
            <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
        @endforeach
    </head>
    <body>
        <x-header />

        {{ $slot }}

        <x-footer />

        <x-cookie-banner />
    </body>
</html>
