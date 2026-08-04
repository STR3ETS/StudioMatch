<x-host-layout :title="__('host.agenda.title')" active="agenda">
    <h1 class="text-2xl font-bold text-prussian-blue">{{ __('host.agenda.title') }}</h1>
    <p class="mt-2 text-prussian-blue/60">{{ __('host.agenda.subtitle') }}</p>

    @if ($days->isEmpty())
        <div class="mt-8 flex flex-col items-center rounded-2xl border border-dashed border-prussian-blue/20 bg-white px-6 py-14 text-center">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-prussian-blue/5 text-xl text-prussian-blue/40"><i class="fa-solid fa-calendar"></i></span>
            <p class="mt-4 font-bold text-prussian-blue">{{ __('host.agenda.empty_title') }}</p>
            <p class="mt-1 max-w-sm text-sm text-prussian-blue/60">{{ __('host.agenda.empty_text') }}</p>
        </div>
    @else
        <div class="mt-8 space-y-8">
            @foreach ($days as $date => $entries)
                <section>
                    <h2 class="text-sm font-bold uppercase tracking-wide text-prussian-blue/50">{{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('l j F Y') }}</h2>
                    <div class="mt-3 space-y-2">
                        @foreach ($entries as $entry)
                            @php $item = $entry['item']; @endphp
                            @if ($entry['kind'] === 'booking')
                                <div class="flex flex-wrap items-center gap-4 rounded-2xl border border-prussian-blue/10 bg-white p-4 sm:flex-nowrap">
                                    <span @class([
                                        'flex h-10 w-10 shrink-0 items-center justify-center rounded-full',
                                        'bg-emerald-500/10 text-emerald-600' => $item->status === \App\Enums\BookingStatus::Confirmed,
                                        'bg-amber-500/10 text-amber-600' => $item->status !== \App\Enums\BookingStatus::Confirmed,
                                    ])><i class="fa-solid fa-music"></i></span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-bold text-prussian-blue">{{ $item->timeRange() }}</p>
                                            <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $item->status->badgeClasses() }}">{{ __('booking.status.' . $item->status->value) }}</span>
                                        </div>
                                        <p class="mt-0.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-prussian-blue/60">
                                            <span><i class="fa-solid fa-door-open fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $item->room->title }}</span>
                                            <span><i class="fa-solid fa-user fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $item->user->name }}</span>
                                        </p>
                                    </div>
                                </div>
                            @else
                                <div class="flex flex-wrap items-center gap-4 rounded-2xl border border-prussian-blue/10 bg-prussian-blue/[0.03] p-4 sm:flex-nowrap">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-prussian-blue/5 text-prussian-blue/50">
                                        <i class="fa-solid {{ $entry['kind'] === 'closed' ? 'fa-lock' : 'fa-ban' }}"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-bold text-prussian-blue">
                                            {{ $entry['kind'] === 'closed'
                                                ? __('host.agenda.closed_all_day')
                                                : sprintf('%02d:00', $item->start_hour) . ' – ' . ($item->end_hour == 24 ? '24:00' : sprintf('%02d:00', $item->end_hour)) }}
                                        </p>
                                        <p class="mt-0.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-prussian-blue/60">
                                            <span><i class="fa-solid fa-door-open fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $item->room->title }}</span>
                                            <span>{{ $item->label ?: __('host.availability.types.' . $item->type->value) }}</span>
                                        </p>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</x-host-layout>
