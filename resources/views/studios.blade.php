<x-layout :title="__('studios.meta_title')" :description="__('studios.meta_description')">
    @php
        // Placeholder-data - vervang later door echte studio's uit de database.
        $studioNames = ['Redlight Recordings', 'Northside Studio', 'De Fabriek', 'Echo Chamber', 'Sound Garden', 'Studio Zuid', 'Waveform Lab', 'The Booth', 'Analog Attic', 'Bassline Studio', 'Loud & Clear', 'Nightshift Audio', 'Vinyl Room'];
        $studioTypes = ['Opname', 'Mix', 'Master'];
        $studioPhotos = ['/temp-studio-1.webp', '/temp-studio-2.webp', '/temp-studio-3.jpg', '/temp-studio-4.jpg', '/temp-studio-5.jpg'];
        $studioCities = ['Amsterdam', 'Rotterdam', 'Utrecht', 'Den Haag', 'Groningen', 'Tilburg', 'Eindhoven', 'Haarlem', 'Nijmegen', 'Breda'];

        $studios = collect(range(0, 39))->map(fn ($i) => [
            'name' => $studioNames[$i % count($studioNames)],
            'city' => $studioCities[$i % count($studioCities)],
            'price' => 30 + (($i * 7) % 45),
            'type' => $studioTypes[$i % 3],
            'rating' => number_format(4.1 + (($i * 7) % 9) / 10, 1),
            'reviews' => 6 + (($i * 13) % 200),
            'photos' => [$studioPhotos[$i % 5], $studioPhotos[($i + 2) % 5], $studioPhotos[($i + 4) % 5]],
        ])->all();

        // Filteropties conform de scope (§2.3) en klantfeedback.
        $daws = ['Logic', 'Pro Tools', 'FL Studio', 'Ableton', 'Cubase'];
        $equipment = ['mic_condenser', 'mic_dynamic', 'mic_usb', 'monitors', 'midi25', 'midi49', 'midi61', 'midi88', 'piano', 'guitar_acoustic', 'guitar_electric', 'bass', 'drums'];
        $facilities = ['wifi', 'parking', 'kitchen', 'microwave', 'fridge', 'coffee', 'smoking', 'ac'];

        // Tijdslots per 30 minuten.
        $slots = [];
        for ($h = 0; $h < 24; $h++) {
            foreach ([0, 30] as $m) {
                $slots[] = sprintf('%02d:%02d', $h, $m);
            }
        }

        // Kaartdata: één pin per stad (eerste studio uit de lijst).
        $cityCoords = [
            'Amsterdam' => [52.3676, 4.9041],
            'Rotterdam' => [51.9244, 4.4777],
            'Utrecht' => [52.0907, 5.1214],
            'Den Haag' => [52.0705, 4.3007],
            'Groningen' => [53.2194, 6.5665],
            'Tilburg' => [51.5606, 5.0919],
            'Eindhoven' => [51.4416, 5.4697],
            'Haarlem' => [52.3874, 4.6462],
            'Nijmegen' => [51.8126, 5.8372],
            'Breda' => [51.5719, 4.7683],
        ];
        $mapStudios = collect($studios)->unique('city')->map(fn ($studio) => [
            'name' => $studio['name'],
            'city' => $studio['city'],
            'price' => $studio['price'],
            'rating' => $studio['rating'],
            'photos' => $studio['photos'],
            'url' => route('studios.show', str($studio['name'])->slug()),
            'lat' => $cityCoords[$studio['city']][0],
            'lng' => $cityCoords[$studio['city']][1],
        ])->values()->all();

        $subLabel = 'block text-xs font-bold uppercase tracking-wide text-prussian-blue/50';
        $checkbox = 'h-4 w-4 rounded border-prussian-blue/30 accent-ruby-red';
        $checkLabel = 'flex cursor-pointer items-center gap-2.5 text-sm text-prussian-blue';
        $field = 'w-full rounded-xl border border-prussian-blue/15 px-3 py-2 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none';
    @endphp

    <x-hero compact>
        <h1 class="text-4xl sm:text-5xl font-bold text-white">{{ __('studios.hero_heading') }}</h1>
        <p class="mx-auto mt-3 max-w-xl text-white/60">{{ __('studios.hero_subtitle') }}</p>
    </x-hero>

    <div class="py-16">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex flex-col gap-8 lg:flex-row">
                {{-- Sticky filter card met inklapbare groepen --}}
                <aside data-reveal class="lg:w-72 lg:shrink-0">
                    <form class="custom-scrollbar rounded-2xl border border-prussian-blue/10 bg-white p-5 shadow-sm lg:sticky lg:top-24 lg:max-h-[85vh] lg:overflow-y-auto lg:overflow-x-hidden">
                        <div class="flex items-center justify-between pb-4">
                            <h2 class="text-base font-bold text-prussian-blue">{{ __('studios.filters.title') }}</h2>
                            <button type="reset" class="cursor-pointer text-xs font-semibold text-ruby-red hover:underline">{{ __('studios.filters.clear') }}</button>
                        </div>

                        {{-- Locatie & afstand --}}
                        <x-filter-group :title="__('studios.filters.groups.location')">
                            <input type="text" placeholder="{{ __('studios.filters.location_placeholder') }}" class="{{ $field }}">
                            <button type="button" class="mt-2 flex cursor-pointer items-center gap-1.5 text-xs font-semibold text-ruby-red hover:underline">
                                <i class="fa-solid fa-location-crosshairs"></i> {{ __('studios.filters.near_me') }}
                            </button>
                            <div class="mt-4">
                                <div class="flex items-center justify-between">
                                    <span class="{{ $subLabel }}">{{ __('studios.filters.distance') }}</span>
                                    <span class="text-xs font-semibold text-prussian-blue" data-distance-value>25 km</span>
                                </div>
                                <input type="range" min="1" max="100" value="25" class="mt-2 w-full accent-ruby-red" oninput="this.closest('div').querySelector('[data-distance-value]').textContent = this.value + ' km'">
                            </div>
                        </x-filter-group>

                        {{-- Prijs --}}
                        <x-filter-group :title="__('studios.filters.groups.price')">
                            <div class="flex items-center gap-2">
                                <input type="number" min="0" placeholder="&euro; 0" class="{{ $field }}">
                                <span class="text-prussian-blue/30">&ndash;</span>
                                <input type="number" min="0" placeholder="&euro; 200" class="{{ $field }}">
                            </div>
                        </x-filter-group>

                        {{-- Beschikbaarheid: boekingsperiode met slots per 30 min --}}
                        <x-filter-group :title="__('studios.filters.groups.availability')">
                            <input type="date" class="{{ $field }} min-w-0">
                            <div class="mt-2 grid grid-cols-2 gap-2">
                                <div class="min-w-0">
                                    <span class="{{ $subLabel }}">{{ __('studios.filters.start') }}</span>
                                    <select class="{{ $field }} mt-1 cursor-pointer">
                                        <option value="">--:--</option>
                                        @foreach ($slots as $slot)
                                            <option>{{ $slot }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="min-w-0">
                                    <span class="{{ $subLabel }}">{{ __('studios.filters.end') }}</span>
                                    <select class="{{ $field }} mt-1 cursor-pointer">
                                        <option value="">--:--</option>
                                        @foreach ($slots as $slot)
                                            <option>{{ $slot }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </x-filter-group>

                        {{-- Studio: type, capaciteit, engineer --}}
                        <x-filter-group :title="__('studios.filters.groups.studio')">
                            <div>
                                <span class="{{ $subLabel }}">{{ __('studios.filters.type') }}</span>
                                <div class="mt-2 space-y-2">
                                    @foreach ([__('studios.type.recording'), __('studios.type.mix'), __('studios.type.master')] as $label)
                                        <label class="{{ $checkLabel }}"><input type="checkbox" class="{{ $checkbox }}"> {{ $label }}</label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="mt-4">
                                <span class="{{ $subLabel }}">{{ __('studios.filters.capacity') }}</span>
                                <div class="mt-2 flex items-center justify-between rounded-xl border border-prussian-blue/15 px-3 py-2" data-stepper data-stepper-any="{{ __('studios.filters.capacity_any') }}">
                                    <span class="text-sm text-prussian-blue" data-stepper-label>{{ __('studios.filters.capacity_any') }}</span>
                                    <div class="flex items-center gap-2">
                                        <button type="button" data-stepper-minus class="flex h-7 w-7 cursor-pointer items-center justify-center rounded-full border border-prussian-blue/20 text-prussian-blue transition hover:bg-prussian-blue/5 disabled:cursor-not-allowed disabled:opacity-30"><i class="fa-solid fa-minus text-[10px]"></i></button>
                                        <button type="button" data-stepper-plus class="flex h-7 w-7 cursor-pointer items-center justify-center rounded-full border border-prussian-blue/20 text-prussian-blue transition hover:bg-prussian-blue/5 disabled:cursor-not-allowed disabled:opacity-30"><i class="fa-solid fa-plus text-[10px]"></i></button>
                                    </div>
                                    <input type="hidden" name="capacity" value="0">
                                </div>
                            </div>
                            <div class="mt-4">
                                <span class="{{ $subLabel }}">{{ __('studios.filters.engineer') }}</span>
                                <div class="mt-2 space-y-2">
                                    @foreach ([__('studios.engineer.with'), __('studios.engineer.without')] as $label)
                                        <label class="{{ $checkLabel }}"><input type="checkbox" class="{{ $checkbox }}"> {{ $label }}</label>
                                    @endforeach
                                </div>
                            </div>
                        </x-filter-group>

                        {{-- Apparatuur --}}
                        <x-filter-group :title="__('studios.filters.groups.equipment')">
                            <div class="space-y-2">
                                @foreach ($equipment as $item)
                                    <label class="{{ $checkLabel }}"><input type="checkbox" class="{{ $checkbox }}"> {{ __('studios.equipment.' . $item) }}</label>
                                @endforeach
                            </div>
                        </x-filter-group>

                        {{-- DAW's --}}
                        <x-filter-group :title="__('studios.filters.groups.daw')">
                            <div class="space-y-2">
                                @foreach ($daws as $daw)
                                    <label class="{{ $checkLabel }}"><input type="checkbox" class="{{ $checkbox }}"> {{ $daw }}</label>
                                @endforeach
                            </div>
                        </x-filter-group>

                        {{-- Voorzieningen --}}
                        <x-filter-group :title="__('studios.filters.groups.facilities')">
                            <div class="space-y-2">
                                @foreach ($facilities as $facility)
                                    <label class="{{ $checkLabel }}"><input type="checkbox" class="{{ $checkbox }}"> {{ __('studios.facilities.' . $facility) }}</label>
                                @endforeach
                            </div>
                        </x-filter-group>

                        <button type="submit" class="mt-5 w-full cursor-pointer rounded-full bg-ruby-red py-2.5 text-sm font-semibold text-white transition hover:bg-ruby-red/90">{{ __('studios.filters.apply') }}</button>
                    </form>
                </aside>

                {{-- Results --}}
                <div data-reveal style="--reveal-delay: .1s" class="flex-1">
                    <div class="mb-5 flex items-center justify-between gap-4">
                        <p class="text-sm font-medium text-prussian-blue/60">{{ __('studios.results', ['count' => count($studios)]) }}</p>
                        <label class="flex items-center gap-2 text-sm text-prussian-blue">
                            <span class="hidden text-prussian-blue/50 sm:inline">{{ __('studios.sort.label') }}</span>
                            <select class="cursor-pointer rounded-xl border border-prussian-blue/15 px-3 py-2 text-sm font-medium text-prussian-blue focus:border-prussian-blue/40 focus:outline-none">
                                <option>{{ __('studios.sort.relevance') }}</option>
                                <option>{{ __('studios.sort.distance') }}</option>
                                <option>{{ __('studios.sort.price_asc') }}</option>
                                <option>{{ __('studios.sort.price_desc') }}</option>
                            </select>
                        </label>
                    </div>

                    <div data-loadmore data-loadmore-initial="16" data-loadmore-step="16">
                        <div data-loadmore-grid class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
                            @foreach ($studios as $studio)
                                <x-studio-card :studio="$studio" />
                            @endforeach
                        </div>
                        <div class="mt-10 flex justify-center">
                            <button type="button" data-loadmore-btn class="cursor-pointer rounded-full border border-prussian-blue/20 px-6 py-2.5 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">
                                {{ __('studios.load_more') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kaart onder de lijst --}}
            <div data-reveal class="mt-16">
                <x-studio-map :studios="$mapStudios" class="aspect-[2/1] rounded-[2.5rem] border border-prussian-blue/10" />
            </div>
        </div>
    </div>
</x-layout>
