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

<x-dashboard-layout :title="$title">
    <div class="flex flex-col gap-8 lg:flex-row lg:gap-10">
        <aside class="lg:w-64 lg:shrink-0">
            <nav class="flex gap-1.5 overflow-x-auto pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden lg:sticky lg:top-10 lg:flex-col lg:pb-0">
                @foreach ($items as $item)
                    @if ($item['url'])
                        <a href="{{ $item['url'] }}"
                           @class([
                               'flex shrink-0 items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition',
                               'bg-prussian-blue text-white' => $active === $item['key'],
                               'text-prussian-blue/70 hover:bg-prussian-blue/5 hover:text-prussian-blue' => $active !== $item['key'],
                           ])>
                            <i class="fa-solid {{ $item['icon'] }} fa-sm w-4 text-center"></i> {{ __('host.nav.' . $item['key']) }}
                        </a>
                    @else
                        <span class="flex shrink-0 cursor-default items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold text-prussian-blue/30">
                            <i class="fa-solid {{ $item['icon'] }} fa-sm w-4 text-center"></i> {{ __('host.nav.' . $item['key']) }}
                        </span>
                    @endif
                @endforeach
            </nav>
        </aside>

        <div class="min-w-0 flex-1">
            {{ $slot }}
        </div>
    </div>
</x-dashboard-layout>
