<x-host-layout :title="__('host.availability.title') . ' · ' . $room->title" active="availability">
    @php
        $label = 'block text-xs font-bold uppercase tracking-wide text-prussian-blue/50';
        $field = 'mt-2 w-full rounded-xl border border-prussian-blue/15 bg-white px-4 py-2.5 text-sm text-prussian-blue focus:border-prussian-blue/40 focus:outline-none';
    @endphp

    <nav class="text-sm text-prussian-blue/50">
        <a href="{{ route('host.availability.index') }}" class="hover:text-prussian-blue">{{ __('host.nav.availability') }}</a>
        <span class="px-1">/</span>
        <span class="text-prussian-blue/70">{{ $room->title }}</span>
    </nav>

    <div class="mt-3 flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-prussian-blue">{{ $room->title }}</h1>
        <span class="rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide {{ $room->effectiveStatus()->badgeClasses() }}">{{ __('host.status.' . $room->effectiveStatus()->value) }}</span>
    </div>
    <x-info-note class="mt-4">{{ __('host.availability.edit_subtitle') }}</x-info-note>

    <section class="mt-8 rounded-2xl border border-prussian-blue/10 bg-white p-6 sm:p-8">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="font-bold text-prussian-blue"><i class="fa-solid fa-umbrella-beach mr-2 text-ruby-red"></i>{{ __('host.availability.vacation_title') }}</h2>
                <p class="mt-1 text-sm text-prussian-blue/60">{{ __('host.availability.vacation_text') }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('host.availability.vacation', $room) }}" class="mt-5 flex flex-wrap items-end gap-4">
            @csrf
            @method('PUT')
            <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-prussian-blue/15 px-4 py-3 text-sm font-semibold text-prussian-blue transition has-checked:border-ruby-red has-checked:bg-ruby-red/5">
                <input type="checkbox" name="on_vacation" value="1" class="h-4 w-4 rounded border-prussian-blue/30 accent-ruby-red" @checked(old('on_vacation', $room->on_vacation))>
                {{ __('host.availability.vacation_toggle') }}
            </label>
            <div>
                <label for="vacation_until" class="{{ $label }}">{{ __('host.availability.vacation_until') }}</label>
                <input id="vacation_until" type="date" name="vacation_until" min="{{ today()->toDateString() }}" value="{{ old('vacation_until', $room->vacation_until?->toDateString()) }}" class="{{ $field }}">
            </div>
            <button type="submit" class="cursor-pointer rounded-full bg-prussian-blue px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-prussian-blue/90">{{ __('host.availability.save') }}</button>
        </form>
        <x-input-error field="vacation_until" />
    </section>

    <section class="mt-8 rounded-2xl border border-prussian-blue/10 bg-white p-6 sm:p-8">
        <h2 class="font-bold text-prussian-blue"><i class="fa-solid fa-calendar-week mr-2 text-ruby-red"></i>{{ __('host.availability.schedule_title') }}</h2>
        <p class="mt-1 text-sm text-prussian-blue/60">{{ __('host.availability.schedule_text') }}</p>

        <form method="POST" action="{{ route('host.availability.schedule', $room) }}" class="mt-6">
            @csrf
            @method('PUT')

            <div class="space-y-2">
                @foreach ($room->hours as $day)
                    <div class="flex flex-wrap items-center gap-3 rounded-xl border border-prussian-blue/10 px-4 py-3 sm:flex-nowrap">
                        <label class="flex w-36 shrink-0 cursor-pointer items-center gap-2.5 text-sm font-semibold text-prussian-blue">
                            <input type="checkbox" name="days[{{ $day->weekday }}][is_open]" value="1" class="h-4 w-4 rounded border-prussian-blue/30 accent-ruby-red" @checked((bool) old('days.' . $day->weekday . '.is_open', $day->is_open))>
                            {{ __('host.availability.days.' . $day->weekday) }}
                        </label>
                        <div class="flex items-center gap-2 text-sm text-prussian-blue/60">
                            <select name="days[{{ $day->weekday }}][open_hour]" class="cursor-pointer rounded-xl border border-prussian-blue/15 px-3 py-2 text-sm text-prussian-blue focus:border-prussian-blue/40 focus:outline-none">
                                @for ($h = 0; $h <= 23; $h++)
                                    <option value="{{ $h }}" @selected((int) old('days.' . $day->weekday . '.open_hour', $day->open_hour) === $h)>{{ sprintf('%02d:00', $h) }}</option>
                                @endfor
                            </select>
                            <span>{{ __('host.availability.until') }}</span>
                            <select name="days[{{ $day->weekday }}][close_hour]" class="cursor-pointer rounded-xl border border-prussian-blue/15 px-3 py-2 text-sm text-prussian-blue focus:border-prussian-blue/40 focus:outline-none">
                                @for ($h = 1; $h <= 24; $h++)
                                    <option value="{{ $h }}" @selected((int) old('days.' . $day->weekday . '.close_hour', $day->close_hour) === $h)>{{ sprintf('%02d:00', $h % 24) === '00:00' && $h === 24 ? '24:00' : sprintf('%02d:00', $h) }}</option>
                                @endfor
                            </select>
                        </div>
                        <x-input-error field="days.{{ $day->weekday }}.close_hour" class="!mt-0" />
                    </div>
                @endforeach
            </div>

            <button type="submit" class="mt-6 cursor-pointer rounded-full bg-ruby-red px-8 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                {{ __('host.availability.schedule_save') }}
            </button>
        </form>
    </section>

    <section class="mt-8 rounded-2xl border border-prussian-blue/10 bg-white p-6 sm:p-8">
        <h2 class="font-bold text-prussian-blue"><i class="fa-solid fa-link mr-2 text-ruby-red"></i>{{ __('host.availability.ical_title') }}</h2>
        <p class="mt-1 text-sm text-prussian-blue/60">{{ __('host.availability.ical_text') }}</p>
        <div class="mt-4 flex flex-wrap items-center gap-2">
            <input type="text" readonly value="{{ URL::signedRoute('ical.room', ['room' => $room->id]) }}" onclick="this.select()"
                   class="min-w-0 flex-1 rounded-xl border border-prussian-blue/15 bg-prussian-blue/[0.03] px-4 py-2.5 text-sm text-prussian-blue/70 focus:outline-none">
            <button type="button"
                    onclick="navigator.clipboard.writeText(this.previousElementSibling.value); this.querySelector('span').textContent = @js(__('host.availability.ical_copied'));"
                    class="shrink-0 cursor-pointer rounded-full border border-prussian-blue/20 px-5 py-2.5 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">
                <i class="fa-solid fa-copy fa-sm mr-1.5"></i><span>{{ __('host.availability.ical_copy') }}</span>
            </button>
        </div>
    </section>

    <section class="mt-8 rounded-2xl border border-prussian-blue/10 bg-white p-6 sm:p-8">
        <h2 class="font-bold text-prussian-blue"><i class="fa-solid fa-calendar-xmark mr-2 text-ruby-red"></i>{{ __('host.availability.exceptions_title') }}</h2>
        <p class="mt-1 text-sm text-prussian-blue/60">{{ __('host.availability.exceptions_text') }}</p>

        <form method="POST" action="{{ route('host.availability.exceptions.store', $room) }}" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @csrf
            <div>
                <label for="ex-date" class="{{ $label }}">{{ __('host.availability.fields.date') }}</label>
                <input id="ex-date" type="date" name="date" min="{{ today()->toDateString() }}" value="{{ old('date') }}" class="{{ $field }}" required>
                <x-input-error field="date" />
            </div>
            <div>
                <label for="ex-type" class="{{ $label }}">{{ __('host.availability.fields.type') }}</label>
                <select id="ex-type" name="type" class="{{ $field }} cursor-pointer">
                    @foreach (\App\Enums\ExceptionType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ __('host.availability.types.' . $type->value) }}</option>
                    @endforeach
                </select>
                <x-input-error field="type" />
            </div>
            <div>
                <label for="ex-start" class="{{ $label }}">{{ __('host.availability.fields.start') }}</label>
                <select id="ex-start" name="start_hour" class="{{ $field }} cursor-pointer">
                    <option value="">—</option>
                    @for ($h = 0; $h <= 23; $h++)
                        <option value="{{ $h }}" @selected(old('start_hour') !== null && old('start_hour') !== '' && (int) old('start_hour') === $h)>{{ sprintf('%02d:00', $h) }}</option>
                    @endfor
                </select>
                <x-input-error field="start_hour" />
            </div>
            <div>
                <label for="ex-end" class="{{ $label }}">{{ __('host.availability.fields.end') }}</label>
                <select id="ex-end" name="end_hour" class="{{ $field }} cursor-pointer">
                    <option value="">—</option>
                    @for ($h = 1; $h <= 24; $h++)
                        <option value="{{ $h }}" @selected(old('end_hour') !== null && old('end_hour') !== '' && (int) old('end_hour') === $h)>{{ $h === 24 ? '24:00' : sprintf('%02d:00', $h) }}</option>
                    @endfor
                </select>
                <x-input-error field="end_hour" />
            </div>
            <div>
                <label for="ex-label" class="{{ $label }}">{{ __('host.availability.fields.label') }}</label>
                <input id="ex-label" type="text" name="label" value="{{ old('label') }}" placeholder="{{ __('host.availability.fields.label_placeholder') }}" class="{{ $field }}">
                <x-input-error field="label" />
            </div>
            <div class="sm:col-span-2 lg:col-span-5">
                <button type="submit" class="cursor-pointer rounded-full bg-prussian-blue px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-prussian-blue/90">
                    <i class="fa-solid fa-plus fa-sm mr-1.5"></i>{{ __('host.availability.exception_add') }}
                </button>
            </div>
        </form>

        @if ($exceptions->isEmpty())
            <p class="mt-6 rounded-xl bg-prussian-blue/[0.03] px-4 py-3 text-sm text-prussian-blue/50">{{ __('host.availability.exceptions_empty') }}</p>
        @else
            <ul class="mt-6 space-y-2">
                @foreach ($exceptions as $exception)
                    <li class="flex flex-wrap items-center gap-3 rounded-xl border border-prussian-blue/10 px-4 py-3 text-sm">
                        <span @class([
                            'flex h-8 w-8 shrink-0 items-center justify-center rounded-full',
                            'bg-emerald-500/10 text-emerald-600' => $exception->type === \App\Enums\ExceptionType::Open,
                            'bg-ruby-red/10 text-ruby-red' => $exception->type === \App\Enums\ExceptionType::Closed,
                            'bg-amber-500/10 text-amber-600' => $exception->type === \App\Enums\ExceptionType::Block,
                        ])>
                            <i class="fa-solid {{ match($exception->type) { \App\Enums\ExceptionType::Open => 'fa-door-open', \App\Enums\ExceptionType::Closed => 'fa-lock', \App\Enums\ExceptionType::Block => 'fa-ban' } }} fa-xs"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-prussian-blue">
                                {{ $exception->date->translatedFormat('D j M Y') }}
                                <span class="ml-1 font-normal text-prussian-blue/60">{{ __('host.availability.types.' . $exception->type->value) }}</span>
                                @if ($exception->start_hour !== null)
                                    <span class="ml-1 font-normal text-prussian-blue/60">{{ sprintf('%02d:00', $exception->start_hour) }} – {{ $exception->end_hour === 24 ? '24:00' : sprintf('%02d:00', $exception->end_hour) }}</span>
                                @endif
                            </p>
                            @if ($exception->label)
                                <p class="text-xs text-prussian-blue/50">{{ $exception->label }}</p>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('host.availability.exceptions.destroy', [$room, $exception]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="{{ __('host.rooms.delete') }}" class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full text-prussian-blue/40 transition hover:bg-ruby-red/10 hover:text-ruby-red">
                                <i class="fa-solid fa-trash fa-xs"></i>
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-host-layout>
