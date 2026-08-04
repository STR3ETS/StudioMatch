@php
    $studio = $room->studio;

    $facilityIcons = [
        'wifi' => 'fa-wifi',
        'parking' => 'fa-square-parking',
        'kitchen' => 'fa-utensils',
        'microwave' => 'fa-plate-wheat',
        'fridge' => 'fa-snowflake',
        'coffee' => 'fa-mug-hot',
        'smoking' => 'fa-smoking',
        'ac' => 'fa-fan',
    ];

    $photos = $room->photos->map->url()->all();
    $houseRules = collect(preg_split('/\r\n|\r|\n/', (string) $room->house_rules))->map(fn ($rule) => trim($rule))->filter()->values();

    $money = fn ($v) => '€ ' . number_format($v, 2, ',', '.');

    $hours = $room->min_hours;
    $rent = $room->hourlyRateEuros() * $hours;

    // Structured data voor zoekmachines (scope §2.1 SEO-basis).
    $schemaData = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => $studio->name . ' - ' . $room->title,
        'description' => Str::limit($room->description, 200),
        'image' => $photos,
        'url' => route('studios.show', $room),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $studio->street,
            'postalCode' => $studio->postal_code,
            'addressLocality' => $studio->city,
            'addressCountry' => 'NL',
        ],
        'geo' => $studio->lat !== null ? [
            '@type' => 'GeoCoordinates',
            'latitude' => $studio->lat,
            'longitude' => $studio->lng,
        ] : null,
        'priceRange' => '€' . number_format($room->hourlyRateEuros(), 0) . ' ' . __('studio.booking.per_hour'),
    ]);
@endphp

