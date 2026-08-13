@php
    $money = fn (int $cents) => '€ ' . number_format($cents / 100, 2, ',', '.');
@endphp

<x-layout :title="__('booking.payment.title')">
    <div class="pt-28 pb-16">
        <div class="mx-auto max-w-xl px-6">
            <h1 data-reveal class="text-3xl font-bold text-prussian-blue">{{ __('booking.payment.title') }}</h1>
            <p data-reveal class="mt-2 text-prussian-blue/60">{{ __('booking.payment.subtitle') }}</p>

            <div data-reveal style="--reveal-delay: .1s" class="mt-8 rounded-2xl border border-prussian-blue/10 bg-white p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="font-bold text-prussian-blue">{{ $booking->room->title }}</h2>
                        <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-prussian-blue/60">
                            <span><i class="fa-solid fa-calendar-days fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->date->translatedFormat('l j F Y') }}</span>
                            <span><i class="fa-solid fa-clock fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->timeRange() }}</span>
                        </p>
                    </div>
                    <p class="shrink-0 text-xl font-bold text-prussian-blue">{{ $money($booking->total_cents) }}</p>
                </div>

                <div class="mt-5 flex items-center justify-between rounded-xl bg-prussian-blue/[0.03] px-4 py-3">
                    <span class="text-sm text-prussian-blue/60">{{ __('booking.payment.time_left') }}</span>
                    <span data-countdown data-expires="{{ $booking->expires_at->timestamp }}" class="text-lg font-bold tabular-nums text-prussian-blue">--:--</span>
                </div>

                <x-info-note class="mt-3">{{ __('booking.payment.hold_note', ['time' => $booking->expires_at->format('H:i')]) }}</x-info-note>

                <div class="mt-5 rounded-xl border border-dashed border-amber-500/40 bg-amber-500/5 px-4 py-3 text-xs leading-relaxed text-prussian-blue/60">
                    <i class="fa-solid fa-flask mr-1 text-amber-500"></i> {{ __('booking.payment.demo_note') }}
                </div>

                <form method="POST" action="{{ route('bookings.pay', $booking) }}" class="mt-6">
                    @csrf
                    <button type="submit" class="w-full cursor-pointer rounded-full bg-ruby-red py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                        <i class="fa-solid fa-lock fa-sm mr-1.5"></i>{{ __('booking.payment.submit', ['amount' => $money($booking->total_cents)]) }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const el = document.querySelector('[data-countdown]');
            if (! el) return;
            const expires = parseInt(el.dataset.expires, 10) * 1000;
            const tick = () => {
                const secondsLeft = Math.max(0, Math.floor((expires - Date.now()) / 1000));
                el.textContent = String(Math.floor(secondsLeft / 60)).padStart(2, '0') + ':' + String(secondsLeft % 60).padStart(2, '0');
                if (secondsLeft <= 0) {
                    clearInterval(timer);
                    window.location.reload();
                }
                if (secondsLeft <= 120) el.classList.add('text-ruby-red');
            };
            const timer = setInterval(tick, 1000);
            tick();
        })();
    </script>
</x-layout>
