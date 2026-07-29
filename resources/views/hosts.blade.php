<x-layout :title="__('hosts.meta_title')" :description="__('hosts.meta_description')">
    @php
        $benefits = [
            ['key' => 'exposure', 'icon' => 'fa-chart-line'],
            ['key' => 'admin', 'icon' => 'fa-wand-magic-sparkles'],
            ['key' => 'payout', 'icon' => 'fa-bolt'],
            ['key' => 'control', 'icon' => 'fa-sliders'],
        ];
    @endphp

    {{-- ===== Split-hero met mockup en zwevende UI-kaartjes ===== --}}
    <div class="relative w-full overflow-hidden bg-ruby-red pt-38 pb-20">
        <x-floating-icons />

        <div class="relative z-10 mx-auto max-w-7xl px-6">
            <div class="grid items-center gap-14 lg:grid-cols-2">
                <div data-reveal class="text-center lg:text-left">
                    <p class="text-sm font-semibold uppercase tracking-wider text-white/50">{{ __('hosts.hero.eyebrow') }}</p>
                    <h1 class="mt-3 text-4xl font-bold text-white sm:text-5xl">{{ __('hosts.hero.heading') }}</h1>
                    <p class="mt-4 max-w-xl text-white/60 max-lg:mx-auto">{{ __('hosts.hero.subtitle') }}</p>
                    <div class="mt-8 flex flex-wrap items-center gap-3 max-lg:justify-center">
                        <a href="#aanmelden" class="rounded-full bg-white px-6 py-3 text-sm font-semibold text-ruby-red shadow-sm transition hover:bg-white/90">{{ __('hosts.hero.cta') }}</a>
                        <a href="#hoe-werkt-het" class="rounded-full border border-white/25 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">{{ __('hosts.hero.secondary') }}</a>
                    </div>

                    {{-- Stats --}}
                    <div class="mt-10 flex gap-10 border-t border-white/10 pt-8">
                        @foreach (__('hosts.hero.stats') as $stat)
                            <div class="text-center lg:text-left">
                                <p class="text-2xl font-bold text-white sm:text-3xl">{{ $stat['value'] }}</p>
                                <p class="mt-1 text-xs leading-snug text-white/50">{{ $stat['label'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Mockup + zwevende kaartjes --}}
                <div data-reveal style="--reveal-delay: .15s" class="relative mx-auto w-fit max-lg:hidden">
                    <x-phone-mockup class="rotate-2" :label="__('hosts.hero.mock')" />

                    <div class="absolute -left-40 top-20 flex items-center gap-3 float-card rounded-2xl bg-white px-4 py-3 shadow-xl shadow-black/25">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-ruby-red/10 text-ruby-red"><i class="fa-solid fa-calendar-check fa-sm"></i></span>
                        <div>
                            <p class="text-xs font-bold text-prussian-blue">{{ __('hosts.hero.cards.booking_title') }}</p>
                            <p class="text-[11px] text-prussian-blue/50">{{ __('hosts.hero.cards.booking_sub') }}</p>
                        </div>
                    </div>

                    <div class="absolute -right-32 bottom-24 flex items-center gap-3 float-card rounded-2xl bg-white px-4 py-3 shadow-xl shadow-black/25">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600"><i class="fa-solid fa-money-bill-wave fa-sm"></i></span>
                        <div>
                            <p class="text-xs font-bold text-prussian-blue">{{ __('hosts.hero.cards.payout_title') }}</p>
                            <p class="text-[11px] text-prussian-blue/50">{{ __('hosts.hero.cards.payout_sub') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Statement: de afknappers, groot uitgeschreven ===== --}}
    <section class="bg-dots py-20">
        <div class="mx-auto max-w-7xl px-6 text-center">
            <div class="space-y-1">
                @foreach (__('hosts.statement.lines') as $line)
                    <p class="text-3xl font-bold text-prussian-blue sm:text-5xl"><span class="text-ruby-red">{{ __('hosts.statement.no') }}</span> {{ $line }}</p>
                @endforeach
            </div>
            <p class="mx-auto mt-6 max-w-xl text-prussian-blue/60">{{ __('hosts.statement.sub') }}</p>
        </div>
    </section>

    {{-- ===== Waarom StudioMatch ===== --}}
    <section class="py-20">
        <div class="mx-auto max-w-7xl px-6">
            <div class="max-w-2xl">
                <h2 class="text-3xl font-bold text-prussian-blue">{{ __('hosts.benefits.title') }}</h2>
                <p class="mt-3 text-prussian-blue/60">{{ __('hosts.benefits.subtitle') }}</p>
            </div>
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($benefits as $b)
                    <div @class([
                        'rounded-2xl p-6 transition hover:-translate-y-1',
                        'bg-prussian-blue text-white shadow-xl shadow-prussian-blue/20' => $loop->first,
                        'border border-prussian-blue/10 hover:shadow-lg hover:shadow-prussian-blue/5' => ! $loop->first,
                    ])>
                        <span @class([
                            'flex h-11 w-11 items-center justify-center rounded-xl',
                            'bg-white/10 text-white' => $loop->first,
                            'bg-ruby-red/10 text-ruby-red' => ! $loop->first,
                        ])><i class="fa-solid {{ $b['icon'] }}"></i></span>
                        <h3 @class(['mt-4 font-bold', 'text-white' => $loop->first, 'text-prussian-blue' => ! $loop->first])>{{ __('hosts.benefits.items.' . $b['key'] . '.title') }}</h3>
                        <p @class(['mt-2 text-sm leading-relaxed', 'text-white/60' => $loop->first, 'text-prussian-blue/60' => ! $loop->first])>{{ __('hosts.benefits.items.' . $b['key'] . '.text') }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===== Feature 1: Dashboard (wit, mockup rechts) ===== --}}
    <section class="py-20">
        <div class="mx-auto max-w-7xl px-6">
            <div class="flex flex-col items-center gap-12 lg:flex-row lg:gap-20">
                <div class="flex-1">
                    <span class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-ruby-red"><i class="fa-solid fa-gauge-high"></i> {{ __('hosts.features.items.dashboard.label') }}</span>
                    <h3 class="mt-2 text-2xl font-bold text-prussian-blue sm:text-3xl">{{ __('hosts.features.items.dashboard.title') }}</h3>
                    <p class="mt-3 text-prussian-blue/60">{{ __('hosts.features.items.dashboard.text') }}</p>
                    <ul class="mt-5 space-y-3">
                        @foreach (__('hosts.features.items.dashboard.bullets') as $bullet)
                            <li class="flex items-start gap-3 text-sm text-prussian-blue/80"><i class="fa-solid fa-check mt-0.5 text-ruby-red"></i> {{ $bullet }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="relative flex flex-1 justify-center">
                    <x-phone-mockup class="-rotate-2" :label="__('hosts.features.items.dashboard.mock')" />
                    <div class="absolute -right-2 top-16 hidden items-center gap-3 float-card rounded-2xl bg-white px-4 py-3 shadow-xl shadow-prussian-blue/15 xl:flex">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-ruby-red/10 text-ruby-red"><i class="fa-solid fa-chart-line fa-sm"></i></span>
                        <div>
                            <p class="text-xs font-bold text-prussian-blue">{{ __('hosts.features.items.dashboard.card_title') }}</p>
                            <p class="text-[11px] text-prussian-blue/50">{{ __('hosts.features.items.dashboard.card_sub') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== Feature 2: Beschikbaarheid (donker full-bleed, mockup links) ===== --}}
    <section class="relative overflow-hidden bg-prussian-blue py-20">
        <x-floating-icons />
        <div class="relative z-10 mx-auto max-w-7xl px-6">
            <div class="flex flex-col items-center gap-12 lg:flex-row-reverse lg:gap-20">
                <div class="flex-1">
                    <span class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-white/50"><i class="fa-solid fa-calendar-days"></i> {{ __('hosts.features.items.availability.label') }}</span>
                    <h3 class="mt-2 text-2xl font-bold text-white sm:text-3xl">{{ __('hosts.features.items.availability.title') }}</h3>
                    <p class="mt-3 text-white/60">{{ __('hosts.features.items.availability.text') }}</p>
                    <ul class="mt-5 space-y-3">
                        @foreach (__('hosts.features.items.availability.bullets') as $bullet)
                            <li class="flex items-start gap-3 text-sm text-white/80"><i class="fa-solid fa-check mt-0.5 text-ruby-red"></i> {{ $bullet }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="relative flex flex-1 justify-center">
                    <x-phone-mockup class="rotate-2" :label="__('hosts.features.items.availability.mock')" />
                    <div class="absolute -left-6 bottom-20 hidden items-center gap-3 float-card rounded-2xl bg-white px-4 py-3 shadow-xl shadow-black/30 xl:flex">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-prussian-blue/5 text-prussian-blue"><i class="fa-solid fa-umbrella-beach fa-sm"></i></span>
                        <div>
                            <p class="text-xs font-bold text-prussian-blue">{{ __('hosts.features.items.availability.card_title') }}</p>
                            <p class="text-[11px] text-prussian-blue/50">{{ __('hosts.features.items.availability.card_sub') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== Feature 3: Boekingen & uitbetaling (wit met dots, bonnetje i.p.v. mockup) ===== --}}
    <section class="bg-dots py-20">
        <div class="mx-auto max-w-7xl px-6">
            <div class="flex flex-col items-center gap-12 lg:flex-row lg:gap-20">
                <div class="flex-1">
                    <span class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-ruby-red"><i class="fa-solid fa-inbox"></i> {{ __('hosts.features.items.bookings.label') }}</span>
                    <h3 class="mt-2 text-2xl font-bold text-prussian-blue sm:text-3xl">{{ __('hosts.features.items.bookings.title') }}</h3>
                    <p class="mt-3 text-prussian-blue/60">{{ __('hosts.features.items.bookings.text') }}</p>
                    <ul class="mt-5 space-y-3">
                        @foreach (__('hosts.features.items.bookings.bullets') as $bullet)
                            <li class="flex items-start gap-3 text-sm text-prussian-blue/80"><i class="fa-solid fa-check mt-0.5 text-ruby-red"></i> {{ $bullet }}</li>
                        @endforeach
                    </ul>
                </div>

                {{-- Uitbetalings-bonnetje --}}
                <div class="relative w-full max-w-sm flex-1">
                    <div class="absolute inset-0 -rotate-3 rounded-3xl bg-prussian-blue/5"></div>
                    <div class="relative rotate-1 rounded-3xl border border-prussian-blue/10 bg-white p-6 shadow-2xl shadow-prussian-blue/10 transition-transform duration-300 hover:rotate-0">
                        <div class="flex items-center justify-between">
                            <p class="font-bold text-prussian-blue">{{ __('hosts.receipt.title') }}</p>
                            <span class="rounded-full bg-prussian-blue/5 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-prussian-blue/60">Stripe</span>
                        </div>
                        <div class="mt-5 space-y-3 text-sm">
                            <div class="flex justify-between text-prussian-blue/70">
                                <span>{{ __('hosts.receipt.booking') }}</span><span>€ 150,00</span>
                            </div>
                            <div class="flex justify-between text-prussian-blue/70">
                                <span>{{ __('hosts.receipt.commission') }}</span><span>− € 13,50</span>
                            </div>
                            <div class="flex justify-between text-prussian-blue/70">
                                <span>{{ __('hosts.receipt.vat') }}</span><span>− € 2,84</span>
                            </div>
                            <div class="flex justify-between border-t border-dashed border-prussian-blue/15 pt-3 font-bold text-prussian-blue">
                                <span>{{ __('hosts.receipt.net') }}</span><span class="text-ruby-red">€ 133,66</span>
                            </div>
                        </div>
                        <p class="mt-5 flex items-center gap-2 text-xs text-prussian-blue/50">
                            <i class="fa-solid fa-shield-halved text-prussian-blue/30"></i> {{ __('hosts.receipt.footer') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== Bento: alles in je dashboard ===== --}}
    <section class="bg-prussian-blue/5 py-20">
        <div class="mx-auto max-w-7xl px-6">
            <div class="max-w-2xl">
                <h2 class="text-3xl font-bold text-prussian-blue">{{ __('hosts.included.title') }}</h2>
                <p class="mt-3 text-prussian-blue/60">{{ __('hosts.included.subtitle') }}</p>
            </div>

            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {{-- Grote tegel: boekingsinbox met mini-preview --}}
                <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6 transition hover:-translate-y-1 hover:shadow-lg hover:shadow-prussian-blue/5 lg:col-span-2">
                    <div class="flex items-center justify-between gap-6">
                        <div>
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-ruby-red/10 text-ruby-red"><i class="fa-solid fa-inbox"></i></span>
                            <h3 class="mt-4 font-bold text-prussian-blue">{{ __('hosts.included.tiles.inbox.title') }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-prussian-blue/60 max-w-[400px]">{{ __('hosts.included.tiles.inbox.text') }}</p>
                        </div>
                        <div class="hidden w-64 shrink-0 rounded-2xl border border-prussian-blue/10 bg-prussian-blue/[0.03] p-4 sm:block">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-prussian-blue/10 text-xs font-bold text-prussian-blue">JV</span>
                                <div>
                                    <p class="text-xs font-bold text-prussian-blue">{{ __('hosts.included.tiles.inbox.request_title') }}</p>
                                    <p class="text-[11px] text-prussian-blue/50">{{ __('hosts.included.tiles.inbox.request_sub') }}</p>
                                </div>
                            </div>
                            <div class="mt-3 flex gap-2">
                                <span class="flex-1 rounded-full bg-ruby-red py-1.5 text-center text-[11px] font-semibold text-white">{{ __('hosts.included.tiles.inbox.accept') }}</span>
                                <span class="flex-1 rounded-full border border-prussian-blue/20 py-1.5 text-center text-[11px] font-semibold text-prussian-blue/70">{{ __('hosts.included.tiles.inbox.decline') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                @foreach ([['agenda', 'fa-calendar-days'], ['revenue', 'fa-chart-line'], ['invoices', 'fa-file-invoice'], ['rooms', 'fa-door-open']] as [$tile, $icon])
                    <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6 transition hover:-translate-y-1 hover:shadow-lg hover:shadow-prussian-blue/5">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-ruby-red/10 text-ruby-red"><i class="fa-solid {{ $icon }}"></i></span>
                        <h3 class="mt-4 font-bold text-prussian-blue">{{ __('hosts.included.tiles.' . $tile . '.title') }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-prussian-blue/60">{{ __('hosts.included.tiles.' . $tile . '.text') }}</p>
                    </div>
                @endforeach

                {{-- Brede tegel: Stripe --}}
                <div class="flex items-center gap-5 rounded-2xl bg-prussian-blue p-6 text-white lg:col-span-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/10"><i class="fa-solid fa-shield-halved"></i></span>
                    <div>
                        <h3 class="font-bold">{{ __('hosts.included.tiles.stripe.title') }}</h3>
                        <p class="mt-1 text-sm leading-relaxed text-white/60">{{ __('hosts.included.tiles.stripe.text') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== Tijdlijn: live in vier stappen ===== --}}
    <section id="hoe-werkt-het" class="py-20">
        <div class="mx-auto max-w-7xl px-6">
            <div class="max-w-2xl">
                <h2 class="text-3xl font-bold text-prussian-blue">{{ __('hosts.steps.title') }}</h2>
                <p class="mt-3 text-prussian-blue/60">{{ __('hosts.steps.subtitle') }}</p>
            </div>

            <div class="relative mt-14">
                <div class="absolute bottom-4 left-5 top-4 border-l-2 border-dashed border-prussian-blue/15 lg:bottom-auto lg:left-[12%] lg:right-[12%] lg:top-5 lg:border-l-0 lg:border-t-2"></div>
                <div class="grid gap-10 lg:grid-cols-4">
                    @foreach (__('hosts.steps.items') as $i => $step)
                        <div class="relative pl-16 lg:pl-0">
                            <span class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-full bg-ruby-red font-bold text-white ring-8 ring-white lg:static lg:inline-flex">{{ $i + 1 }}</span>
                            <h3 class="font-bold text-prussian-blue lg:mt-4">{{ $step['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-prussian-blue/60">{{ $step['text'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ===== Commissie-band met gigantische 9% ===== --}}
    <section class="pb-20">
        <div class="mx-auto max-w-7xl px-6">
            <div class="relative overflow-hidden rounded-3xl bg-prussian-blue px-8 py-14 lg:px-14">
                <span class="text-outline-white pointer-events-none absolute -right-4 -top-14 hidden select-none text-[14rem] font-black leading-none sm:block">9%</span>
                <div class="relative grid items-center gap-10 lg:grid-cols-2">
                    <div class="text-white">
                        <p class="text-sm font-semibold uppercase tracking-wider text-white/50">{{ __('hosts.commission.label') }}</p>
                        <p class="mt-1"><span class="text-6xl font-bold">{{ __('hosts.commission.value') }}</span> <span class="text-white/60">{{ __('hosts.commission.per_booking') }}</span></p>
                        <p class="mt-4 max-w-md text-white/70">{{ __('hosts.commission.text') }}</p>
                    </div>
                    <ul class="space-y-3">
                        @foreach (__('hosts.commission.bullets') as $bullet)
                            <li class="flex items-center gap-3 text-sm text-white/80"><i class="fa-solid fa-circle-check text-ruby-red"></i> {{ $bullet }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== FAQ ===== --}}
    <section class="bg-prussian-blue/5 py-20">
        <div class="mx-auto max-w-3xl px-6">
            <h2 class="text-center text-3xl font-bold text-prussian-blue">{{ __('hosts.faq.title') }}</h2>
            <div class="mt-8 space-y-3">
                @foreach (__('hosts.faq.items') as $item)
                    <details class="group rounded-2xl border border-prussian-blue/10 bg-white px-5 py-4 transition-colors hover:border-prussian-blue/30">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-prussian-blue [&::-webkit-details-marker]:hidden">
                            {{ $item['q'] }}
                            <i class="fa-solid fa-chevron-down text-sm text-prussian-blue/40 transition group-open:rotate-180"></i>
                        </summary>
                        <p class="mt-3 text-sm leading-relaxed text-prussian-blue/60">{{ $item['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===== Slot-CTA met geklipte mockup ===== --}}
    <section id="aanmelden" class="py-20">
        <div class="mx-auto max-w-7xl px-6">
            <div class="relative overflow-hidden rounded-[2.5rem] bg-prussian-blue px-8 py-14 lg:px-14">
                <x-floating-icons />
                <div class="relative z-10 max-w-xl max-lg:text-center">
                    <h2 class="text-3xl font-bold text-white sm:text-4xl">{{ __('hosts.cta.title') }}</h2>
                    <p class="mt-3 text-white/60">{{ __('hosts.cta.text') }}</p>
                    <a href="#" class="mt-7 inline-flex rounded-full bg-ruby-red px-8 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">{{ __('hosts.cta.button') }}</a>
                </div>
            </div>
        </div>
    </section>
</x-layout>
