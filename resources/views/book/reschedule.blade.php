@php
    $room = $booking->room;
@endphp

<x-layout :title="__('booking.reschedule.title')">
    <div class="pt-28 pb-16">
        <div class="mx-auto max-w-xl px-6">
            <nav data-reveal class="text-sm text-prussian-blue/50">
                <a href="{{ route('dashboard.artist') }}" class="hover:text-prussian-blue">{{ __('dashboard.artist.meta_title') }}</a>
                <span class="px-1">/</span>
                <span class="text-prussian-blue/70">{{ __('booking.reschedule.title') }}</span>
            </nav>

            <h1 data-reveal class="mt-3 text-3xl font-bold text-prussian-blue">{{ __('booking.reschedule.title') }}</h1>
            <p data-reveal class="mt-2 text-prussian-blue/60">{{ __('booking.reschedule.subtitle', ['room' => $room->title, 'hours' => $booking->hours()]) }}</p>

            <div data-reveal style="--reveal-delay: .1s" class="mt-6 flex flex-wrap items-center gap-x-4 gap-y-1 rounded-2xl border border-prussian-blue/10 bg-white p-5 text-sm text-prussian-blue/60">
                <span class="font-bold text-prussian-blue">{{ __('booking.reschedule.current') }}</span>
                <span><i class="fa-solid fa-calendar-days fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->date->translatedFormat('l j F Y') }}</span>
                <span><i class="fa-solid fa-clock fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->timeRange() }}</span>
            </div>

            <form method="POST" action="{{ route('bookings.reschedule.store', $booking) }}" id="verzetten"
                  data-availability='@json($freeHours)'
                  data-duration="{{ $booking->hours() }}"
                  data-reveal style="--reveal-delay: .15s"
                  class="mt-6 rounded-2xl border border-prussian-blue/10 bg-white p-6 shadow-lg">
                @csrf

                @if ($errors->has('slot'))
                    <p class="mb-4 rounded-xl bg-ruby-red/10 px-4 py-3 text-sm font-semibold text-ruby-red">{{ $errors->first('slot') }}</p>
                @endif

                {{-- Kalender --}}
                <div>
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

                {{-- Starttijden --}}
                <div class="mt-4">
                    <span class="block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('studio.booking.time') }}</span>
                    <p data-slots-hint class="mt-2 rounded-xl bg-prussian-blue/[0.03] px-3 py-2.5 text-sm text-prussian-blue/50">{{ __('studio.booking.pick_date_first') }}</p>
                    <div data-slots class="mt-2 hidden grid-cols-4 gap-1.5"></div>
                </div>

                <input type="hidden" name="date" value="">
                <input type="hidden" name="start" value="">

                <x-info-note class="mt-5">{{ __('booking.reschedule.note') }}</x-info-note>

                <div class="mt-5 flex items-center gap-3">
                    <button type="submit" data-book-submit disabled class="cursor-pointer rounded-full bg-ruby-red px-8 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90 disabled:cursor-not-allowed disabled:opacity-40">
                        {{ __('booking.reschedule.submit') }}
                    </button>
                    <a href="{{ route('dashboard.artist') }}" class="rounded-full border border-prussian-blue/20 px-6 py-3 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">
                        {{ __('host.rooms.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Zelfde kalenderlogica als de boekwidget, maar met vaste duur --}}
    <script>
        (() => {
            const form = document.getElementById('verzetten');
            if (! form) return;

            const AVAIL = JSON.parse(form.dataset.availability);
            const DURATION = parseInt(form.dataset.duration, 10);
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
            const submit = form.querySelector('[data-book-submit]');

            const keys = Object.keys(AVAIL);
            const parseKey = (k) => new Date(k + 'T00:00:00');
            const firstMonth = new Date(parseKey(keys[0]).getFullYear(), parseKey(keys[0]).getMonth(), 1);
            const lastMonth = new Date(parseKey(keys[keys.length - 1]).getFullYear(), parseKey(keys[keys.length - 1]).getMonth(), 1);
            const dayKey = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');

            const monthFmt = new Intl.DateTimeFormat(locale, { month: 'long', year: 'numeric' });
            const weekdayFmt = new Intl.DateTimeFormat(locale, { weekday: 'short' });

            const startsFor = (k) => {
                const free = AVAIL[k];
                if (! free || ! free.length) return [];
                const set = new Set(free);
                return free.filter((s) => {
                    if (s + DURATION > 24) return false;
                    for (let i = 1; i < DURATION; i++) if (! set.has(s + i)) return false;
                    return true;
                });
            };

            let view = new Date(firstMonth);
            let selectedDate = null;
            let selectedStart = null;

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

            prevBtn.addEventListener('click', () => { view = new Date(view.getFullYear(), view.getMonth() - 1, 1); renderCalendar(); });
            nextBtn.addEventListener('click', () => { view = new Date(view.getFullYear(), view.getMonth() + 1, 1); renderCalendar(); });

            renderCalendar();
            renderSlots();
            sync();
        })();
    </script>
</x-layout>
