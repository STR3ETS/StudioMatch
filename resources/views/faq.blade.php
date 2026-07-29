<x-layout :title="__('faq.meta_title')" :description="__('faq.meta_description')">
    @php
        $categoryIcons = [
            'booking' => 'fa-calendar-check',
            'cancel' => 'fa-rotate-left',
            'hosts' => 'fa-door-open',
            'account' => 'fa-user',
        ];
    @endphp

    {{-- ===== Hero ===== --}}
    <x-hero compact>
        <h1 class="text-4xl font-bold text-white sm:text-5xl">{{ __('faq.hero.heading') }}</h1>
        <p class="mx-auto mt-4 max-w-2xl text-white/60">{{ __('faq.hero.subtitle') }}</p>
    </x-hero>

    {{-- ===== Categorieën ===== --}}
    <section class="py-16">
        <div class="mx-auto max-w-7xl px-6">
            <div class="flex flex-col gap-10 lg:flex-row">
                {{-- Categorie-navigatie --}}
                <aside class="lg:w-64 lg:shrink-0">
                    <nav class="flex gap-2 overflow-x-auto pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden lg:sticky lg:top-28 lg:flex-col lg:overflow-visible lg:pb-0">
                        @foreach (__('faq.categories') as $key => $category)
                            <a href="#faq-{{ $key }}" class="flex shrink-0 items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold text-prussian-blue/70 transition hover:bg-prussian-blue/5 hover:text-prussian-blue">
                                <i class="fa-solid {{ $categoryIcons[$key] }} w-4 text-center text-ruby-red"></i>
                                {{ $category['title'] }}
                            </a>
                        @endforeach
                    </nav>
                </aside>

                {{-- Vragen per categorie --}}
                <div class="min-w-0 flex-1 space-y-12">
                    @foreach (__('faq.categories') as $key => $category)
                        <div id="faq-{{ $key }}" class="scroll-mt-28">
                            <h2 class="flex items-center gap-3 text-xl font-bold text-prussian-blue">
                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-ruby-red/10 text-sm text-ruby-red"><i class="fa-solid {{ $categoryIcons[$key] }}"></i></span>
                                {{ $category['title'] }}
                            </h2>
                            <div class="mt-4 space-y-3">
                                @foreach ($category['items'] as $item)
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
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ===== Contact-CTA ===== --}}
    <section class="pb-20">
        <div class="mx-auto max-w-7xl px-6">
            <div class="relative overflow-hidden rounded-[2.5rem] bg-prussian-blue px-8 py-14 text-center lg:px-14">
                <x-floating-icons />
                <div class="relative z-10">
                    <h2 class="text-3xl font-bold text-white sm:text-4xl">{{ __('faq.cta.title') }}</h2>
                    <p class="mx-auto mt-3 max-w-xl text-white/60">{{ __('faq.cta.text') }}</p>
                    <a href="{{ route('contact') }}" class="mt-7 inline-flex items-center gap-2 rounded-full bg-ruby-red px-8 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                        <i class="fa-solid fa-envelope fa-sm"></i> {{ __('faq.cta.button') }}
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-layout>
