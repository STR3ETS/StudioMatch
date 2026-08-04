<x-host-layout :title="__('host.studios.title')" active="studios">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-prussian-blue">{{ __('host.studios.title') }}</h1>
            <p class="mt-2 text-prussian-blue/60">{{ __('host.studios.subtitle') }}</p>
        </div>
        <a href="{{ route('host.studios.create') }}" class="inline-flex items-center gap-2 rounded-full bg-ruby-red px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
            <i class="fa-solid fa-plus fa-sm"></i> {{ __('host.studios.add') }}
        </a>
    </div>

    @if ($studios->isEmpty())
        <div class="mt-8 flex flex-col items-center rounded-2xl border border-dashed border-prussian-blue/20 bg-white px-6 py-14 text-center">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-prussian-blue/5 text-xl text-prussian-blue/40"><i class="fa-solid fa-building"></i></span>
            <p class="mt-4 font-bold text-prussian-blue">{{ __('host.studios.empty_title') }}</p>
            <p class="mt-1 max-w-sm text-sm text-prussian-blue/60">{{ __('host.studios.empty_text') }}</p>
            <a href="{{ route('host.studios.create') }}" class="mt-6 inline-flex items-center gap-2 rounded-full bg-ruby-red px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                <i class="fa-solid fa-plus fa-sm"></i> {{ __('host.studios.add') }}
            </a>
        </div>
    @else
        <div class="mt-8 space-y-4">
            @foreach ($studios as $studio)
                <div class="flex flex-wrap items-center gap-4 rounded-2xl border border-prussian-blue/10 bg-white p-5 sm:flex-nowrap">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-bold text-prussian-blue">{{ $studio->name }}</h2>
                            @if ($studio->rejected_rooms_count > 0)
                                <span title="{{ trans_choice('host.studios.action_needed_hint', $studio->rejected_rooms_count, ['count' => $studio->rejected_rooms_count]) }}"
                                      class="inline-flex items-center gap-1.5 rounded-full bg-ruby-red/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-ruby-red">
                                    <i class="fa-solid fa-circle-exclamation"></i> {{ __('host.studios.action_needed') }}
                                </span>
                            @endif
                        </div>
                        <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-prussian-blue/60">
                            <span><i class="fa-solid fa-location-dot fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $studio->fullAddress() }}</span>
                            <span><i class="fa-solid fa-door-open fa-xs mr-1.5 text-prussian-blue/40"></i>{{ trans_choice('host.overview.room_count', $studio->rooms_count, ['count' => $studio->rooms_count]) }}</span>
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <a href="{{ route('host.studios.show', $studio) }}" class="flex h-9 items-center gap-2 rounded-full bg-prussian-blue px-4 text-sm font-semibold text-white transition hover:bg-prussian-blue/90">
                            {{ __('host.studios.view') }} <i class="fa-solid fa-arrow-right fa-xs"></i>
                        </a>
                        <a href="{{ route('host.studios.edit', $studio) }}" title="{{ __('host.rooms.edit') }}" class="flex h-9 w-9 items-center justify-center rounded-full border border-prussian-blue/15 text-prussian-blue transition hover:bg-prussian-blue/5">
                            <i class="fa-solid fa-pen fa-xs"></i>
                        </a>
                        <form method="POST" action="{{ route('host.studios.destroy', $studio) }}" data-confirm="{{ __('host.studios.delete_confirm') }}">
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
</x-host-layout>
