@props(['title', 'active' => null])

@php
    $items = [
        ['key' => 'overview', 'icon' => 'fa-gauge-high', 'url' => route('dashboard.admin')],
        ['key' => 'queue', 'icon' => 'fa-clipboard-check', 'url' => route('admin.queue.index')],
        ['key' => 'bookings', 'icon' => 'fa-calendar-check', 'url' => route('admin.bookings.index')],
        ['key' => 'users', 'icon' => 'fa-users', 'url' => route('admin.users.index')],
        ['key' => 'revenue', 'icon' => 'fa-chart-line', 'url' => route('admin.revenue')],
        ['key' => 'tickets', 'icon' => 'fa-life-ring', 'url' => route('admin.tickets.index')],
        ['key' => 'account', 'icon' => 'fa-user-gear', 'url' => route('account.edit')],
    ];
@endphp

<x-dashboard-layout :title="$title">
    <div class="flex flex-col gap-8 lg:flex-row lg:gap-10">
        <aside class="lg:w-64 lg:shrink-0 [view-transition-name:dash-sidebar]">
            <nav class="flex gap-1.5 overflow-x-auto pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden lg:sticky lg:top-10 lg:flex-col lg:pb-0">
                @foreach ($items as $item)
                    @if ($item['url'])
                        <a href="{{ $item['url'] }}"
                           @class([
                               'flex shrink-0 items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition',
                               'bg-prussian-blue text-white' => $active === $item['key'],
                               'text-prussian-blue/70 hover:bg-prussian-blue/5 hover:text-prussian-blue' => $active !== $item['key'],
                           ])>
                            <i class="fa-solid {{ $item['icon'] }} fa-sm w-4 text-center"></i> {{ __('admin.nav.' . $item['key']) }}
                        </a>
                    @else
                        <span class="flex shrink-0 cursor-default items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold text-prussian-blue/30">
                            <i class="fa-solid {{ $item['icon'] }} fa-sm w-4 text-center"></i> {{ __('admin.nav.' . $item['key']) }}
                        </span>
                    @endif
                @endforeach
            </nav>
        </aside>

        <div class="min-w-0 flex-1 [view-transition-name:dash-content]">
            {{ $slot }}
        </div>
    </div>
</x-dashboard-layout>
