<x-layout :title="__('home.meta_title')" :description="__('home.meta_description')">
    <x-hero>
        <p class="text-sm font-semibold uppercase tracking-wider text-white/60">{{ __('home.tagline') }}</p>
        <h1 class="mt-3 text-4xl sm:text-5xl font-bold text-white">{{ __('home.heading') }}</h1>

        {{-- Mobiel: één zoekbalk die de zoek-modal opent --}}
        <button type="button" data-search-open class="mt-10 flex w-full cursor-pointer items-center gap-4 rounded-full bg-white p-2 pl-6 text-left shadow-xl sm:hidden">
            <span class="flex-1">
                <span class="block text-sm font-bold text-prussian-blue">{{ __('home.search.mobile_placeholder') }}</span>
                <span class="block text-xs text-prussian-blue/50">{{ __('home.search.mobile_hint') }}</span>
            </span>
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-ruby-red text-white"><i class="fa-solid fa-magnifying-glass"></i></span>
        </button>

        <form action="{{ route('studios') }}" class="mt-10 max-w-3xl mx-auto max-sm:hidden flex items-center rounded-full bg-white shadow-xl">
            <label class="flex-1 cursor-text rounded-2xl px-10 py-4 text-left transition hover:bg-prussian-blue/5">
                <span class="block text-xs font-bold text-prussian-blue">{{ __('home.search.where') }}</span>
                <input type="text" name="location" placeholder="{{ __('home.search.where_placeholder') }}" class="w-full bg-transparent text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:outline-none">
            </label>

            <span class="hidden sm:block h-8 w-px bg-prussian-blue/10"></span>

            <button type="button" class="flex-1 rounded-2xl px-10 py-4 text-left transition hover:bg-prussian-blue/5">
                <span class="block text-xs font-bold text-prussian-blue">{{ __('home.search.when') }}</span>
                <span class="block text-sm text-prussian-blue/40">{{ __('home.search.when_placeholder') }}</span>
            </button>

            <span class="hidden sm:block h-8 w-px bg-prussian-blue/10"></span>

            <button type="button" class="flex-1 rounded-2xl px-10 py-4 text-left transition hover:bg-prussian-blue/5">
                <span class="block text-xs font-bold text-prussian-blue">{{ __('home.search.type') }}</span>
                <span class="block text-sm text-prussian-blue/40">{{ __('home.search.type_placeholder') }}</span>
            </button>

            <button type="submit" aria-label="{{ __('home.search.submit') }}" class="flex h-12 shrink-0 items-center justify-center gap-2 rounded-full bg-ruby-red font-semibold text-white transition hover:bg-ruby-red/90 sm:w-12 mx-2 mb-2 sm:my-0 sm:mr-4 sm:ml-4">
                <i class="fa-solid fa-magnifying-glass"></i>
                <span class="sm:hidden">{{ __('home.search.submit') }}</span>
            </button>
        </form>

        {{-- Twee duidelijke CTA's boven de vouw --}}
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('studios') }}" class="rounded-full bg-white px-6 py-3 text-sm font-semibold text-ruby-red shadow-sm transition hover:bg-white/90">
                <i class="fa-solid fa-magnifying-glass fa-sm mr-1.5"></i>{{ __('home.cta_book') }}
            </a>
            <a href="{{ route('hosts') }}" class="rounded-full border border-white/30 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                <i class="fa-solid fa-door-open fa-sm mr-1.5"></i>{{ __('home.cta_host') }}
            </a>
        </div>
    </x-hero>

    {{-- Mobiele zoek-modal --}}
    <div data-search-modal class="fixed inset-0 z-[1200] hidden bg-white sm:hidden">
        <div class="flex h-full flex-col">
            <div class="flex items-center justify-between border-b border-prussian-blue/10 px-6 py-4">
                <h2 class="text-lg font-bold text-prussian-blue">{{ __('home.search.mobile_placeholder') }}</h2>
                <button type="button" data-search-close aria-label="{{ __('home.search.close') }}" class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-full text-prussian-blue transition hover:bg-prussian-blue/5">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('studios') }}" class="flex flex-1 flex-col gap-6 overflow-y-auto px-6 py-6">
                <div>
                    <label for="m-search-location" class="block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('home.search.where') }}</label>
                    <div class="relative mt-2">
                        <i class="fa-solid fa-location-dot absolute left-4 top-1/2 -translate-y-1/2 text-sm text-prussian-blue/40"></i>
                        <input id="m-search-location" type="text" name="location" placeholder="{{ __('home.search.where_placeholder') }}" class="w-full rounded-2xl border border-prussian-blue/15 py-3.5 pl-11 pr-4 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none">
                    </div>
                </div>

                <div>
                    <span class="block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('home.search.when') }}</span>
                    <div class="mt-2 flex gap-2">
                        <input type="date" name="date" class="w-full min-w-0 rounded-2xl border border-prussian-blue/15 px-4 py-3.5 text-sm text-prussian-blue focus:border-prussian-blue/40 focus:outline-none">
                        <input type="time" name="time" step="1800" class="w-full min-w-0 rounded-2xl border border-prussian-blue/15 px-4 py-3.5 text-sm text-prussian-blue focus:border-prussian-blue/40 focus:outline-none">
                    </div>
                </div>

                <div>
                    <span class="block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('home.search.type') }}</span>
                    <div class="mt-2 flex gap-2">
                        @foreach (['recording', 'mix', 'master'] as $value)
                            <label class="flex-1">
                                <input type="radio" name="type" value="{{ $value }}" class="peer sr-only">
                                <span class="flex cursor-pointer items-center justify-center rounded-2xl border border-prussian-blue/15 px-4 py-3.5 text-sm font-semibold text-prussian-blue/70 transition peer-checked:border-ruby-red peer-checked:bg-ruby-red/5 peer-checked:text-prussian-blue">{{ __('studios.type.' . $value) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="mt-auto flex cursor-pointer items-center justify-center gap-2 rounded-full bg-ruby-red py-3.5 text-sm font-semibold text-white transition hover:bg-ruby-red/90">
                    <i class="fa-solid fa-magnifying-glass fa-sm"></i> {{ __('home.search.submit') }}
                </button>
            </form>
        </div>
    </div>

    @php
        // Placeholder-data - vervang later door echte studio's uit de database.
        $studioNames = ['Redlight Recordings', 'Northside Studio', 'De Fabriek', 'Echo Chamber', 'Sound Garden', 'Studio Zuid', 'Waveform Lab', 'The Booth'];
        $studioTypes = ['Opname', 'Mix', 'Master'];
        $studioPhotos = ['/temp-studio-1.webp', '/temp-studio-2.webp', '/temp-studio-3.jpg', '/temp-studio-4.jpg', '/temp-studio-5.jpg'];
        $studioCities = ['Amsterdam', 'Groningen', 'Rotterdam', 'Utrecht', 'Eindhoven', 'Tilburg', 'Den Haag', 'Nijmegen'];

        $featured = collect($studioNames)->map(fn ($name, $i) => [
            'name' => $name,
            'city' => $studioCities[$i],
            'price' => 33 + (($i * 5) % 25),
            'type' => $studioTypes[$i % 3],
            'rating' => number_format(4.3 + (($i * 3) % 7) / 10, 1),
            'reviews' => 14 + (($i * 11) % 160),
            'photos' => [$studioPhotos[$i % 5], $studioPhotos[($i + 2) % 5], $studioPhotos[($i + 4) % 5]],
        ])->all();

        // Coördinaten per stad voor de kaart.
        $mapCoords = [
            ['i' => 0, 'lat' => 52.3676, 'lng' => 4.9041],
            ['i' => 1, 'lat' => 53.2194, 'lng' => 6.5665],
            ['i' => 2, 'lat' => 51.9244, 'lng' => 4.4777],
            ['i' => 3, 'lat' => 52.0907, 'lng' => 5.1214],
            ['i' => 4, 'lat' => 51.4416, 'lng' => 5.4697],
            ['i' => 5, 'lat' => 51.5606, 'lng' => 5.0919],
            ['i' => 6, 'lat' => 52.0705, 'lng' => 4.3007],
            ['i' => 7, 'lat' => 51.8126, 'lng' => 5.8372],
        ];
        $mapStudios = collect($mapCoords)->map(function ($c) use ($featured) {
            $studio = $featured[$c['i']];

            return [
                'name' => $studio['name'],
                'city' => $studio['city'],
                'price' => $studio['price'],
                'rating' => $studio['rating'],
                'photos' => $studio['photos'],
                'url' => route('studios.show', str($studio['name'])->slug()),
                'lat' => $c['lat'],
                'lng' => $c['lng'],
            ];
        })->all();
    @endphp

    {{-- Uitgelichte studio's --}}
    <x-studio-slider :title="__('home.studios.title')" :studios="$featured" :first="true" />

    {{-- Waarom StudioMatch (zelfde stijl als de Voor studio's-pagina) --}}
    <section class="py-16">
        <div class="mx-auto max-w-7xl px-6">
            <div class="max-w-2xl">
                <h2 class="text-3xl font-bold text-prussian-blue">{{ __('home.why.title') }}</h2>
                <p class="mt-3 text-prussian-blue/60">{{ __('home.why.subtitle') }}</p>
            </div>
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach (__('home.why.items') as $item)
                    <div @class([
                        'rounded-2xl p-6 transition hover:-translate-y-1',
                        'bg-prussian-blue text-white shadow-xl shadow-prussian-blue/20' => $loop->first,
                        'border border-prussian-blue/10 hover:shadow-lg hover:shadow-prussian-blue/5' => ! $loop->first,
                    ])>
                        <span @class([
                            'flex h-11 w-11 items-center justify-center rounded-xl',
                            'bg-white/10 text-white' => $loop->first,
                            'bg-ruby-red/10 text-ruby-red' => ! $loop->first,
                        ])><i class="fa-solid {{ $item['icon'] }}"></i></span>
                        <h3 @class(['mt-4 font-bold', 'text-white' => $loop->first, 'text-prussian-blue' => ! $loop->first])>{{ $item['title'] }}</h3>
                        <p @class(['mt-2 text-sm leading-relaxed', 'text-white/60' => $loop->first, 'text-prussian-blue/60' => ! $loop->first])>{{ $item['text'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-10 flex flex-wrap items-center gap-3">
                <a href="{{ route('studios') }}" class="rounded-full bg-ruby-red px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                    <i class="fa-solid fa-magnifying-glass fa-sm mr-1.5"></i>{{ __('home.cta_book') }}
                </a>
                <a href="{{ route('how') }}" class="rounded-full border border-prussian-blue/20 px-6 py-3 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">
                    {{ __('nav.how_it_works') }} <i class="fa-solid fa-arrow-right fa-xs ml-1"></i>
                </a>
            </div>
        </div>
    </section>

    {{-- Studio locaties (kaart-placeholder) --}}
    <section class="py-16">
        <div class="mx-auto max-w-7xl px-6">
            <x-studio-map :studios="$mapStudios" class="aspect-[2/1] max-sm:aspect-square rounded-[2.5rem] border border-white/10" />
        </div>
    </section>

    {{-- Feature: boeken (wit, mockup rechts) --}}
    <section class="py-16">
        <div class="mx-auto max-w-7xl px-6">
            <div class="flex flex-col items-center gap-12 lg:flex-row lg:gap-20">
                <div class="flex-1">
                    <span class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-ruby-red"><i class="fa-solid fa-calendar-check"></i> {{ __('home.features.book.label') }}</span>
                    <h2 class="mt-2 text-2xl font-bold text-prussian-blue sm:text-3xl">{{ __('home.features.book.title') }}</h2>
                    <p class="mt-3 text-prussian-blue/60">{{ __('home.features.book.text') }}</p>
                    <ul class="mt-5 space-y-3">
                        @foreach (__('home.features.book.bullets') as $bullet)
                            <li class="flex items-start gap-3 text-sm text-prussian-blue/80"><i class="fa-solid fa-check mt-0.5 text-ruby-red"></i> {{ $bullet }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('studios') }}" class="mt-7 inline-flex items-center gap-2 rounded-full bg-ruby-red px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                        <i class="fa-solid fa-magnifying-glass fa-sm"></i> {{ __('home.cta_book') }}
                    </a>
                </div>
                <div class="relative flex flex-1 justify-center">
                    <x-phone-mockup class="-rotate-2" :label="__('home.features.book.mock')" />
                    <div class="absolute -left-2 top-16 hidden items-center gap-3 float-card rounded-2xl bg-white px-4 py-3 shadow-xl shadow-prussian-blue/15 xl:flex">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-ruby-red/10 text-ruby-red"><i class="fa-solid fa-hourglass-half fa-sm"></i></span>
                        <div>
                            <p class="text-xs font-bold text-prussian-blue">{{ __('home.features.book.card_title') }}</p>
                            <p class="text-[11px] text-prussian-blue/50">{{ __('home.features.book.card_sub') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Feature: dashboard (stippenpatroon, mockup links) --}}
    <section class="bg-dots py-16">
        <div class="mx-auto max-w-7xl px-6">
            <div class="flex flex-col items-center gap-12 lg:flex-row-reverse lg:gap-20">
                <div class="flex-1">
                    <span class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-ruby-red"><i class="fa-solid fa-gauge-high"></i> {{ __('home.features.manage.label') }}</span>
                    <h2 class="mt-2 text-2xl font-bold text-prussian-blue sm:text-3xl">{{ __('home.features.manage.title') }}</h2>
                    <p class="mt-3 text-prussian-blue/60">{{ __('home.features.manage.text') }}</p>
                    <ul class="mt-5 space-y-3">
                        @foreach (__('home.features.manage.bullets') as $bullet)
                            <li class="flex items-start gap-3 text-sm text-prussian-blue/80"><i class="fa-solid fa-check mt-0.5 text-ruby-red"></i> {{ $bullet }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('how') }}" class="mt-7 inline-flex items-center gap-2 rounded-full border border-prussian-blue/20 px-6 py-3 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">
                        {{ __('nav.how_it_works') }} <i class="fa-solid fa-arrow-right fa-xs"></i>
                    </a>
                </div>
                <div class="relative flex flex-1 justify-center">
                    <x-phone-mockup class="rotate-2" :label="__('home.features.manage.mock')" />
                    <div class="absolute -right-2 bottom-20 hidden items-center gap-3 float-card rounded-2xl bg-white px-4 py-3 shadow-xl shadow-prussian-blue/15 xl:flex">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600"><i class="fa-solid fa-circle-check fa-sm"></i></span>
                        <div>
                            <p class="text-xs font-bold text-prussian-blue">{{ __('home.features.manage.card_title') }}</p>
                            <p class="text-[11px] text-prussian-blue/50">{{ __('home.features.manage.card_sub') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA voor studioverhuurders --}}
    <section class="py-16">
        <div class="mx-auto max-w-7xl px-6">
            <div class="relative overflow-hidden rounded-[2.5rem] bg-prussian-blue px-8 py-14 text-center lg:px-14">
                <x-floating-icons />
                <div class="relative z-10">
                    <h2 class="text-3xl font-bold text-white sm:text-4xl">{{ __('home.host_cta.title') }}</h2>
                    <p class="mx-auto mt-3 max-w-xl text-white/60">{{ __('home.host_cta.text') }}</p>
                    <a href="{{ route('hosts') }}" class="mt-7 inline-flex items-center gap-2 rounded-full bg-ruby-red px-8 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                        <i class="fa-solid fa-door-open fa-sm"></i> {{ __('home.host_cta.button') }}
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-layout>
