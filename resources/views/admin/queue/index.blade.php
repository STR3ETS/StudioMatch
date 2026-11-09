<x-admin-layout :title="__('admin.queue.title')" active="queue">
    <h1 class="text-2xl font-bold text-prussian-blue">{{ __('admin.queue.title') }}</h1>
    <p class="mt-2 text-prussian-blue/60">{{ __('admin.queue.subtitle') }}</p>

    @if ($rooms->isEmpty())
        <div class="mt-8 flex flex-col items-center rounded-2xl border border-dashed border-prussian-blue/20 bg-white px-6 py-14 text-center">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-500/10 text-xl text-emerald-600"><i class="fa-solid fa-circle-check"></i></span>
            <p class="mt-4 font-bold text-prussian-blue">{{ __('admin.queue.empty_title') }}</p>
            <p class="mt-1 max-w-sm text-sm text-prussian-blue/60">{{ __('admin.queue.empty_text') }}</p>
        </div>
    @else
        <div class="mt-8 space-y-4">
            @foreach ($rooms as $room)
                <a href="{{ route('admin.queue.show', $room) }}" class="flex flex-wrap items-center gap-4 rounded-2xl border border-prussian-blue/10 bg-white p-4 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-prussian-blue/5 sm:flex-nowrap">
                    {{-- Op een telefoon onder elkaar (foto, tekst, knop), anders persen ze de tekst samen tot een smalle kolom. --}}
                    @if ($room->photos->isNotEmpty())
                        <img src="{{ $room->photos->first()->thumbUrl() }}" alt="{{ $room->title }}" class="aspect-[16/9] w-full shrink-0 rounded-xl object-cover sm:aspect-auto sm:h-20 sm:w-28">
                    @else
                        <span class="flex aspect-[16/9] w-full shrink-0 items-center justify-center rounded-xl bg-prussian-blue/5 text-prussian-blue/30 sm:aspect-auto sm:h-20 sm:w-28"><i class="fa-solid fa-image"></i></span>
                    @endif

                    <div class="min-w-0 grow basis-full sm:basis-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-bold text-prussian-blue">{{ $room->title }}</h2>
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $room->status->badgeClasses() }}">{{ __('host.status.' . $room->status->value) }}</span>
                        </div>
                        <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-prussian-blue/60">
                            <span><i class="fa-solid fa-building fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $room->studio->name }} ({{ $room->studio->city }})</span>
                            <span><i class="fa-solid fa-user fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $room->studio->user->name }}</span>
                            <span><i class="fa-solid fa-clock fa-xs mr-1.5 text-prussian-blue/40"></i>{{ __('admin.queue.since', ['date' => $room->updated_at->translatedFormat('j M Y')]) }}</span>
                        </p>
                    </div>

                    <span class="flex min-h-11 w-full shrink-0 items-center justify-center gap-2 rounded-xl bg-ruby-red text-sm font-semibold text-white sm:min-h-0 sm:w-auto sm:bg-transparent sm:text-ruby-red">
                        {{ __('admin.queue.review') }} <i class="fa-solid fa-arrow-right fa-xs"></i>
                    </span>
                </a>
            @endforeach
        </div>
    @endif
</x-admin-layout>
