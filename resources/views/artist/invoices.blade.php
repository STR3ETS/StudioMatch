@php
    $money = fn (int $cents) => '€ ' . number_format($cents / 100, 2, ',', '.');
@endphp

<x-artist-layout :title="__('invoice.artist.title')" active="invoices">
    <h1 class="text-2xl font-bold text-prussian-blue">{{ __('invoice.artist.title') }}</h1>
    <p class="mt-2 text-prussian-blue/60">{{ __('invoice.artist.subtitle') }}</p>

    @if ($bookings->isEmpty())
        <p class="mt-8 rounded-xl border border-dashed border-prussian-blue/20 bg-white px-4 py-6 text-center text-sm text-prussian-blue/50">{{ __('invoice.artist.empty') }}</p>
    @else
        <div class="mt-8 space-y-4">
            @foreach ($bookings as $booking)
                <div class="rounded-2xl border border-prussian-blue/10 bg-white p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="font-bold text-prussian-blue">{{ $booking->room->studio->name }} - {{ $booking->room->title }}</h2>
                            <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-prussian-blue/60">
                                <span><i class="fa-solid fa-calendar-days fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->date->translatedFormat('j F Y') }}</span>
                                <span><i class="fa-solid fa-clock fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->timeRange() }}</span>
                            </p>
                        </div>
                        <p class="shrink-0 font-bold text-prussian-blue">{{ $money($booking->total_cents) }}</p>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach (\App\Support\Invoices::documentsFor($booking) as $type)
                            <a href="{{ route('invoices.download', [$booking, $type]) }}"
                               class="inline-flex items-center gap-2 rounded-full border border-prussian-blue/15 px-4 py-2 text-xs font-semibold text-prussian-blue transition hover:border-prussian-blue/40 hover:bg-prussian-blue/5">
                                <i class="fa-solid fa-file-arrow-down fa-sm text-prussian-blue/40"></i>{{ __('invoice.labels.' . $type) }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-artist-layout>
