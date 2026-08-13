@php
    $money = fn (int $cents) => '€ ' . number_format($cents / 100, 2, ',', '.');
@endphp

<x-admin-layout :title="__('admin.revenue_page.title')" active="revenue">
    <h1 class="text-2xl font-bold text-prussian-blue">{{ __('admin.revenue_page.title') }}</h1>
    <p class="mt-2 text-prussian-blue/60">{{ __('admin.revenue_page.subtitle') }}</p>

    <div class="mt-8 grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-prussian-blue/5 text-prussian-blue"><i class="fa-solid fa-calendar-check"></i></span>
            <p class="mt-4 text-3xl font-bold text-prussian-blue">{{ $totalCount }}</p>
            <p class="mt-1 text-sm text-prussian-blue/60">{{ __('admin.revenue_page.total_bookings') }}</p>
        </div>
        <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600"><i class="fa-solid fa-euro-sign"></i></span>
            <p class="mt-4 text-3xl font-bold text-prussian-blue">{{ $money($totalRent) }}</p>
            <p class="mt-1 text-sm text-prussian-blue/60">{{ __('admin.revenue_page.total_rent') }}</p>
        </div>
        <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-ruby-red/10 text-ruby-red"><i class="fa-solid fa-chart-line"></i></span>
            <p class="mt-4 text-3xl font-bold text-prussian-blue">{{ $money($totalFees) }}</p>
            <p class="mt-1 text-sm text-prussian-blue/60">{{ __('admin.revenue_page.total_fees') }}</p>
        </div>
    </div>

    <section class="mt-8">
        <h2 class="text-lg font-bold text-prussian-blue">{{ __('admin.revenue_page.per_studio') }}</h2>

        @if ($studios->isEmpty())
            <p class="mt-4 rounded-xl border border-dashed border-prussian-blue/20 bg-white px-4 py-8 text-center text-sm text-prussian-blue/50">{{ __('admin.revenue_page.empty') }}</p>
        @else
            <div class="mt-4 overflow-hidden rounded-2xl border border-prussian-blue/10 bg-white">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-prussian-blue/10 text-left text-xs font-bold uppercase tracking-wide text-prussian-blue/50">
                                <th class="px-5 py-3.5">{{ __('admin.revenue_page.col_studio') }}</th>
                                <th class="px-5 py-3.5">{{ __('admin.revenue_page.col_host') }}</th>
                                <th class="px-5 py-3.5">{{ __('admin.revenue_page.col_bookings') }}</th>
                                <th class="px-5 py-3.5 text-right">{{ __('admin.revenue_page.col_rent') }}</th>
                                <th class="px-5 py-3.5 text-right">{{ __('admin.revenue_page.col_fees') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($studios as $row)
                                <tr class="border-b border-prussian-blue/5 last:border-0">
                                    <td class="px-5 py-3.5">
                                        <p class="font-semibold text-prussian-blue">{{ $row['studio']->name }}</p>
                                        <p class="text-xs text-prussian-blue/50">{{ $row['studio']->city }}</p>
                                    </td>
                                    <td class="px-5 py-3.5 text-prussian-blue/70">{{ $row['studio']->user->name }}</td>
                                    <td class="px-5 py-3.5 text-prussian-blue/70">{{ $row['count'] }}</td>
                                    <td class="px-5 py-3.5 text-right font-semibold text-prussian-blue">{{ $money($row['rent']) }}</td>
                                    <td class="px-5 py-3.5 text-right font-semibold text-prussian-blue">{{ $money($row['fees']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </section>
</x-admin-layout>
