<x-host-layout :title="$studio->name" active="studios">
    <nav class="text-sm text-prussian-blue/50">
        <a href="{{ route('host.studios.index') }}" class="hover:text-prussian-blue">{{ __('host.nav.studios') }}</a>
        <span class="px-1">/</span>
        <span class="text-prussian-blue/70">{{ $studio->name }}</span>
    </nav>

    <div class="mt-3 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-prussian-blue">{{ $studio->name }}</h1>
            <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-prussian-blue/60">
                <span><i class="fa-solid fa-location-dot fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $studio->fullAddress() }}</span>
                @if ($studio->phone)
                    <span><i class="fa-solid fa-phone fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $studio->phone }}</span>
                @endif
            </p>
        </div>
        <a href="{{ route('host.studios.edit', $studio) }}" class="flex h-9 items-center gap-2 rounded-full border border-prussian-blue/15 px-4 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">
            <i class="fa-solid fa-pen fa-xs"></i> {{ __('host.rooms.edit') }}
        </a>
    </div>

    {{-- Ruimtes van deze studio --}}
    <section class="mt-10">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-lg font-bold text-prussian-blue">{{ __('host.studios.rooms_title') }}</h2>
            <a href="{{ route('host.studios.rooms.create', $studio) }}" class="inline-flex items-center gap-2 rounded-full bg-ruby-red px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                <i class="fa-solid fa-plus fa-sm"></i> {{ __('host.rooms.add') }}
            </a>
        </div>

        <x-info-note class="mt-4">{{ __('host.studios.rooms_note') }}</x-info-note>

        @if ($studio->rooms->isEmpty())
            <div class="mt-4 flex flex-col items-center rounded-2xl border border-dashed border-prussian-blue/20 bg-white px-6 py-12 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-prussian-blue/5 text-lg text-prussian-blue/40"><i class="fa-solid fa-door-open"></i></span>
                <p class="mt-3 font-bold text-prussian-blue">{{ __('host.rooms.empty_title') }}</p>
                <p class="mt-1 max-w-sm text-sm text-prussian-blue/60">{{ __('host.rooms.empty_text') }}</p>
            </div>
        @else
            <div class="mt-4 space-y-4">
                @foreach ($studio->rooms as $room)
                    <div class="flex flex-wrap items-center gap-4 rounded-2xl border border-prussian-blue/10 bg-white p-4 sm:flex-nowrap">
                        @if ($room->photos->isNotEmpty())
                            <img src="{{ $room->photos->first()->thumbUrl() }}" alt="{{ $room->title }}" class="h-20 w-28 shrink-0 rounded-xl object-cover">
                        @else
                            <span class="flex h-20 w-28 shrink-0 items-center justify-center rounded-xl bg-prussian-blue/5 text-prussian-blue/30"><i class="fa-solid fa-image"></i></span>
                        @endif

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-bold text-prussian-blue">{{ $room->title }}</h3>
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $room->effectiveStatus()->badgeClasses() }}">{{ __('host.status.' . $room->effectiveStatus()->value) }}</span>
                            </div>
                            <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-prussian-blue/60">
                                <span><i class="fa-solid fa-tag fa-xs mr-1.5 text-prussian-blue/40"></i>{{ __('host.types.' . $room->type->value) }}</span>
                                <span><i class="fa-solid fa-euro-sign fa-xs mr-1.5 text-prussian-blue/40"></i>{{ number_format($room->hourlyRateEuros(), 2, ',', '.') }} {{ __('host.rooms.per_hour') }}</span>
                                <span><i class="fa-solid fa-users fa-xs mr-1.5 text-prussian-blue/40"></i>{{ __('host.rooms.capacity_short', ['count' => $room->capacity]) }}</span>
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <a href="{{ route('host.availability.edit', $room) }}" title="{{ __('host.nav.availability') }}" class="flex h-9 w-9 items-center justify-center rounded-full border border-prussian-blue/15 text-prussian-blue transition hover:bg-prussian-blue/5">
                                <i class="fa-solid fa-calendar-days fa-sm"></i>
                            </a>
                            <a href="{{ route('host.rooms.edit', $room) }}" class="flex h-9 items-center gap-2 rounded-full border border-prussian-blue/15 px-4 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">
                                <i class="fa-solid fa-pen fa-xs"></i> {{ __('host.rooms.edit') }}
                            </a>
                            <form method="POST" action="{{ route('host.rooms.destroy', $room) }}" data-confirm="{{ __('host.rooms.delete_confirm') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="{{ __('host.rooms.delete') }}" class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full text-prussian-blue/50 transition hover:bg-ruby-red/10 hover:text-ruby-red">
                                    <i class="fa-solid fa-trash fa-sm"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</x-host-layout>
