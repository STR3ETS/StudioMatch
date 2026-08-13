@php
    $money = fn (int $cents) => '€ ' . number_format($cents / 100, 2, ',', '.');
    $houseRules = collect(preg_split('/\r\n|\r|\n/', (string) $room->house_rules))->map(fn ($rule) => trim($rule))->filter()->values();
@endphp

<x-layout :title="__('booking.checkout.title')">
    <div class="pt-28 pb-16">
        <div class="mx-auto max-w-7xl px-6">
            <nav data-reveal class="text-sm text-prussian-blue/50">
                <a href="{{ route('studios.show', $room) }}" class="hover:text-prussian-blue">{{ $room->title }}</a>
                <span class="px-1">/</span>
                <span class="text-prussian-blue/70">{{ __('booking.checkout.title') }}</span>
            </nav>

            <h1 data-reveal class="mt-3 text-3xl font-bold text-prussian-blue">{{ __('booking.checkout.title') }}</h1>
            <p data-reveal class="mt-2 text-prussian-blue/60">{{ __('booking.checkout.subtitle') }}</p>

            <form method="POST" action="{{ route('bookings.store', $room) }}" data-reveal style="--reveal-delay: .1s" class="mt-8 space-y-6">
                @csrf
                <input type="hidden" name="date" value="{{ request('date') }}">
                <input type="hidden" name="start" value="{{ request('start') }}">
                <input type="hidden" name="hours" value="{{ request('hours') }}">

                <div class="flex flex-wrap items-center gap-4 rounded-2xl border border-prussian-blue/10 bg-white p-5 sm:flex-nowrap">
                    @if ($room->photos->isNotEmpty())
                        <img src="{{ $room->photos->first()->thumbUrl() }}" alt="{{ $room->title }}" class="h-20 w-28 shrink-0 rounded-xl object-cover">
                    @endif
                    <div class="min-w-0 flex-1">
                        <h2 class="font-bold text-prussian-blue">{{ $room->title }}</h2>
                        <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-prussian-blue/60">
                            <span><i class="fa-solid fa-building fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $room->studio->name }} ({{ $room->studio->city }})</span>
                            <span><i class="fa-solid fa-calendar-days fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $date->translatedFormat('l j F Y') }}</span>
                            <span><i class="fa-solid fa-clock fa-xs mr-1.5 text-prussian-blue/40"></i>{{ sprintf('%02d:00', $startHour) }} – {{ $endHour === 24 ? '24:00' : sprintf('%02d:00', $endHour) }}</span>
                        </p>
                    </div>
                </div>

                <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6">
                    <h2 class="font-bold text-prussian-blue">{{ __('booking.checkout.price_title') }}</h2>
                    <div class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between text-prussian-blue/70">
                            <span>{{ __('studio.booking.rent', ['count' => $endHour - $startHour]) }}</span>
                            <span>{{ $money($prices['rent_cents']) }}</span>
                        </div>
                        <div class="flex justify-between text-prussian-blue/70">
                            <span>{{ __('studio.booking.service_fee') }}</span>
                            <span>{{ $money($prices['service_fee_cents']) }}</span>
                        </div>
                        <div class="flex justify-between text-prussian-blue/70">
                            <span>{{ __('studio.booking.vat') }}</span>
                            <span>{{ $money($prices['vat_cents']) }}</span>
                        </div>
                        <div class="flex justify-between border-t border-dashed border-prussian-blue/15 pt-3 font-bold text-prussian-blue">
                            <span>{{ __('studio.booking.total') }}</span>
                            <span>{{ $money($prices['total_cents']) }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6">
                    <h2 class="font-bold text-prussian-blue">{{ __('booking.checkout.rules_title') }}</h2>
                    @if ($houseRules->isNotEmpty())
                        <ul class="mt-3 space-y-2">
                            @foreach ($houseRules as $rule)
                                <li class="flex items-start gap-2.5 text-sm text-prussian-blue/70"><i class="fa-solid fa-circle mt-1.5 text-[5px] text-prussian-blue/40"></i> {{ $rule }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-3 text-sm text-prussian-blue/50">{{ __('booking.checkout.no_rules') }}</p>
                    @endif

                    <label class="mt-5 flex cursor-pointer items-start gap-2.5 border-t border-prussian-blue/10 pt-5 text-sm text-prussian-blue/80">
                        <input type="checkbox" name="terms" value="1" class="mt-0.5 h-4 w-4 shrink-0 rounded border-prussian-blue/30 accent-ruby-red" required>
                        <span>{{ __('booking.checkout.terms') }}</span>
                    </label>
                    <x-input-error field="terms" />
                </div>

                @if ($errors->has('slot'))
                    <p class="rounded-xl bg-ruby-red/10 px-4 py-3 text-sm font-semibold text-ruby-red">{{ $errors->first('slot') }}</p>
                @endif

                <x-info-note>{{ __('booking.checkout.hold_note', ['minutes' => config('studio.checkout_hold_minutes')]) }}</x-info-note>

                <div class="flex items-center gap-3">
                    <button type="submit" class="cursor-pointer rounded-full bg-ruby-red px-8 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                        {{ __('booking.checkout.submit') }}
                    </button>
                    <a href="{{ route('studios.show', $room) }}" class="rounded-full border border-prussian-blue/20 px-6 py-3 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">
                        {{ __('host.rooms.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-layout>
