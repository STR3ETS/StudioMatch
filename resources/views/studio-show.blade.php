@php
    // Placeholder-data - vervang later door de echte studio uit de database.
    $studio = [
            'name' => 'Redlight Recordings',
            'city' => 'Amsterdam',
            'address' => 'Prinsengracht 263, 1016 GV Amsterdam',
            'type' => 'Opname',
            'rating' => '4.8',
            'reviews' => 42,
            'price' => 45,
            'capacity' => 6,
            'min_hours' => 2,
            'engineer' => true,
            'photos' => ['/temp-studio-1.webp', '/temp-studio-2.webp', '/temp-studio-3.jpg', '/temp-studio-4.jpg', '/temp-studio-5.jpg'],
            // Studio-eigen tekst (wordt niet vertaald - zie scope §2.1).
            'description' => 'Professionele opnamestudio in hartje Amsterdam. Ruime live room met akoestische behandeling, een comfortabele controlroom en hoogwaardige apparatuur. Ideaal voor vocals, bands en volledige producties. Ervaren engineer beschikbaar.',
            'rules' => [
                'Maximaal 6 personen in de studio',
                'Niet roken in de opnameruimte',
            ],
        ];

        $equipment = ['mic_condenser', 'mic_dynamic', 'monitors', 'midi61', 'piano', 'guitar_acoustic', 'drums'];
        $daws = ['Logic', 'Pro Tools', 'Ableton'];
        $facilities = [
            'wifi' => 'fa-wifi',
            'parking' => 'fa-square-parking',
            'kitchen' => 'fa-utensils',
            'coffee' => 'fa-mug-hot',
            'ac' => 'fa-fan',
        ];

        // Placeholder-reviews (studio-eigen content - niet vertaald).
        $reviews = [
            ['name' => 'Jasper de Vries', 'initials' => 'JV', 'date' => 'juni 2026', 'rating' => 5, 'comment' => 'Top studio, geweldige akoestiek en de engineer dacht echt mee. Zeker voor herhaling vatbaar.'],
            ['name' => 'Amira Haddad', 'initials' => 'AH', 'date' => 'mei 2026', 'rating' => 5, 'comment' => 'Fijne ruimte, netjes en goed uitgerust. Boeken ging soepel via het platform.'],
            ['name' => 'Tom Bakker', 'initials' => 'TB', 'date' => 'mei 2026', 'rating' => 4, 'comment' => 'Prima studio voor vocals. Parkeren in de buurt is wel even zoeken.'],
            ['name' => 'Sanne Willems', 'initials' => 'SW', 'date' => 'april 2026', 'rating' => 5, 'comment' => 'Warme sfeer, top monitors en een schone controlroom. Aanrader!'],
        ];

        // Alleen de huur tonen; servicekosten volgen aan het einde van de boekflow (klantfeedback).
        $hours = 3;
        $rent = $studio['price'] * $hours;
        $money = fn ($v) => '€ ' . number_format($v, 2, ',', '.');

        // Tijdslots per 30 minuten.
        $slots = [];
        for ($h = 8; $h <= 22; $h++) {
            foreach ([0, 30] as $m) {
                $slots[] = sprintf('%02d:%02d', $h, $m);
            }
        }
@endphp

