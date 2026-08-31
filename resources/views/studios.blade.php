<x-layout :title="__('studios.meta_title')" :description="__('studios.meta_description')">
    @php
        $subLabel = 'block text-xs font-bold uppercase tracking-wide text-prussian-blue/50';
        $checkbox = 'h-4 w-4 rounded border-prussian-blue/30 accent-ruby-red';
        $checkLabel = 'flex cursor-pointer items-center gap-2.5 text-sm text-prussian-blue';
        $field = 'w-full rounded-xl border border-prussian-blue/15 px-3 py-2 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none';

        $checkedTypes = (array) request('types', []);
        $checkedEngineer = (array) request('engineer', []);
        $checkedEquipment = (array) request('equipment', []);
        $checkedDaws = (array) request('daws', []);
        $checkedFacilities = (array) request('facilities', []);
    @endphp

    <x-hero compact>
        <h1 class="text-4xl sm:text-5xl font-bold text-white">{{ __('studios.hero_heading') }}</h1>
        <p class="mx-auto mt-3 max-w-xl text-white/60">{{ __('studios.hero_subtitle') }}</p>
    </x-hero>

    <div class="pb-32 pt-16 lg:pb-16">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex flex-col gap-8 lg:flex-row">
                <aside data-reveal class="lg:w-72 lg:shrink-0">
                    <form id="studio-filters" method="GET" action="{{ route('studios') }}" class="custom-scrollbar rounded-2xl border border-prussian-blue/10 bg-white p-5 shadow-sm lg:sticky lg:top-24 lg:max-h-[85vh] lg:overflow-y-auto lg:overflow-x-hidden">
                        <div class="flex items-center justify-between pb-4">
                            <h2 class="text-base font-bold text-prussian-blue">{{ __('studios.filters.title') }}</h2>
                            <a href="{{ route('studios') }}" class="text-xs font-semibold text-ruby-red hover:underline">{{ __('studios.filters.clear') }}</a>
                        </div>

                        <x-filter-group :title="__('studios.filters.groups.location')">
                            <input type="text" name="location" value="{{ request('location') }}" placeholder="{{ __('studios.filters.location_placeholder') }}" class="{{ $field }}">
                            <input type="hidden" name="lat" value="{{ request('lat') }}">
                            <input type="hidden" name="lng" value="{{ request('lng') }}">
                            <button type="button" data-near-me class="mt-2 flex cursor-pointer items-start gap-1.5 text-left text-xs font-semibold text-ruby-red hover:underline">
                                <i class="fa-solid fa-location-crosshairs mt-0.5"></i>
                                <span>{{ request('lat') ? __('studios.filters.location_active') : __('studios.filters.near_me') }}</span>
                            </button>
                            <div data-radius-wrap @class(['mt-4', 'hidden' => ! (request('lat') || request('location'))])>
                                <div class="flex items-center justify-between">
                                    <span class="{{ $subLabel }}">{{ __('studios.filters.distance') }}</span>
                                    <span class="text-xs font-semibold text-prussian-blue" data-distance-value>{{ (int) request('radius', 25) }} km</span>
                                </div>
                                <input type="range" name="radius" min="1" max="100" value="{{ (int) request('radius', 25) }}" class="mt-2 w-full accent-ruby-red"
                                       oninput="this.closest('div').querySelector('[data-distance-value]').textContent = this.value + ' km'"
                                       onchange="this.form.requestSubmit()">
                            </div>
                        </x-filter-group>

                        <x-filter-group :title="__('studios.filters.groups.price')">
                            <div class="flex items-center gap-2">
                                <input type="number" name="price_min" value="{{ request('price_min') }}" min="0" placeholder="&euro; 0" class="{{ $field }}">
                                <span class="text-prussian-blue/30">&ndash;</span>
                                <input type="number" name="price_max" value="{{ request('price_max') }}" min="0" placeholder="&euro; 200" class="{{ $field }}">
                            </div>
                        </x-filter-group>

                        <x-filter-group :title="__('studios.filters.groups.availability')">
                            <div data-datepicker data-min="{{ today()->toDateString() }}" data-submit="1">
                                <input type="hidden" name="date" value="{{ request('date') }}">
                                <button type="button" data-datepicker-toggle class="{{ $field }} cursor-pointer text-left">
                                    <span data-datepicker-label class="{{ request('date') ? '' : 'text-prussian-blue/40' }}">{{ request('date') ? \Illuminate\Support\Carbon::parse(request('date'))->translatedFormat('j M Y') : __('studios.filters.date_placeholder') }}</span>
                                </button>
                                <div data-datepicker-panel class="mt-2 hidden rounded-2xl border border-prussian-blue/10 bg-white p-3"></div>
                            </div>
                            <div class="mt-2 grid grid-cols-2 gap-2">
                                <div class="min-w-0">
                                    <span class="{{ $subLabel }}">{{ __('studios.filters.start') }}</span>
                                    <select name="start" class="{{ $field }} mt-1 cursor-pointer">
                                        <option value="">--:--</option>
                                        @for ($h = 0; $h <= 23; $h++)
                                            <option value="{{ $h }}" @selected(request('start') !== null && request('start') !== '' && (int) request('start') === $h)>{{ sprintf('%02d:00', $h) }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="min-w-0">
                                    <span class="{{ $subLabel }}">{{ __('studios.filters.end') }}</span>
                                    <select name="end" class="{{ $field }} mt-1 cursor-pointer">
                                        <option value="">--:--</option>
                                        @for ($h = 1; $h <= 24; $h++)
                                            <option value="{{ $h }}" @selected(request('end') !== null && request('end') !== '' && (int) request('end') === $h)>{{ $h === 24 ? '24:00' : sprintf('%02d:00', $h) }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </x-filter-group>

                        <x-filter-group :title="__('studios.filters.groups.studio')">
                            <div>
                                <span class="{{ $subLabel }}">{{ __('studios.filters.type') }}</span>
                                <div class="mt-2 space-y-2">
                                    @foreach (\App\Enums\RoomType::cases() as $type)
                                        <label class="{{ $checkLabel }}"><input type="checkbox" name="types[]" value="{{ $type->value }}" class="{{ $checkbox }}" @checked(in_array($type->value, $checkedTypes))> {{ __('host.types.' . $type->value) }}</label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="mt-4">
                                <span class="{{ $subLabel }}">{{ __('studios.filters.capacity') }}</span>
                                <div class="mt-2 flex items-center justify-between rounded-xl border border-prussian-blue/15 px-3 py-2" data-stepper data-stepper-any="{{ __('studios.filters.capacity_any') }}" data-stepper-suffix=" {{ __('studios.filters.persons') }}">
                                    <span class="text-sm text-prussian-blue" data-stepper-label>{{ (int) request('capacity', 0) > 0 ? (int) request('capacity') . ' ' . __('studios.filters.persons') : __('studios.filters.capacity_any') }}</span>
                                    <div class="flex items-center gap-2">
                                        <button type="button" data-stepper-minus class="flex h-7 w-7 cursor-pointer items-center justify-center rounded-full border border-prussian-blue/20 text-prussian-blue transition hover:bg-prussian-blue/5 disabled:cursor-not-allowed disabled:opacity-30"><i class="fa-solid fa-minus text-[10px]"></i></button>
                                        <button type="button" data-stepper-plus class="flex h-7 w-7 cursor-pointer items-center justify-center rounded-full border border-prussian-blue/20 text-prussian-blue transition hover:bg-prussian-blue/5 disabled:cursor-not-allowed disabled:opacity-30"><i class="fa-solid fa-plus text-[10px]"></i></button>
                                    </div>
                                    <input type="hidden" name="capacity" value="{{ (int) request('capacity', 0) }}">
                                </div>
                            </div>
                            <div class="mt-4">
                                <span class="{{ $subLabel }}">{{ __('studios.filters.engineer') }}</span>
                                <div class="mt-2 space-y-2">
                                    <label class="{{ $checkLabel }}"><input type="checkbox" name="engineer[]" value="1" class="{{ $checkbox }}" @checked(in_array('1', $checkedEngineer, true))> {{ __('studios.engineer.with') }}</label>
                                    <label class="{{ $checkLabel }}"><input type="checkbox" name="engineer[]" value="0" class="{{ $checkbox }}" @checked(in_array('0', $checkedEngineer, true))> {{ __('studios.engineer.without') }}</label>
                                </div>
                            </div>
                        </x-filter-group>

                        <x-filter-group :title="__('studios.filters.groups.equipment')">
                            <div class="space-y-2">
                                @foreach (config('studio.equipment') as $item)
                                    <label class="{{ $checkLabel }}"><input type="checkbox" name="equipment[]" value="{{ $item }}" class="{{ $checkbox }}" @checked(in_array($item, $checkedEquipment))> {{ __('studios.equipment.' . $item) }}</label>
                                @endforeach
                            </div>
                        </x-filter-group>

                        <x-filter-group :title="__('studios.filters.groups.daw')">
                            <div class="space-y-2">
                                @foreach (config('studio.daws') as $daw)
                                    <label class="{{ $checkLabel }}"><input type="checkbox" name="daws[]" value="{{ $daw }}" class="{{ $checkbox }}" @checked(in_array($daw, $checkedDaws))> {{ $daw }}</label>
                                @endforeach
                            </div>
                        </x-filter-group>

                        <x-filter-group :title="__('studios.filters.groups.facilities')">
                            <div class="space-y-2">
                                @foreach (config('studio.facilities') as $facility)
                                    <label class="{{ $checkLabel }}"><input type="checkbox" name="facilities[]" value="{{ $facility }}" class="{{ $checkbox }}" @checked(in_array($facility, $checkedFacilities))> {{ __('studios.facilities.' . $facility) }}</label>
                                @endforeach
                            </div>
                        </x-filter-group>

                        <div class="sticky bottom-0 -mx-5 mt-5 border-t border-prussian-blue/10 bg-white px-5 pb-1 pt-4">
                            <button type="submit" class="w-full cursor-pointer rounded-full bg-ruby-red py-2.5 text-sm font-semibold text-white transition hover:bg-ruby-red/90">{{ __('studios.filters.apply') }}</button>
                        </div>
                    </form>
                </aside>

                <div data-reveal data-results style="--reveal-delay: .1s" class="flex-1 transition-opacity duration-200">
                    <div class="mb-5 flex items-center justify-between gap-4">
                        <p data-results-count class="text-sm font-medium text-prussian-blue/60">{{ __('studios.results', ['count' => $cards->count()]) }}</p>
                        <label class="flex items-center gap-2 text-sm text-prussian-blue">
                            <span class="hidden text-prussian-blue/50 sm:inline">{{ __('studios.sort.label') }}</span>
                            <select name="sort" form="studio-filters" onchange="document.getElementById('studio-filters').requestSubmit()" class="cursor-pointer rounded-xl border border-prussian-blue/15 px-3 py-2 text-sm font-medium text-prussian-blue focus:border-prussian-blue/40 focus:outline-none">
                                <option value="relevance" @selected(request('sort', 'relevance') === 'relevance')>{{ __('studios.sort.relevance') }}</option>
                                @if (request('lat'))
                                    <option value="distance" @selected(request('sort') === 'distance')>{{ __('studios.sort.distance') }}</option>
                                @endif
                                <option value="price_asc" @selected(request('sort') === 'price_asc')>{{ __('studios.sort.price_asc') }}</option>
                                <option value="price_desc" @selected(request('sort') === 'price_desc')>{{ __('studios.sort.price_desc') }}</option>
                            </select>
                        </label>
                    </div>

                    @if ($cards->isEmpty())
                        <div class="flex flex-col items-center rounded-2xl border border-dashed border-prussian-blue/20 bg-white px-6 py-16 text-center">
                            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-prussian-blue/5 text-xl text-prussian-blue/40"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <p class="mt-4 font-bold text-prussian-blue">{{ __('studios.empty_title') }}</p>
                            <p class="mt-1 max-w-sm text-sm text-prussian-blue/60">{{ __('studios.empty_text') }}</p>
                            <a href="{{ route('studios') }}" class="mt-6 inline-flex items-center gap-2 rounded-full border border-prussian-blue/20 px-6 py-2.5 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">
                                {{ __('studios.filters.clear') }}
                            </a>
                        </div>
                    @else
                        <div data-loadmore data-loadmore-initial="16" data-loadmore-step="16">
                            <div data-loadmore-grid class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
                                @foreach ($cards as $card)
                                    <x-studio-card :studio="$card" />
                                @endforeach
                            </div>
                            <div class="mt-10 flex justify-center">
                                <button type="button" data-loadmore-btn class="cursor-pointer rounded-full border border-prussian-blue/20 px-6 py-2.5 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">
                                    {{ __('studios.load_more') }}
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div data-results-map>
                @if (count($mapStudios) > 0)
                    <div data-reveal class="mt-16">
                        <x-studio-map :studios="$mapStudios" class="aspect-[2/1] rounded-[2.5rem] border border-prussian-blue/10" />
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="fixed inset-x-0 bottom-0 z-[1100] flex items-center justify-between gap-4 border-t border-prussian-blue/10 bg-white px-5 py-3 shadow-[0_-8px_30px_rgba(16,43,63,0.12)] lg:hidden">
        <p data-sticky-count class="text-sm font-medium text-prussian-blue/60">{{ __('studios.results', ['count' => $cards->count()]) }}</p>
        <button type="submit" form="studio-filters" class="shrink-0 cursor-pointer rounded-full bg-ruby-red px-6 py-3 text-sm font-semibold text-white transition hover:bg-ruby-red/90">
            {{ __('studios.filters.apply') }}
        </button>
    </div>

    <script>
        (() => {
            const form = document.getElementById('studio-filters');
            const results = document.querySelector('[data-results]');
            const mapWrap = document.querySelector('[data-results-map]');
            const radiusWrap = document.querySelector('[data-radius-wrap]');
            if (! form || ! results) return;

            let controller = null;

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                const params = new URLSearchParams(new FormData(form));
                [...params.keys()].forEach((key) => {
                    if (params.getAll(key).every((value) => value === '')) params.delete(key);
                });
                const url = form.action + (params.toString() ? '?' + params.toString() : '');

                results.classList.add('opacity-40', 'pointer-events-none');
                controller?.abort();
                controller = new AbortController();

                try {
                    const response = await fetch(url, { signal: controller.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    if (! response.ok) throw new Error('fetch failed');
                    const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const newResults = doc.querySelector('[data-results]');
                    const newMap = doc.querySelector('[data-results-map]');
                    if (! newResults) throw new Error('missing results');

                    results.innerHTML = newResults.innerHTML;
                    if (mapWrap && newMap) mapWrap.innerHTML = newMap.innerHTML;
                    history.replaceState({}, '', url);

                    const stickyCount = document.querySelector('[data-sticky-count]');
                    const freshCount = results.querySelector('[data-results-count]');
                    if (stickyCount && freshCount) stickyCount.textContent = freshCount.textContent;

                    // On mobile the filter panel sits above the results, so jump to them.
                    if (window.innerWidth < 1024) results.scrollIntoView({ behavior: 'smooth', block: 'start' });

                    const hasLocation = form.elements.location.value.trim() !== '' || form.elements.lat.value !== '';
                    radiusWrap?.classList.toggle('hidden', ! hasLocation);

                    document.dispatchEvent(new CustomEvent('sm:results-updated'));
                } catch (error) {
                    if (error.name !== 'AbortError') window.location.href = url;
                    return;
                } finally {
                    results.classList.remove('opacity-40', 'pointer-events-none');
                }
            });
        })();

        (() => {
            const button = document.querySelector('[data-near-me]');
            if (! button || ! navigator.geolocation) return;
            button.addEventListener('click', () => {
                button.classList.add('opacity-50');
                navigator.geolocation.getCurrentPosition((position) => {
                    const form = button.closest('form');
                    form.querySelector('input[name=lat]').value = position.coords.latitude.toFixed(5);
                    form.querySelector('input[name=lng]').value = position.coords.longitude.toFixed(5);
                    form.requestSubmit();
                }, () => {
                    button.classList.remove('opacity-50');
                    button.querySelector('span').textContent = @json(__('studios.filters.near_me_error'));
                });
            });
        })();
    </script>
</x-layout>
