@props(['title', 'active' => null])

@php
    $items = [
        ['key' => 'overview', 'icon' => 'fa-gauge-high', 'url' => route('dashboard.admin'), 'mobile' => true],
        ['key' => 'queue', 'icon' => 'fa-clipboard-check', 'url' => route('admin.queue.index'), 'mobile' => true],
        ['key' => 'bookings', 'icon' => 'fa-calendar-check', 'url' => route('admin.bookings.index'), 'mobile' => true],
        ['key' => 'users', 'icon' => 'fa-users', 'url' => route('admin.users.index')],
        ['key' => 'revenue', 'icon' => 'fa-chart-line', 'url' => route('admin.revenue')],
        ['key' => 'tickets', 'icon' => 'fa-life-ring', 'url' => route('admin.tickets.index'), 'mobile' => true],
        ['key' => 'account', 'icon' => 'fa-user-gear', 'url' => route('account.edit')],
    ];
@endphp

<x-dashboard-layout :title="$title" :nav="$items" :active="$active" lang-prefix="admin.nav.">
    {{ $slot }}
</x-dashboard-layout>
