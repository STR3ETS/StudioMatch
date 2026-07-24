<x-layout :title="__('studios.meta_title')" :description="__('studios.meta_description')">
    @php
        // Placeholder-data - vervang later door echte studio's uit de database.
        $studioNames = ['Redlight Recordings', 'Northside Studio', 'De Fabriek', 'Echo Chamber', 'Sound Garden', 'Studio Zuid', 'Waveform Lab', 'The Booth', 'Analog Attic', 'Bassline Studio', 'Loud & Clear', 'Nightshift Audio', 'Vinyl Room'];
        $studioTypes = ['Opname', 'Mix & master'];
        $studioPhotos = ['/temp-studio-1.webp', '/temp-studio-2.webp', '/temp-studio-3.jpg', '/temp-studio-4.jpg', '/temp-studio-5.jpg'];
        $studioCities = ['Amsterdam', 'Rotterdam', 'Utrecht', 'Den Haag', 'Groningen', 'Tilburg', 'Eindhoven', 'Haarlem', 'Nijmegen', 'Breda'];

        $studios = collect(range(0, 39))->map(fn ($i) => [
            'name' => $studioNames[$i % count($studioNames)],
            'city' => $studioCities[$i % count($studioCities)],
            'price' => 30 + (($i * 7) % 45),
            'type' => $studioTypes[$i % 2],
            'rating' => number_format(4.1 + (($i * 7) % 9) / 10, 1),
            'reviews' => 6 + (($i * 13) % 200),
            'photo' => $studioPhotos[$i % count($studioPhotos)],
        ])->all();

        // Filteropties conform de scope (§2.3).
        $daws = ['Logic', 'Pro Tools', 'FL Studio', 'Ableton', 'Cubase'];
        $equipment = ['mic_condenser', 'mic_dynamic', 'monitors', 'preamp', 'midi', 'amp'];
        $facilities = ['wifi', 'parking', 'kitchen', 'microwave', 'fridge', 'coffee', 'smoking', 'ac'];

        $subLabel = 'block text-xs font-bold uppercase tracking-wide text-prussian-blue/50';
        $checkbox = 'h-4 w-4 rounded border-prussian-blue/30 accent-ruby-red';
        $checkLabel = 'flex cursor-pointer items-center gap-2.5 text-sm text-prussian-blue';
        $field = 'w-full rounded-xl border border-prussian-blue/15 px-3 py-2 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none';
    @endphp

    <x-hero compact>
        <h1 class="text-4xl sm:text-5xl font-bold text-white">{{ __('studios.hero_heading') }}</h1>
        <p class="mx-auto mt-3 max-w-xl text-white/60">{{ __('studios.hero_subtitle') }}</p>
    </x-hero>

    <div class="py-12">
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
                        <x-filter-group :title="__('studios.filters.groups.location')" open>
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
                        <x-filter-group :title="__('studios.filters.groups.price')" open>
                            <div class="flex items-center gap-2">
                                <input type="number" min="0" placeholder="&euro; 0" class="{{ $field }}">
                                <span class="text-prussian-blue/30">&ndash;</span>
                                <input type="number" min="0" placeholder="&euro; 200" class="{{ $field }}">
                            </div>
                        </x-filter-group>

                        {{-- Beschikbaarheid --}}
                        <x-filter-group :title="__('studios.filters.groups.availability')">
                            <div class="flex gap-2">
                                <input type="date" class="{{ $field }}">
                                <input type="time" class="{{ $field }}">
                            </div>
                        </x-filter-group>

                        {{-- Studio: type, capaciteit, engineer --}}
                        <x-filter-group :title="__('studios.filters.groups.studio')">
                            <div>
                                <span class="{{ $subLabel }}">{{ __('studios.filters.type') }}</span>
                                <div class="mt-2 space-y-2">
                                    @foreach ([__('studios.type.recording'), __('studios.type.mixmaster')] as $label)
                                        <label class="{{ $checkLabel }}"><input type="checkbox" class="{{ $checkbox }}"> {{ $label }}</label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="mt-4">
                                <span class="{{ $subLabel }}">{{ __('studios.filters.capacity') }}</span>
                                <select class="{{ $field }} mt-2 cursor-pointer">
                                    <option value="">{{ __('studios.filters.capacity_any') }}</option>
                                    <option value="1">1&ndash;2</option>
                                    <option value="3">3&ndash;4</option>
                                    <option value="5">5&ndash;8</option>
                                    <option value="9">9+</option>
                                </select>
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

                        {{-- Uitrusting: apparatuur + DAW's --}}
                        <x-filter-group :title="__('studios.filters.groups.equipment')">
                            <div>
                                <span class="{{ $subLabel }}">{{ __('studios.filters.equipment') }}</span>
                                <div class="mt-2 space-y-2">
                                    @foreach ($equipment as $item)
                                        <label class="{{ $checkLabel }}"><input type="checkbox" class="{{ $checkbox }}"> {{ __('studios.equipment.' . $item) }}</label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="mt-4">
                                <span class="{{ $subLabel }}">{{ __('studios.filters.daw') }}</span>
                                <div class="mt-2 space-y-2">
                                    @foreach ($daws as $daw)
                                        <label class="{{ $checkLabel }}"><input type="checkbox" class="{{ $checkbox }}"> {{ $daw }}</label>
                                    @endforeach
                                </div>
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
        </div>
    </div>
</x-layout>
