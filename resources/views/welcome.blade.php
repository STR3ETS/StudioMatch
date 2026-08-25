<x-layout :title="__('home.meta_title')" :description="__('home.meta_description')">
    <x-hero>
        <p class="text-sm font-semibold uppercase tracking-wider text-white/60">{{ __('home.tagline') }}</p>
        <h1 class="mt-3 text-4xl sm:text-5xl font-bold text-white">{{ __('home.heading') }}</h1>

        <button type="button" data-search-open class="mt-10 flex w-full cursor-pointer items-center gap-4 rounded-full bg-white p-2 pl-6 text-left shadow-xl sm:hidden">
            <span class="flex-1">
                <span class="block text-sm font-bold text-prussian-blue">{{ __('home.search.mobile_placeholder') }}</span>
                <span class="block text-xs text-prussian-blue/50">{{ __('home.search.mobile_hint') }}</span>
            </span>
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-ruby-red text-white"><i class="fa-solid fa-magnifying-glass"></i></span>
        </button>

        <form action="{{ route('studios') }}" class="mt-10 max-w-3xl mx-auto max-sm:hidden flex items-center rounded-full bg-white shadow-xl">
            <label class="flex-1 cursor-text rounded-2xl px-10 py-4 text-left transition hover:bg-prussian-blue/5">
                <span class="block text-xs font-bold text-prussian-blue">{{ __('home.search.where') }}</span>
                <input type="text" name="location" placeholder="{{ __('home.search.where_placeholder') }}" class="w-full bg-transparent text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:outline-none">
            </label>

            <span class="hidden sm:block h-8 w-px bg-prussian-blue/10"></span>

            <div class="relative flex-1" data-datepicker data-min="{{ today()->toDateString() }}">
                <input type="hidden" name="date">
                <button type="button" data-datepicker-toggle class="w-full cursor-pointer rounded-2xl px-10 py-4 text-left transition hover:bg-prussian-blue/5">
                    <span class="block text-xs font-bold text-prussian-blue">{{ __('home.search.when') }}</span>
                    <span data-datepicker-label class="block text-sm text-prussian-blue/40">{{ __('home.search.when_placeholder') }}</span>
                </button>
                <div data-datepicker-panel class="absolute left-1/2 top-full z-50 mt-3 hidden w-72 -translate-x-1/2 rounded-2xl border border-prussian-blue/10 bg-white p-4 text-left shadow-xl"></div>
            </div>

            <span class="hidden sm:block h-8 w-px bg-prussian-blue/10"></span>

            <label class="flex-1 cursor-pointer rounded-2xl px-10 py-4 text-left transition hover:bg-prussian-blue/5">
                <span class="block text-xs font-bold text-prussian-blue">{{ __('home.search.type') }}</span>
                <select name="type" class="w-full cursor-pointer appearance-none bg-transparent text-sm text-prussian-blue focus:outline-none">
                    <option value="">{{ __('home.search.all_types') }}</option>
                    @foreach (['recording', 'mix', 'master'] as $value)
                        <option value="{{ $value }}">{{ __('studios.type.' . $value) }}</option>
                    @endforeach
                </select>
            </label>

            <button type="submit" aria-label="{{ __('home.search.submit') }}" class="flex h-12 shrink-0 items-center justify-center gap-2 rounded-full bg-ruby-red font-semibold text-white transition hover:bg-ruby-red/90 sm:w-12 mx-2 mb-2 sm:my-0 sm:mr-4 sm:ml-4">
                <i class="fa-solid fa-magnifying-glass"></i>
                <span class="sm:hidden">{{ __('home.search.submit') }}</span>
            </button>
        </form>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('studios') }}" class="rounded-full bg-white px-6 py-3 text-sm font-semibold text-ruby-red shadow-sm transition hover:bg-white/90">
                <i class="fa-solid fa-magnifying-glass fa-sm mr-1.5"></i>{{ __('home.cta_book') }}
            </a>
            <a href="{{ route('hosts') }}" class="rounded-full border border-white/30 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                <i class="fa-solid fa-door-open fa-sm mr-1.5"></i>{{ __('home.cta_host') }}
            </a>
        </div>
    </x-hero>

    <div data-search-modal class="fixed inset-0 z-[1200] hidden bg-white sm:hidden">
        <div class="flex h-full flex-col">
            <div class="flex items-center justify-between border-b border-prussian-blue/10 px-6 py-4">
                <h2 class="text-lg font-bold text-prussian-blue">{{ __('home.search.mobile_placeholder') }}</h2>
                <button type="button" data-search-close aria-label="{{ __('home.search.close') }}" class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-full text-prussian-blue transition hover:bg-prussian-blue/5">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('studios') }}" class="flex flex-1 flex-col gap-6 overflow-y-auto px-6 py-6">
                <div>
                    <label for="m-search-location" class="block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('home.search.where') }}</label>
                    <div class="relative mt-2">
                        <i class="fa-solid fa-location-dot absolute left-4 top-1/2 -translate-y-1/2 text-sm text-prussian-blue/40"></i>
                        <input id="m-search-location" type="text" name="location" placeholder="{{ __('home.search.where_placeholder') }}" class="w-full rounded-2xl border border-prussian-blue/15 py-3.5 pl-11 pr-4 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none">
                    </div>
                </div>

                <div>
                    <span class="block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('home.search.when') }}</span>
                    <div class="mt-2 flex gap-2">
                        <div class="relative w-full min-w-0" data-datepicker data-min="{{ today()->toDateString() }}">
                            <input type="hidden" name="date">
                            <button type="button" data-datepicker-toggle class="w-full cursor-pointer rounded-2xl border border-prussian-blue/15 px-4 py-3.5 text-left text-sm text-prussian-blue focus:border-prussian-blue/40 focus:outline-none">
                                <span data-datepicker-label class="text-prussian-blue/40">{{ __('home.search.when_placeholder') }}</span>
                            </button>
                            <div data-datepicker-panel class="absolute left-0 top-full z-50 mt-2 hidden w-72 rounded-2xl border border-prussian-blue/10 bg-white p-4 shadow-xl"></div>
                        </div>
                        <select name="start" class="w-full min-w-0 cursor-pointer rounded-2xl border border-prussian-blue/15 px-4 py-3.5 text-sm text-prussian-blue focus:border-prussian-blue/40 focus:outline-none">
                            <option value="">--:--</option>
                            @for ($h = 0; $h <= 23; $h++)
                                <option value="{{ $h }}">{{ sprintf('%02d:00', $h) }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div>
                    <span class="block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('home.search.type') }}</span>
                    <div class="mt-2 flex gap-2">
                        @foreach (['recording', 'mix', 'master'] as $value)
                            <label class="flex-1">
                                <input type="radio" name="type" value="{{ $value }}" class="peer sr-only">
                                <span class="flex cursor-pointer items-center justify-center rounded-2xl border border-prussian-blue/15 px-4 py-3.5 text-sm font-semibold text-prussian-blue/70 transition peer-checked:border-ruby-red peer-checked:bg-ruby-red/5 peer-checked:text-prussian-blue">{{ __('studios.type.' . $value) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="mt-auto flex cursor-pointer items-center justify-center gap-2 rounded-full bg-ruby-red py-3.5 text-sm font-semibold text-white transition hover:bg-ruby-red/90">
                    <i class="fa-solid fa-magnifying-glass fa-sm"></i> {{ __('home.search.submit') }}
                </button>
            </form>
        </div>
    </div>

    @if ($featured->isNotEmpty())
        <x-studio-slider :title="__('home.studios.title')" :studios="$featured" :first="true" />
    @endif

    <section class="py-16">
        <div class="mx-auto max-w-7xl px-6">
            <div class="max-w-2xl">
                <h2 class="text-3xl font-bold text-prussian-blue">{{ __('home.why.title') }}</h2>
                <p class="mt-3 text-prussian-blue/60">{{ __('home.why.subtitle') }}</p>
            </div>
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach (__('home.why.items') as $item)
                    <div @class([
                        'rounded-2xl p-6 transition hover:-translate-y-1',
                        'bg-prussian-blue text-white shadow-xl shadow-prussian-blue/20' => $loop->first,
                        'border border-prussian-blue/10 hover:shadow-lg hover:shadow-prussian-blue/5' => ! $loop->first,
                    ])>
                        <span @class([
                            'flex h-11 w-11 items-center justify-center rounded-xl',
                            'bg-white/10 text-white' => $loop->first,
                            'bg-ruby-red/10 text-ruby-red' => ! $loop->first,
                        ])><i class="fa-solid {{ $item['icon'] }}"></i></span>
                        <h3 @class(['mt-4 font-bold', 'text-white' => $loop->first, 'text-prussian-blue' => ! $loop->first])>{{ $item['title'] }}</h3>
                        <p @class(['mt-2 text-sm leading-relaxed', 'text-white/60' => $loop->first, 'text-prussian-blue/60' => ! $loop->first])>{{ $item['text'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-10 flex flex-wrap items-center gap-3">
                <a href="{{ route('studios') }}" class="rounded-full bg-ruby-red px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                    <i class="fa-solid fa-magnifying-glass fa-sm mr-1.5"></i>{{ __('home.cta_book') }}
                </a>
                <a href="{{ route('how') }}" class="rounded-full border border-prussian-blue/20 px-6 py-3 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">
                    {{ __('nav.how_it_works') }} <i class="fa-solid fa-arrow-right fa-xs ml-1"></i>
                </a>
            </div>
        </div>
    </section>

    @if (count($mapStudios) > 0)
        <section class="py-16">
            <div class="mx-auto max-w-7xl px-6">
                <x-studio-map :studios="$mapStudios" class="aspect-[2/1] max-sm:aspect-square rounded-[2.5rem] border border-white/10" />
            </div>
        </section>
    @endif

    <section class="py-16">
        <div class="mx-auto max-w-7xl px-6">
            <div class="relative overflow-hidden rounded-[2.5rem] bg-prussian-blue px-8 py-14 text-center lg:px-14">
                <x-floating-icons />
                <div class="relative z-10">
                    <h2 class="text-3xl font-bold text-white sm:text-4xl">{{ __('home.host_cta.title') }}</h2>
                    <p class="mx-auto mt-3 max-w-xl text-white/60">{{ __('home.host_cta.text') }}</p>
                    <a href="{{ route('hosts') }}" class="mt-7 inline-flex items-center gap-2 rounded-full bg-ruby-red px-8 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                        <i class="fa-solid fa-door-open fa-sm"></i> {{ __('home.host_cta.button') }}
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-layout>
