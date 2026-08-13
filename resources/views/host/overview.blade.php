<x-host-layout :title="__('dashboard.host.meta_title')" active="overview">
    <div data-reveal>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-ruby-red/10 px-3 py-1 text-xs font-bold uppercase tracking-wide text-ruby-red"><i class="fa-solid fa-door-open fa-xs"></i> {{ __('dashboard.host.badge') }}</span>
        <h1 class="mt-3 text-3xl font-bold text-prussian-blue">{{ __('dashboard.greeting', ['name' => auth()->user()->firstName()]) }}</h1>
        <p class="mt-2 text-prussian-blue/60">{{ __('dashboard.host.subtitle') }}</p>
    </div>

    <section data-reveal style="--reveal-delay: .1s" class="mt-10">
        <div class="rounded-2xl bg-prussian-blue p-6 text-white sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold">{{ __('dashboard.host.checklist_title') }}</h2>
                    <p class="mt-1 text-sm text-white/60">{{ __('dashboard.host.checklist_text') }}</p>
                </div>
                <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold">{{ $checklistDone }} / {{ count($checklist) }}</span>
            </div>
            <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                @foreach ($checklist as $i => $step)
                    <li>
                        @if ($step['url'] && ! $step['done'])
                            <a href="{{ $step['url'] }}" class="flex items-center gap-3 rounded-xl bg-white/5 px-4 py-3 text-sm text-white/70 transition hover:bg-white/15 hover:text-white">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-white/25 text-xs font-bold">{{ $i + 1 }}</span>
                                {{ __('host.checklist.' . $step['key']) }}
                                <i class="fa-solid fa-arrow-right fa-xs ml-auto"></i>
                            </a>
                        @else
                            <span @class([
                                'flex items-center gap-3 rounded-xl px-4 py-3 text-sm',
                                'bg-white/10 font-semibold' => $step['done'],
                                'bg-white/5 text-white/50' => ! $step['done'],
                            ])>
                                <span @class([
                                    'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs',
                                    'bg-emerald-500 text-white' => $step['done'],
                                    'border border-white/25 font-bold' => ! $step['done'],
                                ])>
                                    @if ($step['done']) <i class="fa-solid fa-check"></i> @else {{ $i + 1 }} @endif
                                </span>
                                {{ __('host.checklist.' . $step['key']) }}
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    @if ($pendingRequests > 0)
        <section data-reveal style="--reveal-delay: .05s" class="mt-10">
            <a href="{{ route('host.bookings.index') }}" class="flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-prussian-blue p-6 text-white shadow-xl shadow-prussian-blue/20 transition hover:-translate-y-0.5 sm:flex-nowrap">
                <div class="flex items-center gap-4">
                    <span class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/10">
                        <i class="fa-solid fa-inbox"></i>
                        <span class="absolute -right-1.5 -top-1.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-ruby-red px-1 text-[10px] font-bold">{{ $pendingRequests }}</span>
                    </span>
                    <div>
                        <h2 class="font-bold">{{ trans_choice('host.overview.requests_pending', $pendingRequests, ['count' => $pendingRequests]) }}</h2>
                        <p class="mt-0.5 text-sm text-white/60">{{ __('host.overview.requests_text') }}</p>
                    </div>
                </div>
                <span class="flex shrink-0 items-center gap-2 text-sm font-semibold">{{ __('host.overview.requests_action') }} <i class="fa-solid fa-arrow-right fa-xs"></i></span>
            </a>
        </section>
    @endif

    @if ($rejectedRooms->isNotEmpty() || $inReviewRooms->isNotEmpty())
        <section data-reveal style="--reveal-delay: .15s" class="mt-10">
            <h2 class="text-lg font-bold text-prussian-blue">{{ __('host.overview.status_title') }}</h2>
            <div class="mt-4 space-y-3">
                @foreach ($rejectedRooms as $room)
                    <div class="flex flex-wrap items-center gap-4 rounded-2xl border border-ruby-red/30 bg-ruby-red/5 p-5 sm:flex-nowrap">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-ruby-red/10 text-ruby-red"><i class="fa-solid fa-circle-exclamation"></i></span>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-prussian-blue">{{ __('host.overview.status_rejected', ['room' => $room->title, 'studio' => $room->studio->name]) }}</p>
                            @if ($room->rejection_reason)
                                <p class="mt-1 text-sm leading-relaxed text-prussian-blue/70">
                                    <span class="font-semibold">{{ __('host.rooms.rejected_reason_label') }}:</span> {{ $room->rejection_reason }}
                                </p>
                            @endif
                        </div>
                        <a href="{{ route('host.rooms.edit', $room) }}" class="shrink-0 rounded-full bg-ruby-red px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                            {{ __('host.overview.status_rejected_action') }}
                        </a>
                    </div>
                @endforeach

                @if ($inReviewRooms->isNotEmpty())
                    <div class="flex flex-wrap items-center gap-4 rounded-2xl border border-amber-500/30 bg-amber-500/5 p-5 sm:flex-nowrap">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-500/10 text-amber-600"><i class="fa-solid fa-hourglass-half"></i></span>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-prussian-blue">{{ trans_choice('host.overview.status_in_review', $inReviewRooms->count(), ['rooms' => $inReviewRooms->pluck('title')->join(', ', ' ' . __('host.overview.and') . ' ')]) }}</p>
                            <p class="mt-1 text-sm text-prussian-blue/60">{{ __('host.overview.status_in_review_text') }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    @endif

    <section data-reveal style="--reveal-delay: .2s" class="mt-10">
        <div class="grid gap-4 sm:grid-cols-2">
            <a href="{{ route('host.profile.edit') }}" class="group rounded-2xl border border-prussian-blue/10 bg-white p-6 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-prussian-blue/5">
                <div class="flex items-start justify-between gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-ruby-red/10 text-ruby-red"><i class="fa-solid fa-briefcase"></i></span>
                    @if ($profile)
                        <span class="rounded-full bg-emerald-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-600">{{ __('host.overview.complete') }}</span>
                    @else
                        <span class="rounded-full bg-amber-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-amber-600">{{ __('host.overview.todo') }}</span>
                    @endif
                </div>
                <h3 class="mt-4 font-bold text-prussian-blue">{{ __('host.nav.profile') }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-prussian-blue/60">{{ __('host.overview.profile_text') }}</p>
            </a>

            <a href="{{ route('host.studios.index') }}" class="group rounded-2xl border border-prussian-blue/10 bg-white p-6 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-prussian-blue/5">
                <div class="flex items-start justify-between gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-ruby-red/10 text-ruby-red"><i class="fa-solid fa-building"></i></span>
                    <span class="flex flex-wrap justify-end gap-1.5">
                        <span class="rounded-full bg-prussian-blue/5 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-prussian-blue/60">{{ trans_choice('host.overview.studio_count', $studioCount, ['count' => $studioCount]) }}</span>
                        <span class="rounded-full bg-prussian-blue/5 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-prussian-blue/60">{{ trans_choice('host.overview.room_count', $roomCount, ['count' => $roomCount]) }}</span>
                    </span>
                </div>
                <h3 class="mt-4 font-bold text-prussian-blue">{{ __('host.nav.studios') }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-prussian-blue/60">{{ __('host.overview.studios_text') }}</p>
            </a>
        </div>
    </section>
</x-host-layout>
