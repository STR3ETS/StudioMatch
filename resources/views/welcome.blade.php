<x-layout :title="__('home.meta_title')" :description="__('home.meta_description')">
    <x-hero>
        <p class="text-sm font-semibold uppercase tracking-wider text-white/50">{{ __('home.tagline') }}</p>
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
                {{-- Waar --}}
                <div>
                    <label for="m-search-location" class="block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('home.search.where') }}</label>
                    <div class="relative mt-2">
                        <i class="fa-solid fa-location-dot absolute left-4 top-1/2 -translate-y-1/2 text-sm text-prussian-blue/40"></i>
                        <input id="m-search-location" type="text" name="location" placeholder="{{ __('home.search.where_placeholder') }}" class="w-full rounded-2xl border border-prussian-blue/15 py-3.5 pl-11 pr-4 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none">
                    </div>
                </div>

                {{-- Wanneer --}}
                <div>
                    <span class="block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('home.search.when') }}</span>
                    <div class="mt-2 flex gap-2">
                        <input type="date" name="date" class="w-full min-w-0 rounded-2xl border border-prussian-blue/15 px-4 py-3.5 text-sm text-prussian-blue focus:border-prussian-blue/40 focus:outline-none">
                        <input type="time" name="time" class="w-full min-w-0 rounded-2xl border border-prussian-blue/15 px-4 py-3.5 text-sm text-prussian-blue focus:border-prussian-blue/40 focus:outline-none">
                    </div>
                </div>

                {{-- Type --}}
                <div>
                    <span class="block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('home.search.type') }}</span>
                    <div class="mt-2 flex gap-2">
                        @foreach (['recording' => __('studios.type.recording'), 'mixmaster' => __('studios.type.mixmaster')] as $value => $label)
                            <label class="flex-1">
                                <input type="radio" name="type" value="{{ $value }}" class="peer sr-only">
                                <span class="flex cursor-pointer items-center justify-center rounded-2xl border border-prussian-blue/15 px-4 py-3.5 text-sm font-semibold text-prussian-blue/70 transition peer-checked:border-ruby-red peer-checked:bg-ruby-red/5 peer-checked:text-prussian-blue">{{ $label }}</span>
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
        $studioNames = ['Redlight Recordings', 'Northside Studio', 'De Fabriek', 'Echo Chamber', 'Sound Garden', 'Studio Zuid', 'Waveform Lab', 'The Booth', 'Analog Attic', 'Bassline Studio', 'Loud & Clear', 'Nightshift Audio', 'Vinyl Room'];
        $studioTypes = ['Opname', 'Mix & master'];
        $studioPhotos = ['/temp-studio-1.webp', '/temp-studio-2.webp', '/temp-studio-3.jpg', '/temp-studio-4.jpg', '/temp-studio-5.jpg'];

        // Genereer een setje placeholder-studio's voor een stad.
        $studiosFor = function (string $city) use ($studioNames, $studioTypes, $studioPhotos) {
            return collect($studioNames)->map(fn ($name, $i) => [
                'name' => $name,
                'city' => $city,
                'price' => 30 + (($i * 7) % 30),
                'type' => $studioTypes[$i % 2],
                'rating' => number_format(4.2 + (($i * 7) % 8) / 10, 1),
                'reviews' => 8 + (($i * 13) % 190),
                'photo' => $studioPhotos[$i % count($studioPhotos)],
            ])->all();
        };

        // Uitgelichte mix (verschillende steden).
        $featured = collect($studioNames)->map(fn ($name, $i) => [
            'name' => $name,
            'city' => ['Amsterdam', 'Groningen', 'Rotterdam', 'Utrecht', 'Eindhoven', 'Tilburg', 'Den Haag', 'Nijmegen', 'Haarlem', 'Breda', 'Arnhem', 'Leiden', 'Maastricht'][$i],
            'price' => 33 + (($i * 5) % 25),
            'type' => $studioTypes[$i % 2],
            'rating' => number_format(4.3 + (($i * 3) % 7) / 10, 1),
            'reviews' => 14 + (($i * 11) % 160),
            'photo' => $studioPhotos[$i % count($studioPhotos)],
        ])->all();

        // Bekendste studiosteden - elk een eigen slider.
        $cities = ['Amsterdam', 'Rotterdam', 'Utrecht', 'Den Haag', 'Groningen', 'Tilburg'];
    @endphp

    <x-studio-slider :title="__('home.studios.title')" :studios="$featured" :first="true" />

    @foreach ($cities as $city)
        <x-studio-slider :title="__('home.studios.in_city', ['city' => $city])" :studios="$studiosFor($city)" :last="$loop->last" />
    @endforeach
</x-layout>
