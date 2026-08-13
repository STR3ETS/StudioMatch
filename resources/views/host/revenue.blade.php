@php
    $money = fn (int $cents) => '€ ' . number_format($cents / 100, 2, ',', '.');
@endphp

<x-host-layout :title="__('host.revenue.title')" active="revenue">
    <h1 class="text-2xl font-bold text-prussian-blue">{{ __('host.revenue.title') }}</h1>
    <p class="mt-2 text-prussian-blue/60">{{ __('host.revenue.subtitle') }}</p>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-{{ $onHoldTotal > 0 ? 4 : 3 }}">
        <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-ruby-red/10 text-ruby-red"><i class="fa-solid fa-euro-sign"></i></span>
            <p class="mt-4 text-3xl font-bold text-prussian-blue">{{ $money($thisMonthTotal) }}</p>
            <p class="mt-1 text-sm text-prussian-blue/60">{{ __('host.revenue.this_month') }}</p>
        </div>
        <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600"><i class="fa-solid fa-hourglass-half"></i></span>
            <p class="mt-4 text-3xl font-bold text-prussian-blue">{{ $money($expectedTotal) }}</p>
            <p class="mt-1 text-sm text-prussian-blue/60">{{ __('host.revenue.expected') }}</p>
        </div>
        <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600"><i class="fa-solid fa-chart-line"></i></span>
            <p class="mt-4 text-3xl font-bold text-prussian-blue">{{ $money($realisedTotal) }}</p>
            <p class="mt-1 text-sm text-prussian-blue/60">{{ __('host.revenue.total') }}</p>
        </div>
        @if ($onHoldTotal > 0)
            <div class="rounded-2xl border border-ruby-red/30 bg-white p-6">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-ruby-red/10 text-ruby-red"><i class="fa-solid fa-pause"></i></span>
                <p class="mt-4 text-3xl font-bold text-prussian-blue">{{ $money($onHoldTotal) }}</p>
                <p class="mt-1 text-sm text-prussian-blue/60">{{ __('host.revenue.on_hold') }}</p>
            </div>
        @endif
    </div>

    <x-info-note class="mt-6">{{ __('host.revenue.payout_note') }}</x-info-note>

    <section class="mt-8">
        <h2 class="text-lg font-bold text-prussian-blue">{{ __('host.revenue.payouts_title') }}</h2>

        @if ($payouts->isEmpty())
            <p class="mt-4 rounded-xl border border-dashed border-prussian-blue/20 bg-white px-4 py-6 text-center text-sm text-prussian-blue/50">{{ __('host.revenue.payouts_empty') }}</p>
        @else
            <div class="mt-4 overflow-hidden rounded-2xl border border-prussian-blue/10 bg-white">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-prussian-blue/10 text-left text-xs font-bold uppercase tracking-wide text-prussian-blue/50">
                            <th class="px-5 py-3.5">{{ __('host.revenue.col_session') }}</th>
                            <th class="hidden px-5 py-3.5 sm:table-cell">{{ __('host.revenue.col_date') }}</th>
                            <th class="px-5 py-3.5 text-right">{{ __('host.revenue.col_amount') }}</th>
                            <th class="px-5 py-3.5 text-right">{{ __('host.revenue.col_status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payouts as $payout)
                            <tr class="border-b border-prussian-blue/5 last:border-0">
                                <td class="px-5 py-3.5 font-semibold text-prussian-blue">{{ $payout['booking']->room->title }}</td>
                                <td class="hidden px-5 py-3.5 text-prussian-blue/70 sm:table-cell">{{ $payout['booking']->date->translatedFormat('j M Y') }} {{ $payout['booking']->timeRange() }}</td>
                                <td class="px-5 py-3.5 text-right font-semibold text-prussian-blue">{{ $money($payout['amount']) }}</td>
                                <td class="px-5 py-3.5 text-right">
                                    @if ($payout['state'] === 'paid')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-700"><i class="fa-solid fa-circle-check fa-xs"></i>{{ __('host.revenue.payout_paid', ['date' => $payout['booking']->transferred_at->format('d-m-Y')]) }}</span>
                                    @elseif ($payout['state'] === 'paused')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-ruby-red/10 px-3 py-1 text-xs font-semibold text-ruby-red"><i class="fa-solid fa-pause fa-xs"></i>{{ __('host.revenue.payout_paused') }}</span>
                                    @elseif ($payout['state'] === 'scheduled')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-700"><i class="fa-solid fa-clock fa-xs"></i>{{ __('host.revenue.payout_scheduled', ['date' => $payout['booking']->startsAt()->addHours(24)->format('d-m H:i')]) }}</span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-prussian-blue/5 px-3 py-1 text-xs font-semibold text-prussian-blue/70"><i class="fa-solid fa-arrows-rotate fa-xs"></i>{{ __('host.revenue.payout_processing') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="mt-8">
        <h2 class="text-lg font-bold text-prussian-blue">{{ __('host.revenue.months_title') }}</h2>

        @if ($months->isEmpty())
            <p class="mt-4 rounded-xl border border-dashed border-prussian-blue/20 bg-white px-4 py-6 text-center text-sm text-prussian-blue/50">{{ __('host.revenue.months_empty') }}</p>
        @else
            <div class="mt-4 overflow-hidden rounded-2xl border border-prussian-blue/10 bg-white">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-prussian-blue/10 text-left text-xs font-bold uppercase tracking-wide text-prussian-blue/50">
                            <th class="px-5 py-3.5">{{ __('host.revenue.col_month') }}</th>
                            <th class="px-5 py-3.5">{{ __('host.revenue.col_sessions') }}</th>
                            <th class="px-5 py-3.5 text-right">{{ __('host.revenue.col_revenue') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($months as $month)
                            <tr class="border-b border-prussian-blue/5 last:border-0">
                                <td class="px-5 py-3.5 font-semibold capitalize text-prussian-blue">{{ $month['label'] }}</td>
                                <td class="px-5 py-3.5 text-prussian-blue/70">{{ $month['count'] }}</td>
                                <td class="px-5 py-3.5 text-right font-semibold text-prussian-blue">{{ $money($month['total']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-host-layout>