<x-layout :title="$studio['name']" :description="$studio['description']">

    <div class="pt-28 pb-16">
        <div class="max-w-7xl mx-auto px-6">
            {{-- Breadcrumb + titel --}}
            <nav data-reveal class="text-sm text-prussian-blue/50">
                <a href="{{ route('studios') }}" class="hover:text-prussian-blue">{{ __('studio.breadcrumb') }}</a>
                <span class="px-1">/</span>
                <span class="text-prussian-blue/70">{{ $studio['name'] }}</span>
            </nav>

            <div data-reveal class="mt-3 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-prussian-blue">{{ $studio['name'] }}</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-prussian-blue/60">
                        <span class="flex items-center gap-1 font-semibold text-prussian-blue">
                            <i class="fa-solid fa-star text-[11px] text-ruby-red"></i> {{ $studio['rating'] }}
                        </span>
                        <span>{{ __('studio.reviews', ['count' => $studio['reviews']]) }}</span>
                        <span>&middot;</span>
                        <span class="flex items-center gap-1"><i class="fa-solid fa-location-dot text-[11px]"></i> {{ $studio['city'] }}</span>
                        <span>&middot;</span>
                        <span class="rounded-full bg-prussian-blue/5 px-2 py-0.5 text-xs font-semibold text-prussian-blue">{{ $studio['type'] }}</span>
                    </div>
                </div>
            </div>

            {{-- Fotogalerij --}}
            <div data-reveal style="--reveal-delay: .1s" class="mt-5 grid gap-2 overflow-hidden rounded-2xl sm:h-[440px] sm:grid-cols-4 sm:grid-rows-2">
                <img src="{{ $studio['photos'][0] }}" alt="{{ $studio['name'] }}" data-lightbox-src="{{ $studio['photos'][0] }}" class="h-64 w-full cursor-zoom-in object-cover transition hover:brightness-90 sm:col-span-2 sm:row-span-2 sm:h-full">
                @foreach (array_slice($studio['photos'], 1, 4) as $photo)
                    <img src="{{ $photo }}" alt="{{ $studio['name'] }}" data-lightbox-src="{{ $photo }}" class="hidden h-full w-full cursor-zoom-in object-cover transition hover:brightness-90 sm:block">
                @endforeach
            </div>

            {{-- Content: hoofdkolom + boekwidget --}}
            <div class="mt-10 flex flex-col gap-10 lg:flex-row">
                <div class="min-w-0 flex-1">
                    {{-- Kernfeiten --}}
                    <div class="flex flex-wrap gap-x-8 gap-y-3 border-b border-prussian-blue/10 pb-6 text-sm text-prussian-blue">
                        <span class="flex items-center gap-2"><i class="fa-solid fa-users text-ruby-red"></i> {{ __('studio.facts.capacity', ['count' => $studio['capacity']]) }}</span>
                    </div>

                    {{-- Over deze studio --}}
                    <section class="border-b border-prussian-blue/10 py-6">
                        <h2 class="text-lg font-bold text-prussian-blue">{{ __('studio.about') }}</h2>
                        <p class="mt-3 leading-relaxed text-prussian-blue/70">{{ $studio['description'] }}</p>
                    </section>

                    {{-- Apparatuur --}}
                    <section class="border-b border-prussian-blue/10 py-6">
                        <h2 class="text-lg font-bold text-prussian-blue">{{ __('studio.equipment') }}</h2>
                        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            @foreach ($equipment as $item)
                                <span class="flex items-center gap-2.5 text-sm text-prussian-blue/80"><i class="fa-solid fa-check text-ruby-red"></i> {{ __('studios.equipment.' . $item) }}</span>
                            @endforeach
                        </div>
                    </section>

                    {{-- DAW's --}}
                    <section class="border-b border-prussian-blue/10 py-6">
                        <h2 class="text-lg font-bold text-prussian-blue">{{ __('studio.daw') }}</h2>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($daws as $daw)
                                <span class="rounded-full border border-prussian-blue/15 px-3 py-1 text-sm font-medium text-prussian-blue">{{ $daw }}</span>
                            @endforeach
                        </div>
                    </section>

                    {{-- Voorzieningen --}}
                    <section class="border-b border-prussian-blue/10 py-6">
                        <h2 class="text-lg font-bold text-prussian-blue">{{ __('studio.facilities') }}</h2>
                        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @foreach ($facilities as $facility => $icon)
                                <span class="flex items-center gap-3 text-sm text-prussian-blue/80"><i class="fa-solid {{ $icon }} w-5 text-center text-prussian-blue/40"></i> {{ __('studios.facilities.' . $facility) }}</span>
                            @endforeach
                        </div>
                    </section>

                    {{-- Huisregels --}}
                    <section class="border-b border-prussian-blue/10 py-6">
                        <h2 class="text-lg font-bold text-prussian-blue">{{ __('studio.rules') }}</h2>
                        <ul class="mt-3 space-y-2">
                            @foreach ($studio['rules'] as $rule)
                                <li class="flex items-start gap-2.5 text-sm text-prussian-blue/70"><i class="fa-solid fa-circle mt-1.5 text-[5px] text-prussian-blue/40"></i> {{ $rule }}</li>
                            @endforeach
                        </ul>
                    </section>

                    {{-- Reviews --}}
                    <section class="border-b border-prussian-blue/10 py-6">
                        <h2 class="flex items-center gap-2 text-lg font-bold text-prussian-blue">
                            <i class="fa-solid fa-star text-ruby-red"></i>
                            {{ $studio['rating'] }}
                            <span class="text-prussian-blue/30">&middot;</span>
                            {{ __('studio.reviews', ['count' => $studio['reviews']]) }}
                        </h2>
                        <div class="mt-5 grid gap-x-8 gap-y-6 sm:grid-cols-2">
                            @foreach ($reviews as $review)
                                <div>
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-prussian-blue/10 text-sm font-bold text-prussian-blue">{{ $review['initials'] }}</span>
                                        <div>
                                            <p class="text-sm font-semibold text-prussian-blue">{{ $review['name'] }}</p>
                                            <p class="text-xs text-prussian-blue/50">{{ $review['date'] }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-2 flex gap-0.5 text-[11px]">
                                        @for ($s = 1; $s <= 5; $s++)
                                            <i @class(['fa-solid fa-star', 'text-ruby-red' => $s <= $review['rating'], 'text-prussian-blue/15' => $s > $review['rating']])></i>
                                        @endfor
                                    </div>
                                    <p class="mt-2 text-sm leading-relaxed text-prussian-blue/70">{{ $review['comment'] }}</p>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="mt-6 cursor-pointer rounded-full border border-prussian-blue/20 px-5 py-2 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">
                            {{ __('studio.reviews_all', ['count' => $studio['reviews']]) }}
                        </button>
                    </section>

                    {{-- Locatie --}}
                    <section class="py-6">
                        <h2 class="text-lg font-bold text-prussian-blue">{{ __('studio.location') }}</h2>
                        <p class="mt-2 text-sm text-prussian-blue/70">{{ $studio['address'] }}</p>
                        <div class="mt-3 flex h-64 items-center justify-center rounded-2xl bg-prussian-blue/5 text-prussian-blue/30">
                            <span class="flex items-center gap-2"><i class="fa-solid fa-map-location-dot"></i> {{ __('studio.map_placeholder') }}</span>
                        </div>
                    </section>
                </div>

                {{-- Boekwidget --}}
                <div data-reveal style="--reveal-delay: .15s" class="lg:w-[360px] lg:shrink-0">
                    <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6 shadow-lg lg:sticky lg:top-28">
                        <p class="text-prussian-blue">
                            <span class="text-2xl font-bold">{{ $money($studio['price']) }}</span>
                            <span class="text-sm text-prussian-blue/50">{{ __('studio.booking.per_hour') }}</span>
                        </p>

                        <div class="mt-4 space-y-3">
                            <div class="grid grid-cols-2 gap-2">
                                <div class="min-w-0">
                                    <label class="block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('studio.booking.date') }}</label>
                                    <input type="date" class="mt-1 w-full min-w-0 rounded-xl border border-prussian-blue/15 px-3 py-2 text-sm text-prussian-blue focus:border-prussian-blue/40 focus:outline-none">
                                </div>
                                <div class="min-w-0">
                                    <label class="block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('studio.booking.time') }}</label>
                                    <select class="mt-1 w-full min-w-0 cursor-pointer rounded-xl border border-prussian-blue/15 px-3 py-2 text-sm text-prussian-blue focus:border-prussian-blue/40 focus:outline-none">
                                        @foreach ($slots as $slot)
                                            <option @selected($slot === '12:00')>{{ $slot }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('studio.booking.duration') }}</label>
                                <select class="mt-1 w-full cursor-pointer rounded-xl border border-prussian-blue/15 px-3 py-2 text-sm text-prussian-blue focus:border-prussian-blue/40 focus:outline-none">
                                    @for ($h = $studio['min_hours']; $h <= 8; $h++)
                                        <option @selected($h === $hours)>{{ __('studio.booking.hours', ['count' => $h]) }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>

                        {{-- Totaal (servicekosten volgen in de checkout - klantfeedback) --}}
                        <div class="mt-5 flex justify-between border-t border-prussian-blue/10 pt-4 font-bold text-prussian-blue">
                            <span>{{ __('studio.booking.total') }} ({{ __('studio.booking.hours', ['count' => $hours]) }})</span>
                            <span>{{ $money($rent) }}</span>
                        </div>

                        <button type="button" class="mt-5 w-full cursor-pointer rounded-full bg-ruby-red py-3 text-sm font-semibold text-white transition hover:bg-ruby-red/90">{{ __('studio.booking.book') }}</button>

                        <p class="mt-3 text-center text-xs text-prussian-blue/50">{{ __('studio.booking.disclaimer') }}</p>
                        <p class="mt-2 flex items-center justify-center gap-1.5 text-center text-xs text-prussian-blue/50">
                            <i class="fa-solid fa-shield-halved text-prussian-blue/30"></i> {{ __('studio.booking.cancel') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Lightbox voor de fotogalerij --}}
    <div data-lightbox class="fixed inset-0 z-[1300] hidden bg-prussian-blue/95 p-4">
        <button type="button" data-lightbox-close aria-label="{{ __('home.search.close') }}" class="absolute right-5 top-5 z-10 flex h-10 w-10 cursor-pointer items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <button type="button" data-lightbox-prev aria-label="{{ __('home.studios.prev') }}" class="absolute left-4 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
        <button type="button" data-lightbox-next aria-label="{{ __('home.studios.next') }}" class="absolute right-4 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
        <div class="flex h-full items-center justify-center">
            <img src="" alt="{{ $studio['name'] }}" class="max-h-full max-w-full rounded-2xl object-contain">
        </div>
    </div>
</x-layout>
