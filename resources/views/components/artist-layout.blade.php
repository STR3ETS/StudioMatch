@props(['title', 'active' => null])

@php
    $items = [
        ['key' => 'overview', 'icon' => 'fa-gauge-high', 'url' => route('dashboard.artist'), 'mobile' => true],
        ['key' => 'account', 'icon' => 'fa-user-gear', 'url' => route('account.edit'), 'mobile' => true],
        ['key' => 'invoices', 'icon' => 'fa-file-invoice', 'url' => route('artist.invoices.index'), 'mobile' => true],
    ];
@endphp

<x-dashboard-layout :title="$title" :nav="$items" :active="$active" lang-prefix="dashboard.artist_nav.">
    {{ $slot }}
</x-dashboard-layout>