<x-layout :title="$studio->name . ' - ' . $room->title . ' · ' . $studio->city" :description="Str::limit($room->description, 160)" :schema="$schemaData">

    <div class="pt-28 pb-32 lg:pb-16">
        <div class="max-w-7xl mx-auto px-6">
            {{-- Breadcrumb + titel --}}
            <nav data-reveal class="text-sm text-prussian-blue/50">
                <a href="{{ route('studios') }}" class="hover:text-prussian-blue">{{ __('studio.breadcrumb') }}</a>
                <span class="px-1">/</span>
                <span class="text-prussian-blue/70">{{ $studio->name }} - {{ $room->title }}</span>
            </nav>

            <div data-reveal class="mt-3 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-prussian-blue">{{ $studio->name }} - {{ $room->title }}</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-prussian-blue/60">
                        <span class="flex items-center gap-1.5"><i class="fa-solid fa-location-dot text-[11px] text-prussian-blue/40"></i> {{ $studio->city }}</span>
                        <span class="rounded-full bg-prussian-blue/5 px-2 py-0.5 text-xs font-semibold text-prussian-blue">{{ $room->typeLabel() }}</span>
                    </div>
                </div>
            </div>

            {{-- Fotogalerij: 1 grote + 4 kleine; bij meer foto's een "+N" op de laatste tegel --}}
            @php $extraPhotos = count($photos) - 5; @endphp
            @if (count($photos) > 0)
                <div data-reveal style="--reveal-delay: .1s" class="mt-5 grid gap-2 overflow-hidden rounded-2xl sm:h-[440px] sm:grid-cols-4 sm:grid-rows-2">
                    <img src="{{ $photos[0] }}" alt="{{ $room->title }}" data-lightbox-src="{{ $photos[0] }}" @class([
                        'h-64 w-full cursor-zoom-in object-cover transition hover:brightness-90 sm:h-full',
                        'sm:col-span-2 sm:row-span-2' => count($photos) > 1,
                        'sm:col-span-4 sm:row-span-2' => count($photos) === 1,
                    ])>
                    @foreach (array_slice($photos, 1, 4) as $photo)
                        @if ($loop->last && $extraPhotos > 0)
                            <div class="relative hidden sm:block">
                                <img src="{{ $photo }}" alt="{{ $room->title }}" data-lightbox-src="{{ $photo }}" class="h-full w-full cursor-zoom-in object-cover transition hover:brightness-90">
                                <span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-prussian-blue/60 text-2xl font-bold text-white">+{{ $extraPhotos }}</span>
                            </div>
                        @else
                            <img src="{{ $photo }}" alt="{{ $room->title }}" data-lightbox-src="{{ $photo }}" class="hidden h-full w-full cursor-zoom-in object-cover transition hover:brightness-90 sm:block">
                        @endif
                    @endforeach

                    {{-- Extra foto's draaien wel mee in de lightbox --}}
                    @foreach (array_slice($photos, 5) as $photo)
                        <span class="hidden" data-lightbox-src="{{ $photo }}"></span>
                    @endforeach
                </div>
            @else
                <div data-reveal style="--reveal-delay: .1s" class="mt-5 flex h-64 items-center justify-center rounded-2xl bg-prussian-blue/5 text-prussian-blue/30 sm:h-[440px]">
                    <i class="fa-solid fa-image text-3xl"></i>
                </div>
            @endif

            {{-- Content: hoofdkolom + boekwidget --}}
            <div class="mt-10 flex flex-col gap-10 lg:flex-row">
                <div class="min-w-0 flex-1">
                    {{-- Kernfeiten --}}
                    <div class="flex flex-wrap gap-x-8 gap-y-3 border-b border-prussian-blue/10 pb-6 text-sm text-prussian-blue">
                        <span class="flex items-center gap-2"><i class="fa-solid fa-users text-ruby-red"></i> {{ __('studio.facts.capacity', ['count' => $room->capacity]) }}</span>
                        <span class="flex items-center gap-2"><i class="fa-solid fa-clock text-ruby-red"></i> {{ __('studio.facts.min_duration', ['count' => $room->min_hours]) }}</span>
                        <span class="flex items-center gap-2"><i class="fa-solid fa-headphones text-ruby-red"></i> {{ $room->engineer_included ? __('studio.facts.engineer_yes') : __('studio.facts.engineer_no') }}</span>
                    </div>

                    {{-- Over deze studio --}}
                    <section class="border-b border-prussian-blue/10 py-6">
                        <h2 class="text-lg font-bold text-prussian-blue">{{ __('studio.about') }}</h2>
                        <p class="mt-3 whitespace-pre-line leading-relaxed text-prussian-blue/70">{{ $room->description }}</p>
                    </section>

                    {{-- Apparatuur --}}
                    @if ($room->equipment || $room->equipment_extra)
                        <section class="border-b border-prussian-blue/10 py-6">
                            <h2 class="text-lg font-bold text-prussian-blue">{{ __('studio.equipment') }}</h2>
                            <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                @foreach ($room->equipment ?? [] as $item)
                                    <span class="flex items-center gap-2.5 text-sm text-prussian-blue/80"><i class="fa-solid fa-check text-ruby-red"></i> {{ __('studios.equipment.' . $item) }}</span>
                                @endforeach
                                @if ($room->equipment_extra)
                                    <span class="flex items-center gap-2.5 text-sm text-prussian-blue/80"><i class="fa-solid fa-check text-ruby-red"></i> {{ $room->equipment_extra }}</span>
                                @endif
                            </div>
                        </section>
                    @endif

                    {{-- DAW's --}}
                    @if ($room->daws)
                        <section class="border-b border-prussian-blue/10 py-6">
                            <h2 class="text-lg font-bold text-prussian-blue">{{ __('studio.daw') }}</h2>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($room->daws as $daw)
                                    <span class="rounded-full border border-prussian-blue/15 px-3 py-1 text-sm font-medium text-prussian-blue">{{ $daw }}</span>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    {{-- Voorzieningen --}}
                    @if ($room->facilities)
                        <section class="border-b border-prussian-blue/10 py-6">
                            <h2 class="text-lg font-bold text-prussian-blue">{{ __('studio.facilities') }}</h2>
                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                @foreach ($room->facilities as $facility)
                                    <span class="flex items-center gap-3 text-sm text-prussian-blue/80"><i class="fa-solid {{ $facilityIcons[$facility] ?? 'fa-check' }} w-5 text-center text-prussian-blue/40"></i> {{ __('studios.facilities.' . $facility) }}</span>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    {{-- Huisregels --}}
                    @if ($houseRules->isNotEmpty())
                        <section class="border-b border-prussian-blue/10 py-6">
                            <h2 class="text-lg font-bold text-prussian-blue">{{ __('studio.rules') }}</h2>
                            <ul class="mt-3 space-y-2">
                                @foreach ($houseRules as $rule)
                                    <li class="flex items-start gap-2.5 text-sm text-prussian-blue/70"><i class="fa-solid fa-circle mt-1.5 text-[5px] text-prussian-blue/40"></i> {{ $rule }}</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    {{-- Locatie --}}
                    <section class="pt-6">
                        <h2 class="text-lg font-bold text-prussian-blue">{{ __('studio.location') }}</h2>
                        <p class="mt-2 text-sm text-prussian-blue/70">{{ $studio->fullAddress() }}</p>
                        @if (count($mapStudios) > 0)
                            <x-studio-map :studios="$mapStudios" data-zoom="{{ $studio->lat !== null ? 15 : 12 }}" class="mt-3 h-[28rem] max-sm:h-80 border border-prussian-blue/10" />
                        @else
                            <div class="mt-3 flex h-64 items-center justify-center rounded-2xl bg-prussian-blue/5 text-prussian-blue/30">
                                <span class="flex items-center gap-2"><i class="fa-solid fa-map-location-dot"></i> {{ __('studio.map_placeholder') }}</span>
                            </div>
                        @endif
                    </section>
                </div>

                {{-- Boekwidget: kalender + tijdvak op basis van echte beschikbaarheid --}}
                <div data-reveal style="--reveal-delay: .15s" class="lg:w-[360px] lg:shrink-0">
                    <form method="GET" action="{{ route('studios.book', $room) }}" id="boeken"
                          data-availability='@json($freeHours)'
                          data-price-cents="{{ $room->hourly_rate_cents }}"
                          data-rent-label="{{ __('studio.booking.rent', ['count' => ':count']) }}"
                          data-hours-label="{{ __('studio.booking.hours', ['count' => ':count']) }}"
                          data-min-hours="{{ $room->min_hours }}"
                          data-max-hours="8"
                          class="custom-scrollbar scroll-mt-28 rounded-2xl border border-prussian-blue/10 bg-white p-6 shadow-lg lg:sticky lg:top-28 lg:max-h-[calc(100vh-8.5rem)] lg:overflow-y-auto">
                        <p class="text-prussian-blue">
                            <span class="text-2xl font-bold">{{ $money($room->hourlyRateEuros()) }}</span>
                            <span class="text-sm text-prussian-blue/50">{{ __('studio.booking.per_hour') }}</span>
                        </p>

                        @if ($errors->has('slot'))
                            <p class="mt-4 rounded-xl bg-ruby-red/10 px-4 py-3 text-sm font-semibold text-ruby-red">{{ $errors->first('slot') }}</p>
                        @endif

                        {{-- Kalender --}}
                        <div class="mt-4">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('studio.booking.date') }}</span>
                                <div class="flex items-center gap-1">
                                    <button type="button" data-cal-prev class="flex h-7 w-7 cursor-pointer items-center justify-center rounded-full text-prussian-blue/60 transition hover:bg-prussian-blue/5 disabled:cursor-default disabled:opacity-30"><i class="fa-solid fa-chevron-left fa-xs"></i></button>
                                    <span data-cal-month class="w-32 text-center text-sm font-semibold capitalize text-prussian-blue"></span>
                                    <button type="button" data-cal-next class="flex h-7 w-7 cursor-pointer items-center justify-center rounded-full text-prussian-blue/60 transition hover:bg-prussian-blue/5 disabled:cursor-default disabled:opacity-30"><i class="fa-solid fa-chevron-right fa-xs"></i></button>
                                </div>
                            </div>
                            <div data-cal-grid class="mt-2 grid grid-cols-7 gap-1"></div>
                        </div>

                        {{-- Aantal uur (plus/min-stepper) --}}
                        @php $initialHours = min(8, max($room->min_hours, (int) old('hours', request('hours', $hours)))); @endphp
                        <div class="mt-4">
                            <span class="block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('studio.booking.duration') }}</span>
                            <div class="mt-1 flex items-center justify-between rounded-xl border border-prussian-blue/15 px-3 py-2">
                                <span class="text-sm font-semibold text-prussian-blue" data-hours-label>{{ __('studio.booking.hours', ['count' => $initialHours]) }}</span>
                                <div class="flex items-center gap-2">
                                    <button type="button" data-hours-minus class="flex h-7 w-7 cursor-pointer items-center justify-center rounded-full border border-prussian-blue/20 text-prussian-blue transition hover:bg-prussian-blue/5 disabled:cursor-not-allowed disabled:opacity-30"><i class="fa-solid fa-minus text-[10px]"></i></button>
                                    <button type="button" data-hours-plus class="flex h-7 w-7 cursor-pointer items-center justify-center rounded-full border border-prussian-blue/20 text-prussian-blue transition hover:bg-prussian-blue/5 disabled:cursor-not-allowed disabled:opacity-30"><i class="fa-solid fa-plus text-[10px]"></i></button>
                                </div>
                            </div>
                            <input type="hidden" name="hours" value="{{ $initialHours }}">
                        </div>

                        {{-- Starttijden --}}
                        <div class="mt-4">
                            <span class="block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('studio.booking.time') }}</span>
                            <p data-slots-hint class="mt-2 rounded-xl bg-prussian-blue/[0.03] px-3 py-2.5 text-sm text-prussian-blue/50">{{ __('studio.booking.pick_date_first') }}</p>
                            <div data-slots class="mt-2 hidden grid-cols-4 gap-1.5"></div>
                        </div>

                        <input type="hidden" name="date" value="{{ old('date', request('date')) }}">
                        <input type="hidden" name="start" value="{{ old('start', request('start')) }}">

                        <div class="mt-5 flex justify-between border-t border-prussian-blue/10 pt-4 font-bold text-prussian-blue">
                            <span data-rent-text>{{ __('studio.booking.rent', ['count' => $hours]) }}</span>
                            <span data-total>{{ $money($rent) }}</span>
                        </div>

                        <button type="submit" data-book-submit disabled class="mt-5 w-full cursor-pointer rounded-full bg-ruby-red py-3 text-sm font-semibold text-white transition hover:bg-ruby-red/90 disabled:cursor-not-allowed disabled:opacity-40">{{ __('studio.booking.book') }}</button>

                        <p class="mt-3 text-center text-xs text-prussian-blue/50">{{ __('studio.booking.disclaimer') }}</p>
                        <p class="mt-2 flex items-center justify-center gap-1.5 text-center text-xs text-prussian-blue/50">
                            <i class="fa-solid fa-shield-halved text-prussian-blue/30"></i> {{ __('studio.booking.cancel') }}
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Sticky boekbalk op mobiel: vanaf-prijs + directe CTA naar de kalender (klantfeedback) --}}
    <div class="fixed inset-x-0 bottom-0 z-[1100] flex items-center justify-between gap-4 border-t border-prussian-blue/10 bg-white px-5 py-3 shadow-[0_-8px_30px_rgba(16,43,63,0.12)] lg:hidden">
        <p class="text-prussian-blue">
            <span class="block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('studio.booking.from') }}</span>
            <span class="text-lg font-bold">{{ $money($room->hourlyRateEuros()) }}</span>
            <span class="text-sm text-prussian-blue/50">{{ __('studio.booking.per_hour') }}</span>
        </p>
        <a href="#boeken" class="shrink-0 rounded-full bg-ruby-red px-6 py-3 text-sm font-semibold text-white transition hover:bg-ruby-red/90">{{ __('studio.booking.book') }}</a>
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
            <img src="" alt="{{ $room->title }}" class="max-h-full max-w-full rounded-2xl object-contain">
        </div>
    </div>

    {{-- Boekingskalender: datum → aantal uur → starttijd, op echte beschikbaarheid --}}
    <script>
        (() => {
            const form = document.getElementById('boeken');
            if (! form || ! form.dataset.availability) return;

            const AVAIL = JSON.parse(form.dataset.availability);
            const PRICE = parseInt(form.dataset.priceCents, 10);
            const RENT_LABEL = form.dataset.rentLabel;
            const NO_SLOTS = @json(__('studio.booking.no_slots'));
            const locale = document.documentElement.lang || 'nl';

            const monthEl = form.querySelector('[data-cal-month]');
            const prevBtn = form.querySelector('[data-cal-prev]');
            const nextBtn = form.querySelector('[data-cal-next]');
            const grid = form.querySelector('[data-cal-grid]');
            const slotsEl = form.querySelector('[data-slots]');
            const slotsHint = form.querySelector('[data-slots-hint]');
            const dateInput = form.querySelector('input[name=date]');
            const startInput = form.querySelector('input[name=start]');
            const hoursInput = form.querySelector('input[name=hours]');
            const hoursLabel = form.querySelector('[data-hours-label]');
            const hoursMinus = form.querySelector('[data-hours-minus]');
            const hoursPlus = form.querySelector('[data-hours-plus]');
            const MIN_HOURS = parseInt(form.dataset.minHours, 10);
            const MAX_HOURS = parseInt(form.dataset.maxHours, 10);
            const HOURS_LABEL = form.dataset.hoursLabel;
            const totalEl = form.querySelector('[data-total]');
            const rentText = form.querySelector('[data-rent-text]');
            const submit = form.querySelector('[data-book-submit]');

            const keys = Object.keys(AVAIL);
            const parseKey = (k) => new Date(k + 'T00:00:00');
            const firstMonth = new Date(parseKey(keys[0]).getFullYear(), parseKey(keys[0]).getMonth(), 1);
            const lastMonth = new Date(parseKey(keys[keys.length - 1]).getFullYear(), parseKey(keys[keys.length - 1]).getMonth(), 1);
            const dayKey = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');

            const money = new Intl.NumberFormat(locale === 'nl' ? 'nl-NL' : 'en-NL', { style: 'currency', currency: 'EUR' });
            const monthFmt = new Intl.DateTimeFormat(locale, { month: 'long', year: 'numeric' });
            const weekdayFmt = new Intl.DateTimeFormat(locale, { weekday: 'short' });

            const duration = () => parseInt(hoursInput.value, 10);

            // Starttijden waarvoor de hele duur aaneengesloten vrij is (raster van 1 uur).
            const startsFor = (k) => {
                const free = AVAIL[k];
                if (! free || ! free.length) return [];
                const set = new Set(free);
                const d = duration();
                return free.filter((s) => {
                    if (s + d > 24) return false;
                    for (let i = 1; i < d; i++) if (! set.has(s + i)) return false;
                    return true;
                });
            };

            let view = new Date(firstMonth);
            let selectedDate = dateInput.value && AVAIL[dateInput.value] ? dateInput.value : null;
            let selectedStart = selectedDate && startInput.value !== '' ? parseInt(startInput.value, 10) : null;
            if (selectedDate) view = new Date(parseKey(selectedDate).getFullYear(), parseKey(selectedDate).getMonth(), 1);

            const renderCalendar = () => {
                monthEl.textContent = monthFmt.format(view);
                prevBtn.disabled = view.getTime() <= firstMonth.getTime();
                nextBtn.disabled = view.getTime() >= lastMonth.getTime();

                let html = '';
                for (let i = 0; i < 7; i++) {
                    html += `<span class="py-1 text-center text-[10px] font-bold uppercase text-prussian-blue/40">${weekdayFmt.format(new Date(2024, 0, i + 1)).slice(0, 2)}</span>`;
                }
                const offset = (new Date(view.getFullYear(), view.getMonth(), 1).getDay() + 6) % 7;
                html += '<span></span>'.repeat(offset);
                const daysInMonth = new Date(view.getFullYear(), view.getMonth() + 1, 0).getDate();
                for (let d = 1; d <= daysInMonth; d++) {
                    const k = dayKey(new Date(view.getFullYear(), view.getMonth(), d));
                    const enabled = startsFor(k).length > 0;
                    const sel = k === selectedDate;
                    const classes = sel
                        ? 'bg-ruby-red font-bold text-white'
                        : (enabled ? 'cursor-pointer font-semibold text-prussian-blue hover:bg-ruby-red/10' : 'cursor-default text-prussian-blue/25');
                    html += `<button type="button" data-day="${k}" ${enabled ? '' : 'disabled'} class="aspect-square rounded-lg text-sm transition ${classes}">${d}</button>`;
                }
                grid.innerHTML = html;
            };

            const renderSlots = () => {
                if (! selectedDate) {
                    slotsEl.classList.add('hidden');
                    slotsEl.classList.remove('grid');
                    slotsHint.classList.remove('hidden');
                    return;
                }
                const starts = startsFor(selectedDate);
                slotsHint.classList.add('hidden');
                slotsEl.classList.remove('hidden');
                slotsEl.classList.add('grid');
                if (! starts.length) {
                    slotsEl.classList.remove('grid');
                    slotsEl.innerHTML = `<p class="rounded-xl bg-prussian-blue/[0.03] px-3 py-2.5 text-sm text-prussian-blue/50">${NO_SLOTS}</p>`;
                    return;
                }
                slotsEl.innerHTML = starts.map((s) => {
                    const sel = s === selectedStart;
                    const classes = sel
                        ? 'border-ruby-red bg-ruby-red font-bold text-white'
                        : 'border-prussian-blue/15 font-semibold text-prussian-blue hover:border-ruby-red/60';
                    return `<button type="button" data-slot="${s}" class="cursor-pointer rounded-xl border px-2 py-2 text-sm transition ${classes}">${String(s).padStart(2, '0')}:00</button>`;
                }).join('');
            };

            const sync = () => {
                dateInput.value = selectedDate ?? '';
                startInput.value = selectedStart ?? '';
                totalEl.textContent = money.format(PRICE * duration() / 100);
                rentText.textContent = RENT_LABEL.replace(':count', duration());
                submit.disabled = ! (selectedDate && selectedStart !== null);
            };

            grid.addEventListener('click', (event) => {
                const day = event.target.closest('[data-day]');
                if (! day || day.disabled) return;
                selectedDate = day.dataset.day;
                selectedStart = null;
                renderCalendar();
                renderSlots();
                sync();
            });

            slotsEl.addEventListener('click', (event) => {
                const slot = event.target.closest('[data-slot]');
                if (! slot) return;
                selectedStart = parseInt(slot.dataset.slot, 10);
                renderSlots();
                sync();
            });

            const setDuration = (value) => {
                const clamped = Math.min(MAX_HOURS, Math.max(MIN_HOURS, value));
                hoursInput.value = clamped;
                hoursLabel.textContent = HOURS_LABEL.replace(':count', clamped);
                hoursMinus.disabled = clamped <= MIN_HOURS;
                hoursPlus.disabled = clamped >= MAX_HOURS;
                if (selectedDate && selectedStart !== null && ! startsFor(selectedDate).includes(selectedStart)) selectedStart = null;
                if (selectedDate && ! startsFor(selectedDate).length) { selectedDate = null; selectedStart = null; }
                renderCalendar();
                renderSlots();
                sync();
            };

            hoursMinus.addEventListener('click', () => setDuration(duration() - 1));
            hoursPlus.addEventListener('click', () => setDuration(duration() + 1));

            prevBtn.addEventListener('click', () => { view = new Date(view.getFullYear(), view.getMonth() - 1, 1); renderCalendar(); });
            nextBtn.addEventListener('click', () => { view = new Date(view.getFullYear(), view.getMonth() + 1, 1); renderCalendar(); });

            setDuration(duration());
        })();
    </script>
</x-layout>
