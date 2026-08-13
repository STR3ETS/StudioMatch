@props(['title', 'active' => null])

@php
    $items = [
        ['key' => 'overview', 'icon' => 'fa-gauge-high', 'url' => route('dashboard.artist')],
        ['key' => 'account', 'icon' => 'fa-user-gear', 'url' => route('account.edit')],
        ['key' => 'invoices', 'icon' => 'fa-file-invoice', 'url' => route('artist.invoices.index')],
    ];
@endphp

<x-dashboard-layout :title="$title" :nav="$items" :active="$active" lang-prefix="dashboard.artist_nav.">
    {{ $slot }}
</x-dashboard-layout>
