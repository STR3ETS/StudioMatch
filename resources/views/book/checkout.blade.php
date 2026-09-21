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
                @if ($endDate)
                    <input type="hidden" name="end_date" value="{{ request('end_date') }}">
                @else
                    <input type="hidden" name="start" value="{{ request('start') }}">
                    <input type="hidden" name="hours" value="{{ request('hours') }}">
                @endif
                @if ($withEngineer)
                    <input type="hidden" name="engineer" value="1">
                @endif

                <div class="flex flex-wrap items-center gap-4 rounded-2xl border border-prussian-blue/10 bg-white p-5 sm:flex-nowrap">
                    @if ($room->photos->isNotEmpty())
                        <img src="{{ $room->photos->first()->thumbUrl() }}" alt="{{ $room->title }}" class="h-20 w-28 shrink-0 rounded-xl object-cover">
                    @endif
                    <div class="min-w-0 flex-1">
                        <h2 class="font-bold text-prussian-blue">{{ $room->title }}</h2>
                        <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-prussian-blue/60">
                            <span><i class="fa-solid fa-building fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $room->studio->name }} ({{ $room->studio->city }})</span>
                            @if ($endDate)
                                <span><i class="fa-solid fa-calendar-days fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $date->translatedFormat('j F Y') }} &ndash; {{ $endDate->translatedFormat('j F Y') }}</span>
                                <span><i class="fa-solid fa-clock fa-xs mr-1.5 text-prussian-blue/40"></i>{{ trans_choice('booking.day_count', $days, ['count' => $days]) }}</span>
                            @else
                                <span><i class="fa-solid fa-calendar-days fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $date->translatedFormat('l j F Y') }}</span>
                                <span><i class="fa-solid fa-clock fa-xs mr-1.5 text-prussian-blue/40"></i>{{ \App\Support\Hours::bookingRange($date, $startHour, $endHour) }}</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6">
                    <h2 class="font-bold text-prussian-blue">{{ __('booking.checkout.price_title') }}</h2>
                    <div class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between text-prussian-blue/70">
                            <span>{{ __('studio.booking.rent', ['count' => $endHour - $startHour]) }}{{ $withEngineer ? ' ' . __('booking.checkout.with_engineer') : '' }}</span>
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

                <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6" data-buyer-type>
                    <h2 class="font-bold text-prussian-blue">{{ __('booking.checkout.buyer_title') }}</h2>
                    <p class="mt-1 text-sm text-prussian-blue/60">{{ __('booking.checkout.buyer_note') }}</p>

                    @php $buyerType = old('buyer_type', 'particulier'); @endphp
                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                        @foreach (['particulier', 'zakelijk'] as $option)
                            <label>
                                <input type="radio" name="buyer_type" value="{{ $option }}" class="peer sr-only" @checked($buyerType === $option)>
                                <span class="flex cursor-pointer items-center gap-2 rounded-xl border border-prussian-blue/15 px-4 py-3 text-sm font-semibold text-prussian-blue transition peer-checked:border-ruby-red peer-checked:bg-ruby-red/5">
                                    <i class="fa-solid {{ $option === 'zakelijk' ? 'fa-briefcase' : 'fa-user' }} text-xs text-ruby-red"></i>
                                    {{ __('booking.checkout.buyer_' . $option) }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error field="buyer_type" />

                    <div data-buyer-business class="{{ $buyerType === 'zakelijk' ? 'mt-4 space-y-4' : 'mt-4 hidden space-y-4' }}">
                        <div>
                            <label for="buyer_company" class="block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('booking.checkout.buyer_company') }}</label>
                            <input id="buyer_company" type="text" name="buyer_company" value="{{ old('buyer_company') }}" class="mt-2 w-full rounded-xl border border-prussian-blue/15 bg-white px-4 py-2.5 text-sm text-prussian-blue focus:border-prussian-blue/40 focus:outline-none">
                            <x-input-error field="buyer_company" />
                        </div>
                        <div>
                            <label for="buyer_vat_number" class="block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('booking.checkout.buyer_vat') }}</label>
                            <input id="buyer_vat_number" type="text" name="buyer_vat_number" value="{{ old('buyer_vat_number') }}" class="mt-2 w-full rounded-xl border border-prussian-blue/15 bg-white px-4 py-2.5 text-sm text-prussian-blue focus:border-prussian-blue/40 focus:outline-none">
                            <x-input-error field="buyer_vat_number" />
                        </div>
                    </div>

                    <script>
                        (() => {
                            const wrap = document.querySelector('[data-buyer-type]');
                            const business = wrap?.querySelector('[data-buyer-business]');
                            if (! wrap || ! business) return;
                            wrap.querySelectorAll('input[name=buyer_type]').forEach((radio) => {
                                radio.addEventListener('change', () => business.classList.toggle('hidden', radio.value !== 'zakelijk'));
                            });
                        })();
                    </script>
                </div>
                @if (! auth()->user()->hasCompleteAddress())
                    @php $addressField = 'mt-2 w-full rounded-xl border border-prussian-blue/15 bg-white px-4 py-2.5 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none'; @endphp
                    <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6">
                        <h2 class="font-bold text-prussian-blue">{{ __('booking.checkout.address_title') }}</h2>
                        <p class="mt-1 text-sm text-prussian-blue/60">{{ __('booking.checkout.address_note') }}</p>
                        <div class="mt-4 space-y-4">
                            <div data-address-autocomplete data-url="{{ route('address.suggest') }}" class="relative">
                                <label for="street" class="block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('account.profile.street') }}</label>
                                <input id="street" type="text" name="street" value="{{ old('street', auth()->user()->street) }}" class="{{ $addressField }}" autocomplete="off" data-address-street required>
                                <div data-address-suggestions class="absolute left-0 right-0 top-full z-30 mt-1 hidden overflow-hidden rounded-xl border border-prussian-blue/10 bg-white shadow-xl"></div>
                                <x-input-error field="street" />
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="postal_code" class="block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('account.profile.postal_code') }}</label>
                                    <input id="postal_code" type="text" name="postal_code" value="{{ old('postal_code', auth()->user()->postal_code) }}" class="{{ $addressField }}" data-address-postal required>
                                    <x-input-error field="postal_code" />
                                </div>
                                <div>
                                    <label for="city" class="block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('account.profile.city') }}</label>
                                    <input id="city" type="text" name="city" value="{{ old('city', auth()->user()->city) }}" class="{{ $addressField }}" data-address-city required>
                                    <x-input-error field="city" />
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

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
