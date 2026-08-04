@php
    $money = fn (int $cents) => '€ ' . number_format($cents / 100, 2, ',', '.');
@endphp

<x-host-layout :title="__('host.bookings.title')" active="inbox">
    <h1 class="text-2xl font-bold text-prussian-blue">{{ __('host.bookings.title') }}</h1>
    <p class="mt-2 text-prussian-blue/60">{{ __('host.bookings.subtitle') }}</p>

    {{-- Nieuwe aanvragen --}}
    <section class="mt-8">
        <h2 class="text-lg font-bold text-prussian-blue">{{ __('host.bookings.requests_title') }}</h2>

        @if ($requests->isEmpty())
            <p class="mt-4 rounded-xl border border-dashed border-prussian-blue/20 bg-white px-4 py-6 text-center text-sm text-prussian-blue/50">{{ __('host.bookings.requests_empty') }}</p>
        @else
            <div class="mt-4 space-y-3">
                @foreach ($requests as $booking)
                    <div class="flex flex-wrap items-center gap-4 rounded-2xl border border-amber-500/30 bg-white p-5 lg:flex-nowrap">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-prussian-blue/10 text-sm font-bold text-prussian-blue">{{ strtoupper(mb_substr($booking->user->name, 0, 1)) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-prussian-blue">{{ $booking->user->name }}</p>
                            <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-prussian-blue/60">
                                <span><i class="fa-solid fa-door-open fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->room->title }}</span>
                                <span><i class="fa-solid fa-calendar-days fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->date->translatedFormat('D j M Y') }}</span>
                                <span><i class="fa-solid fa-clock fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->timeRange() }}</span>
                                <span><i class="fa-solid fa-euro-sign fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $money($booking->rent_cents) }} {{ __('host.bookings.rent_for_you') }}</span>
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <form method="POST" action="{{ route('host.bookings.accept', $booking) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="cursor-pointer rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                    <i class="fa-solid fa-check fa-sm mr-1"></i>{{ __('host.bookings.accept') }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('host.bookings.decline', $booking) }}" data-confirm="{{ __('host.bookings.decline_confirm') }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="cursor-pointer rounded-full border border-prussian-blue/20 px-5 py-2.5 text-sm font-semibold text-prussian-blue transition hover:bg-ruby-red/10 hover:text-ruby-red">
                                    {{ __('host.bookings.decline') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Probleemmeldingen: gemeld door de artiest, bemiddeld door StudioMatch --}}
    @if ($disputes->isNotEmpty())
        <section class="mt-10">
            <h2 class="text-lg font-bold text-prussian-blue">{{ __('host.bookings.disputes_title') }}</h2>
            <div class="mt-4 space-y-3">
                @foreach ($disputes as $booking)
                    <div class="rounded-2xl border border-prussian-blue/10 bg-white p-5">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-bold text-prussian-blue">{{ $booking->room->title }}</p>
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $booking->status->badgeClasses() }}">{{ __('booking.status.' . $booking->status->value) }}</span>
                        </div>
                        <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-prussian-blue/60">
                            <span><i class="fa-solid fa-calendar-days fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->date->translatedFormat('D j M Y') }} {{ $booking->timeRange() }}</span>
                            <span><i class="fa-solid fa-user fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->user->name }}</span>
                            <span><i class="fa-solid fa-clock fa-xs mr-1.5 text-prussian-blue/40"></i>{{ __('host.bookings.disputed_on', ['date' => $booking->disputed_at->translatedFormat('j M Y')]) }}</span>
                        </p>
                        <p class="mt-2 rounded-xl bg-prussian-blue/[0.03] px-4 py-3 text-sm leading-relaxed text-prussian-blue/70">{{ $booking->dispute_reason }}</p>

                        @if ($booking->wasDisputeDismissed())
                            <p class="mt-2 flex items-start gap-2 text-sm font-medium text-emerald-600">
                                <i class="fa-solid fa-circle-check mt-0.5"></i> {{ __('host.bookings.dispute_dismissed') }}
                            </p>
                        @elseif ($booking->wasDisputeUpheld())
                            <p class="mt-2 flex items-start gap-2 text-sm font-medium text-ruby-red">
                                <i class="fa-solid fa-circle-xmark mt-0.5"></i> {{ __('host.bookings.dispute_upheld') }}
                            </p>
                        @else
                            <p class="mt-2 flex items-start gap-2 text-sm font-medium text-amber-600">
                                <i class="fa-solid fa-hourglass-half mt-0.5"></i> {{ __('host.bookings.dispute_open') }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Komende bevestigde sessies --}}
    <section class="mt-10">
        <h2 class="text-lg font-bold text-prussian-blue">{{ __('host.bookings.upcoming_title') }}</h2>

        @if ($upcoming->isEmpty())
            <p class="mt-4 rounded-xl border border-dashed border-prussian-blue/20 bg-white px-4 py-6 text-center text-sm text-prussian-blue/50">{{ __('host.bookings.upcoming_empty') }}</p>
        @else
            <div class="mt-4 space-y-3">
                @foreach ($upcoming as $booking)
                    <div class="flex flex-wrap items-center gap-4 rounded-2xl border border-prussian-blue/10 bg-white p-5 lg:flex-nowrap">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600"><i class="fa-solid fa-calendar-check"></i></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-bold text-prussian-blue">{{ $booking->user->name }}</p>
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $booking->status->badgeClasses() }}">{{ __('booking.status.' . $booking->status->value) }}</span>
                            </div>
                            <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-prussian-blue/60">
                                <span><i class="fa-solid fa-door-open fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->room->title }}</span>
                                <span><i class="fa-solid fa-calendar-days fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->date->translatedFormat('D j M Y') }}</span>
                                <span><i class="fa-solid fa-clock fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->timeRange() }}</span>
                                <span><i class="fa-solid fa-envelope fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->user->email }}</span>
                            </p>
                        </div>
                        <p class="shrink-0 font-bold text-prussian-blue">{{ $money($booking->rent_cents) }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</x-host-layout>
