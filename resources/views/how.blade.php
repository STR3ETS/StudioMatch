<x-layout :title="__('how.meta_title')" :description="__('how.meta_description')">
    {{-- ===== Hero ===== --}}
    <x-hero compact>
        <h1 class="text-4xl font-bold text-white sm:text-5xl">{{ __('how.hero.heading') }}</h1>
        <p class="mx-auto mt-4 max-w-2xl text-white/60">{{ __('how.hero.subtitle') }}</p>
        <a href="{{ route('hosts') }}" class="mt-7 inline-flex items-center gap-2 rounded-full border border-white/20 px-5 py-2.5 text-sm font-semibold text-white/80 transition hover:bg-white/10 hover:text-white">
            <i class="fa-solid fa-door-open fa-sm"></i> {{ __('how.hero.for_studios') }}
            <i class="fa-solid fa-arrow-right fa-xs"></i>
        </a>
    </x-hero>

    {{-- ===== Stap 01: Zoek & vergelijk (stippenpatroon) ===== --}}
    <section class="bg-dots py-20">
        <div class="mx-auto max-w-7xl px-6">
            <div class="flex flex-col items-center gap-12 lg:flex-row lg:gap-24">
                <div class="relative flex-1">
                    <span class="pointer-events-none absolute -top-8 -left-2 select-none text-[5rem] sm:-top-14 sm:-left-6 sm:text-[8rem] font-black leading-none text-prussian-blue/5">{{ __('how.steps.items.search.step') }}</span>
                    <div class="relative">
                        <span class="text-xs font-bold uppercase tracking-wide text-ruby-red">{{ __('how.steps.items.search.step') }} · {{ __('how.steps.items.search.label') }}</span>
                        <h3 class="mt-2 text-2xl font-bold text-prussian-blue sm:text-3xl">{{ __('how.steps.items.search.title') }}</h3>
                        <p class="mt-3 text-prussian-blue/60">{{ __('how.steps.items.search.text') }}</p>
                        <ul class="mt-5 space-y-3">
                            @foreach (__('how.steps.items.search.bullets') as $bullet)
                                <li class="flex items-start gap-3 text-sm text-prussian-blue/80"><i class="fa-solid fa-check mt-0.5 text-ruby-red"></i> {{ $bullet }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="flex flex-1 justify-center">
                    <x-phone-mockup class="-rotate-2" :label="__('how.steps.items.search.mock')" />
                </div>
            </div>
        </div>
    </section>

    {{-- ===== Stap 02: Boek & betaal (donker full-bleed) ===== --}}
    <section class="relative overflow-hidden bg-prussian-blue py-20">
        <div class="relative z-10 mx-auto max-w-7xl px-6">
            <div class="flex flex-col items-center gap-12 lg:flex-row-reverse lg:gap-24">
                <div class="relative flex-1">
                    <span class="pointer-events-none absolute -top-8 -left-2 select-none text-[5rem] sm:-top-14 sm:-left-6 sm:text-[8rem] font-black leading-none text-white/5">{{ __('how.steps.items.book.step') }}</span>
                    <div class="relative">
                        <span class="text-xs font-bold uppercase tracking-wide text-white/50">{{ __('how.steps.items.book.step') }} · {{ __('how.steps.items.book.label') }}</span>
                        <h3 class="mt-2 text-2xl font-bold text-white sm:text-3xl">{{ __('how.steps.items.book.title') }}</h3>
                        <p class="mt-3 text-white/60">{{ __('how.steps.items.book.text') }}</p>
                        <ul class="mt-5 space-y-3">
                            @foreach (__('how.steps.items.book.bullets') as $bullet)
                                <li class="flex items-start gap-3 text-sm text-white/80"><i class="fa-solid fa-check mt-0.5 text-ruby-red"></i> {{ $bullet }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="relative flex flex-1 justify-center">
                    <x-phone-mockup class="rotate-2" :label="__('how.steps.items.book.mock')" />
                    <div class="absolute -left-4 top-20 hidden items-center gap-3 float-card rounded-2xl bg-white px-4 py-3 shadow-xl shadow-black/30 xl:flex">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-ruby-red/10 text-ruby-red"><i class="fa-solid fa-hourglass-half fa-sm"></i></span>
                        <div>
                            <p class="text-xs font-bold text-prussian-blue">{{ __('how.steps.items.book.card_title') }}</p>
                            <p class="text-[11px] text-prussian-blue/50">{{ __('how.steps.items.book.card_sub') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== Stap 03: Bevestiging & sessie (stippenpatroon) ===== --}}
    <section class="bg-dots py-20">
        <div class="mx-auto max-w-7xl px-6">
            <div class="flex flex-col items-center gap-12 lg:flex-row lg:gap-24">
                <div class="relative flex-1">
                    <span class="pointer-events-none absolute -top-8 -left-2 select-none text-[5rem] sm:-top-14 sm:-left-6 sm:text-[8rem] font-black leading-none text-prussian-blue/5">{{ __('how.steps.items.session.step') }}</span>
                    <div class="relative">
                        <span class="text-xs font-bold uppercase tracking-wide text-ruby-red">{{ __('how.steps.items.session.step') }} · {{ __('how.steps.items.session.label') }}</span>
                        <h3 class="mt-2 text-2xl font-bold text-prussian-blue sm:text-3xl">{{ __('how.steps.items.session.title') }}</h3>
                        <p class="mt-3 text-prussian-blue/60">{{ __('how.steps.items.session.text') }}</p>
                        <ul class="mt-5 space-y-3">
                            @foreach (__('how.steps.items.session.bullets') as $bullet)
                                <li class="flex items-start gap-3 text-sm text-prussian-blue/80"><i class="fa-solid fa-check mt-0.5 text-ruby-red"></i> {{ $bullet }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="relative flex flex-1 justify-center">
                    <x-phone-mockup class="rotate-2" :label="__('how.steps.items.session.mock')" />
                    <div class="absolute -right-4 bottom-24 hidden items-center gap-3 float-card rounded-2xl bg-white px-4 py-3 shadow-xl shadow-prussian-blue/15 xl:flex">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600"><i class="fa-solid fa-circle-check fa-sm"></i></span>
                        <div>
                            <p class="text-xs font-bold text-prussian-blue">{{ __('how.steps.items.session.card_title') }}</p>
                            <p class="text-[11px] text-prussian-blue/50">{{ __('how.steps.items.session.card_sub') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== Prijsopbouw: donker paneel met bonnetje ===== --}}
    <section class="py-20">
        <div class="mx-auto max-w-7xl px-6">
            <div class="relative overflow-hidden rounded-3xl bg-prussian-blue px-8 py-14 lg:px-14">
                <div class="relative grid items-center gap-12 lg:grid-cols-2">
                    <div>
                        <span class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-white/50"><i class="fa-solid fa-receipt"></i> {{ __('how.pricing.label') }}</span>
                        <h2 class="mt-2 text-2xl font-bold text-white sm:text-3xl">{{ __('how.pricing.title') }}</h2>
                        <p class="mt-3 text-white/60">{{ __('how.pricing.text') }}</p>
                        <ul class="mt-5 space-y-3">
                            @foreach (__('how.pricing.bullets') as $bullet)
                                <li class="flex items-start gap-3 text-sm text-white/80"><i class="fa-solid fa-check mt-0.5 text-ruby-red"></i> {{ $bullet }}</li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="relative mx-auto w-full max-w-sm">
                        <div class="absolute inset-0 rotate-3 rounded-3xl bg-white/10"></div>
                        <div class="relative -rotate-1 rounded-3xl bg-white p-6 shadow-2xl shadow-black/30 transition-transform duration-300 hover:rotate-0">
                            <div class="flex items-center justify-between">
                                <p class="font-bold text-prussian-blue">{{ __('how.pricing.receipt.title') }}</p>
                                <span class="rounded-full bg-prussian-blue/5 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-prussian-blue/60">iDEAL</span>
                            </div>
                            <div class="mt-5 space-y-3 text-sm">
                                <div class="flex justify-between text-prussian-blue/70"><span>{{ __('how.pricing.receipt.rent') }}</span><span>€ 150,00</span></div>
                                <div class="flex justify-between text-prussian-blue/70"><span>{{ __('how.pricing.receipt.fee') }}</span><span>€ 16,34</span></div>
                                <div class="flex justify-between border-t border-dashed border-prussian-blue/15 pt-3 font-bold text-prussian-blue"><span>{{ __('how.pricing.receipt.total') }}</span><span class="text-ruby-red">€ 166,34</span></div>
                            </div>
                            <p class="mt-5 flex items-center gap-2 text-xs text-prussian-blue/50"><i class="fa-solid fa-lock text-prussian-blue/30"></i> {{ __('how.pricing.receipt.footer') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== Annuleren & verzetten (grijs) ===== --}}
    <section class="bg-prussian-blue/5 py-20">
        <div class="mx-auto max-w-7xl px-6">
            <div class="max-w-2xl">
                <h2 class="text-3xl font-bold text-prussian-blue">{{ __('how.cancel.title') }}</h2>
                <p class="mt-3 text-prussian-blue/60">{{ __('how.cancel.subtitle') }}</p>
            </div>

            @php
                $tierIcons = [
                    ['fa-circle-check', 'bg-emerald-500/10 text-emerald-600'],
                    ['fa-circle-half-stroke', 'bg-amber-500/10 text-amber-600'],
                    ['fa-circle-xmark', 'bg-ruby-red/10 text-ruby-red'],
                ];
            @endphp

            <div class="mt-10 grid gap-4 sm:grid-cols-3">
                @foreach (__('how.cancel.tiers') as $i => $tier)
                    <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6 transition hover:-translate-y-1 hover:shadow-lg hover:shadow-prussian-blue/5">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl {{ $tierIcons[$i][1] }}"><i class="fa-solid {{ $tierIcons[$i][0] }}"></i></span>
                        <p class="mt-4 text-sm text-prussian-blue/60">{{ $tier['when'] }}</p>
                        <p class="mt-1 text-xl font-bold text-prussian-blue">{{ $tier['refund'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <div class="flex gap-4 rounded-2xl border border-prussian-blue/10 bg-white p-6 transition hover:-translate-y-1 hover:shadow-lg hover:shadow-prussian-blue/5">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-prussian-blue/5 text-prussian-blue"><i class="fa-solid fa-calendar-plus"></i></span>
                    <div>
                        <h3 class="font-bold text-prussian-blue">{{ __('how.cancel.reschedule_title') }}</h3>
                        <p class="mt-1 text-sm leading-relaxed text-prussian-blue/60">{{ __('how.cancel.reschedule') }}</p>
                    </div>
                </div>
                <div class="flex gap-4 rounded-2xl bg-prussian-blue p-6 text-white">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/10"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <div>
                        <h3 class="font-bold">{{ __('how.cancel.problem_title') }}</h3>
                        <p class="mt-1 text-sm leading-relaxed text-white/60">{{ __('how.cancel.problem') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== Vertrouwen (wit) ===== --}}
    <section class="py-20">
        <div class="mx-auto max-w-7xl px-6">
            <h2 class="text-3xl font-bold text-prussian-blue">{{ __('how.trust.title') }}</h2>
            <div class="mt-10 grid gap-8 sm:grid-cols-3">
                @foreach (__('how.trust.items') as $item)
                    <div class="flex gap-4">
                        <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-prussian-blue/5 text-prussian-blue"><i class="fa-solid {{ $item['icon'] }} text-sm"></i></span>
                        <div>
                            <h3 class="font-bold text-prussian-blue">{{ $item['title'] }}</h3>
                            <p class="mt-1 text-sm leading-relaxed text-prussian-blue/60">{{ $item['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===== FAQ (grijs) ===== --}}
    <section class="bg-prussian-blue/5 py-20">
        <div class="mx-auto max-w-3xl px-6">
            <h2 class="text-center text-3xl font-bold text-prussian-blue">{{ __('how.faq.title') }}</h2>
            <div class="mt-8 space-y-3">
                @foreach (__('how.faq.items') as $item)
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

    {{-- ===== Slot-CTA ===== --}}
    <section class="py-20">
        <div class="mx-auto max-w-7xl px-6">
            <div class="relative overflow-hidden rounded-[2.5rem] bg-prussian-blue px-8 py-14 text-center lg:px-14">
                <x-floating-icons />
                <div class="relative z-10">
                    <h2 class="text-3xl font-bold text-white sm:text-4xl">{{ __('how.cta.title') }}</h2>
                    <p class="mx-auto mt-3 max-w-xl text-white/60">{{ __('how.cta.text') }}</p>
                    <a href="{{ route('studios') }}" class="mt-7 inline-flex items-center gap-2 rounded-full bg-ruby-red px-8 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                        <i class="fa-solid fa-magnifying-glass fa-sm"></i> {{ __('how.cta.button') }}
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-layout>
