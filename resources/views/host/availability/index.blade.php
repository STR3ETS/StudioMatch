<x-host-layout :title="__('host.availability.title')" active="availability">
    <h1 class="text-2xl font-bold text-prussian-blue">{{ __('host.availability.title') }}</h1>
    <p class="mt-2 text-prussian-blue/60">{{ __('host.availability.subtitle') }}</p>

    @if ($rooms->isEmpty())
        <div class="mt-8 flex flex-col items-center rounded-2xl border border-dashed border-prussian-blue/20 bg-white px-6 py-14 text-center">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-prussian-blue/5 text-xl text-prussian-blue/40"><i class="fa-solid fa-calendar-days"></i></span>
            <p class="mt-4 font-bold text-prussian-blue">{{ __('host.rooms.empty_title') }}</p>
            <p class="mt-1 max-w-sm text-sm text-prussian-blue/60">{{ __('host.availability.empty_text') }}</p>
            <a href="{{ $hasStudios ? route('host.studios.index') : route('host.studios.create') }}" class="mt-6 inline-flex items-center gap-2 rounded-full bg-ruby-red px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                {{ $hasStudios ? __('host.nav.studios') : __('host.studios.add') }} <i class="fa-solid fa-arrow-right fa-xs"></i>
            </a>
        </div>
    @else
        <div class="mt-8 space-y-4">
            @foreach ($rooms as $room)
                @php $openDays = $room->hours->where('is_open', true)->count(); @endphp
                <a href="{{ route('host.availability.edit', $room) }}" class="flex flex-wrap items-center gap-4 rounded-2xl border border-prussian-blue/10 bg-white p-5 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-prussian-blue/5 sm:flex-nowrap">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-bold text-prussian-blue">{{ $room->title }}</h2>
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $room->effectiveStatus()->badgeClasses() }}">{{ __('host.status.' . $room->effectiveStatus()->value) }}</span>
                        </div>
                        <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-prussian-blue/60">
                            <span><i class="fa-solid fa-building fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $room->studio->name }}</span>
                            <span><i class="fa-solid fa-calendar-week fa-xs mr-1.5 text-prussian-blue/40"></i>{{ trans_choice('host.availability.open_days', $openDays, ['count' => $openDays]) }}</span>
                        </p>
                    </div>
                    <i class="fa-solid fa-arrow-right shrink-0 text-prussian-blue/30"></i>
                </a>
            @endforeach
        </div>
    @endif
</x-host-layout>
