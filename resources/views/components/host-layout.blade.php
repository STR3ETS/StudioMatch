@props(['title', 'active' => null])

@php
    $items = [
        ['key' => 'overview', 'icon' => 'fa-gauge-high', 'url' => route('dashboard.host')],
        ['key' => 'profile', 'icon' => 'fa-briefcase', 'url' => route('host.profile.edit')],
        ['key' => 'studios', 'icon' => 'fa-building', 'url' => route('host.studios.index')],
        ['key' => 'availability', 'icon' => 'fa-calendar-days', 'url' => route('host.availability.index')],
        ['key' => 'inbox', 'icon' => 'fa-inbox', 'url' => route('host.bookings.index')],
        ['key' => 'agenda', 'icon' => 'fa-calendar', 'url' => route('host.agenda')],
        ['key' => 'revenue', 'icon' => 'fa-chart-line', 'url' => route('host.revenue')],
        ['key' => 'invoices', 'icon' => 'fa-file-invoice', 'url' => route('host.invoices.index')],
        ['key' => 'damage', 'icon' => 'fa-triangle-exclamation', 'url' => route('host.damage.index')],
        ['key' => 'stripe', 'icon' => 'fa-shield-halved', 'url' => route('host.stripe.show')],
        ['key' => 'account', 'icon' => 'fa-user-gear', 'url' => route('account.edit')],
    ];
@endphp

<x-dashboard-layout :title="$title" :nav="$items" :active="$active" lang-prefix="host.nav.">
    {{ $slot }}
</x-dashboard-layout>
