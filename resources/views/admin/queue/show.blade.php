<x-admin-layout :title="$room->title . ' · ' . __('admin.queue.title')" active="queue">
    @php
        $label = 'text-xs font-bold uppercase tracking-wide text-prussian-blue/50';
    @endphp

    <nav class="text-sm text-prussian-blue/50">
        <a href="{{ route('admin.queue.index') }}" class="hover:text-prussian-blue">{{ __('admin.queue.title') }}</a>
        <span class="px-1">/</span>
        <span class="text-prussian-blue/70">{{ $room->title }}</span>
    </nav>

    <div class="mt-3 flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-bold text-prussian-blue">{{ $room->title }}</h1>
                <span class="rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide {{ $room->status->badgeClasses() }}">{{ __('host.status.' . $room->status->value) }}</span>
            </div>
            <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-prussian-blue/60">
                <span><i class="fa-solid fa-building fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $room->studio->name }}</span>
                <span><i class="fa-solid fa-location-dot fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $room->studio->fullAddress() }}</span>
                <span><i class="fa-solid fa-user fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $room->studio->user->name }} ({{ $room->studio->user->email }})</span>
            </p>
        </div>
    </div>

    {{-- Acties --}}
    <div class="mt-6 grid gap-4">
        <form method="POST" action="{{ route('admin.queue.approve', $room) }}" class="flex items-center justify-between gap-4 rounded-2xl border border-emerald-500/30 bg-emerald-500/5 p-5">
            @csrf
            @method('PATCH')
            <div>
                <p class="text-sm font-bold text-prussian-blue">{{ __('admin.queue.approve_title') }}</p>
                <p class="mt-0.5 text-xs text-prussian-blue/60">{{ __('admin.queue.approve_text') }}</p>
            </div>
            <button type="submit" class="shrink-0 cursor-pointer rounded-full bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                <i class="fa-solid fa-check fa-sm mr-1.5"></i>{{ __('admin.queue.approve_button') }}
            </button>
        </form>

        <form method="POST" action="{{ route('admin.queue.reject', $room) }}" class="rounded-2xl border border-ruby-red/30 bg-ruby-red/5 p-5">
            @csrf
            @method('PATCH')
            <p class="text-sm font-bold text-prussian-blue">{{ __('admin.queue.reject_title') }}</p>
            <textarea name="rejection_reason" rows="2" placeholder="{{ __('admin.queue.reject_placeholder') }}" class="mt-3 w-full rounded-xl border border-prussian-blue/15 bg-white px-4 py-2.5 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none" required>{{ old('rejection_reason') }}</textarea>
            <x-input-error field="rejection_reason" />
            <button type="submit" class="mt-3 cursor-pointer rounded-full bg-ruby-red px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-ruby-red/90">
                <i class="fa-solid fa-xmark fa-sm mr-1.5"></i>{{ __('admin.queue.reject_button') }}
            </button>
        </form>
    </div>

    {{-- Foto's --}}
    @if ($room->photos->isNotEmpty())
        <section class="mt-8">
            <h2 class="{{ $label }}">{{ __('host.rooms.section_photos') }}</h2>
            <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($room->photos as $photo)
                    <img src="{{ $photo->url() }}" alt="" class="aspect-[4/3] w-full rounded-xl object-cover">
                @endforeach
            </div>
        </section>
    @endif

    {{-- Gegevens --}}
    <section class="mt-8 rounded-2xl border border-prussian-blue/10 bg-white p-6 sm:p-8">
        <div class="grid gap-6 sm:grid-cols-3">
            <div>
                <h3 class="{{ $label }}">{{ __('host.rooms.fields.type') }}</h3>
                <p class="mt-1 text-sm text-prussian-blue">{{ __('host.types.' . $room->type->value) }}</p>
            </div>
            <div>
                <h3 class="{{ $label }}">{{ __('host.rooms.fields.hourly_rate') }}</h3>
                <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-prussian-blue">
                    <span>€ {{ number_format($room->hourlyRateEuros(), 2, ',', '.') }} {{ __('host.rooms.per_hour') }}</span>
                    <span class="text-prussian-blue/60"><i class="fa-solid fa-clock fa-xs mr-1.5 text-prussian-blue/40"></i>{{ __('host.rooms.min_hours_short', ['count' => $room->min_hours]) }}</span>
                </p>
            </div>
            <div>
                <h3 class="{{ $label }}">{{ __('host.rooms.fields.capacity') }}</h3>
                <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-prussian-blue">
                    <span>{{ __('host.rooms.capacity_short', ['count' => $room->capacity]) }}</span>
                    @if ($room->engineer_included)
                        <span class="text-prussian-blue/60"><i class="fa-solid fa-headphones fa-xs mr-1.5 text-prussian-blue/40"></i>{{ __('host.rooms.fields.engineer') }}</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="mt-6">
            <h3 class="{{ $label }}">{{ __('host.rooms.fields.description') }}</h3>
            <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-prussian-blue/80">{{ $room->description }}</p>
        </div>

        @if ($room->equipment || $room->equipment_extra)
            <div class="mt-6">
                <h3 class="{{ $label }}">{{ __('host.rooms.section_equipment') }}</h3>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ($room->equipment ?? [] as $item)
                        <span class="rounded-full border border-prussian-blue/15 px-3 py-1 text-xs font-medium text-prussian-blue">{{ __('studios.equipment.' . $item) }}</span>
                    @endforeach
                    @foreach ($room->daws ?? [] as $daw)
                        <span class="rounded-full border border-prussian-blue/15 px-3 py-1 text-xs font-medium text-prussian-blue">{{ $daw }}</span>
                    @endforeach
                </div>
                @if ($room->equipment_extra)
                    <p class="mt-2 text-sm text-prussian-blue/60">{{ $room->equipment_extra }}</p>
                @endif
            </div>
        @endif

        @if ($room->facilities)
            <div class="mt-6">
                <h3 class="{{ $label }}">{{ __('host.rooms.section_extras') }}</h3>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ($room->facilities as $facility)
                        <span class="rounded-full border border-prussian-blue/15 px-3 py-1 text-xs font-medium text-prussian-blue">{{ __('studios.facilities.' . $facility) }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($room->house_rules)
            <div class="mt-6">
                <h3 class="{{ $label }}">{{ __('host.rooms.fields.house_rules') }}</h3>
                <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-prussian-blue/80">{{ $room->house_rules }}</p>
            </div>
        @endif
    </section>
</x-admin-layout>
