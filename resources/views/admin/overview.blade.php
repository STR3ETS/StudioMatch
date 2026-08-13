<x-admin-layout :title="__('admin.overview.meta_title')" active="overview">
    <div data-reveal>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-ruby-red/10 px-3 py-1 text-xs font-bold uppercase tracking-wide text-ruby-red"><i class="fa-solid fa-shield-halved fa-xs"></i> {{ __('admin.badge') }}</span>
        <h1 class="mt-3 text-3xl font-bold text-prussian-blue">{{ __('dashboard.greeting', ['name' => auth()->user()->firstName()]) }}</h1>
        <p class="mt-2 text-prussian-blue/60">{{ __('admin.overview.subtitle') }}</p>
    </div>

    <section data-reveal style="--reveal-delay: .1s" class="mt-10">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600"><i class="fa-solid fa-hourglass-half"></i></span>
                <p class="mt-4 text-3xl font-bold text-prussian-blue">{{ $inReviewCount }}</p>
                <p class="mt-1 text-sm text-prussian-blue/60">{{ __('admin.overview.in_review') }}</p>
            </div>
            <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600"><i class="fa-solid fa-circle-check"></i></span>
                <p class="mt-4 text-3xl font-bold text-prussian-blue">{{ $liveCount }}</p>
                <p class="mt-1 text-sm text-prussian-blue/60">{{ __('admin.overview.live_rooms') }}</p>
            </div>
            <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-ruby-red/10 text-ruby-red"><i class="fa-solid fa-building"></i></span>
                <p class="mt-4 text-3xl font-bold text-prussian-blue">{{ $studioCount }}</p>
                <p class="mt-1 text-sm text-prussian-blue/60">{{ __('admin.overview.studios') }}</p>
            </div>
            <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-prussian-blue/5 text-prussian-blue"><i class="fa-solid fa-users"></i></span>
                <p class="mt-4 text-3xl font-bold text-prussian-blue">{{ $artistCount + $hostCount }}</p>
                <p class="mt-1 text-sm text-prussian-blue/60">{{ __('admin.overview.users', ['artists' => $artistCount, 'hosts' => $hostCount]) }}</p>
            </div>
        </div>
    </section>

    @if ($openTickets > 0)
        <section data-reveal style="--reveal-delay: .05s" class="mt-10">
            <a href="{{ route('admin.tickets.index') }}" class="flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-ruby-red p-6 text-white shadow-xl shadow-ruby-red/25 transition hover:-translate-y-0.5 sm:flex-nowrap">
                <div class="flex items-center gap-4">
                    <span class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15">
                        <i class="fa-solid fa-life-ring"></i>
                        <span class="absolute -right-1.5 -top-1.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-white px-1 text-[10px] font-bold text-ruby-red">{{ $openTickets }}</span>
                    </span>
                    <div>
                        <h2 class="font-bold">{{ trans_choice('admin.overview.tickets_pending', $openTickets, ['count' => $openTickets]) }}</h2>
                        <p class="mt-0.5 text-sm text-white/70">{{ __('admin.overview.tickets_text') }}</p>
                    </div>
                </div>
                <span class="flex shrink-0 items-center gap-2 text-sm font-semibold">{{ __('admin.overview.to_tickets') }} <i class="fa-solid fa-arrow-right fa-xs"></i></span>
            </a>
        </section>
    @endif

    <section data-reveal style="--reveal-delay: .2s" class="mt-10">
        <a href="{{ route('admin.queue.index') }}" @class([
            'flex flex-wrap items-center justify-between gap-4 rounded-2xl p-6 transition hover:-translate-y-0.5 sm:flex-nowrap',
            'bg-prussian-blue text-white shadow-xl shadow-prussian-blue/20' => $inReviewCount > 0,
            'border border-prussian-blue/10 bg-white hover:shadow-lg hover:shadow-prussian-blue/5' => $inReviewCount === 0,
        ])>
            <div class="flex items-center gap-4">
                <span @class([
                    'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl',
                    'bg-white/10 text-white' => $inReviewCount > 0,
                    'bg-ruby-red/10 text-ruby-red' => $inReviewCount === 0,
                ])><i class="fa-solid fa-clipboard-check"></i></span>
                <div>
                    <h2 @class(['font-bold', 'text-white' => $inReviewCount > 0, 'text-prussian-blue' => $inReviewCount === 0])>{{ __('admin.queue.title') }}</h2>
                    <p @class(['mt-0.5 text-sm', 'text-white/60' => $inReviewCount > 0, 'text-prussian-blue/60' => $inReviewCount === 0])>
                        {{ $inReviewCount > 0 ? trans_choice('admin.overview.queue_pending', $inReviewCount, ['count' => $inReviewCount]) : __('admin.queue.empty_title') }}
                    </p>
                </div>
            </div>
            <span @class([
                'flex shrink-0 items-center gap-2 text-sm font-semibold',
                'text-white' => $inReviewCount > 0,
                'text-ruby-red' => $inReviewCount === 0,
            ])>{{ __('admin.overview.to_queue') }} <i class="fa-solid fa-arrow-right fa-xs"></i></span>
        </a>
    </section>
</x-admin-layout>
