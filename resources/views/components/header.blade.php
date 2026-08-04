@php
    $navItems = [
        ['label' => __('nav.home'), 'url' => route('home'), 'active' => request()->routeIs('home')],
        ['label' => __('nav.studios'), 'url' => route('studios'), 'active' => request()->routeIs('studios', 'studios.*')],
        ['label' => __('nav.for_studios'), 'url' => route('hosts'), 'active' => request()->routeIs('hosts')],
        ['label' => __('nav.how_it_works'), 'url' => route('how'), 'active' => request()->routeIs('how')],
        ['label' => __('nav.faq'), 'url' => route('faq'), 'active' => request()->routeIs('faq')],
        ['label' => __('nav.contact'), 'url' => route('contact'), 'active' => request()->routeIs('contact')],
    ];
@endphp

<div data-header class="fixed z-999 w-full h-auto py-5 px-4 sm:px-6 top-0 left-0 right-0 transition-all duration-300">
    <div class="relative z-10 max-w-7xl w-full mx-auto bg-white/70 supports-[backdrop-filter]:bg-white/55 backdrop-blur-lg border border-white/50 shadow-lg shadow-prussian-blue/5 rounded-full py-3 px-4 sm:px-6 flex items-center justify-between gap-3">
        <a href="{{ route('home') }}" class="shrink-0"><img src="/logos/sm-primary-logo-blauw.png" alt="Studio Match" class="max-h-9 w-auto sm:max-h-10"></a>

        {{-- Desktop-navigatie --}}
        <ul class="hidden items-center gap-6 lg:flex xl:gap-8">
            @foreach ($navItems as $item)
                <li>
                    <a href="{{ $item['url'] }}"
                       @if ($item['active']) aria-current="page" @endif
                       @class([
                           'relative text-prussian-blue text-sm font-semibold transition after:absolute after:-bottom-1.5 after:left-0 after:h-0.5 after:w-full after:origin-left after:bg-ruby-red after:transition-transform',
                           'opacity-100 after:scale-x-100' => $item['active'],
                           'opacity-90 hover:opacity-100 after:scale-x-0 hover:after:scale-x-100' => ! $item['active'],
                       ])>{{ $item['label'] }}</a>
                </li>
            @endforeach
        </ul>

        <div class="flex items-center gap-2">
            {{-- Taal-dropdown (desktop) --}}
            <details data-dropdown class="group relative max-lg:hidden">
                <summary class="list-none [&::-webkit-details-marker]:hidden h-9 px-3 gap-2 flex items-center rounded-full text-sm font-semibold text-prussian-blue/70 hover:text-prussian-blue hover:bg-prussian-blue/5 transition cursor-pointer select-none">
                    <i class="fa-solid fa-globe fa-sm"></i>
                    <span class="uppercase">{{ app()->getLocale() }}</span>
                    <i class="fa-solid fa-chevron-down fa-2xs text-prussian-blue/40 transition group-open:rotate-180"></i>
                </summary>
                <div class="absolute right-0 z-50 mt-2 flex w-44 flex-col gap-1 rounded-2xl border border-prussian-blue/10 bg-white p-1.5 shadow-lg">
                    @foreach (config('localization.supported') as $code => $label)
                        <a href="{{ route('language.switch', $code) }}"
                           @class([
                               'flex items-center justify-between gap-3 px-3 py-2 rounded-xl text-sm transition',
                               'bg-prussian-blue/5 text-prussian-blue font-semibold' => app()->getLocale() === $code,
                               'text-prussian-blue/70 font-medium hover:bg-prussian-blue/5 hover:text-prussian-blue' => app()->getLocale() !== $code,
                           ])>
                            <span class="flex items-center gap-2.5">
                                <span class="w-5 text-xs font-bold uppercase text-prussian-blue/40">{{ $code }}</span>
                                {{ $label }}
                            </span>
                            @if (app()->getLocale() === $code)
                                <i class="fa-solid fa-check fa-sm text-ruby-red"></i>
                            @endif
                        </a>
                    @endforeach
                </div>
            </details>

            {{-- Account --}}
            @auth
                <a href="{{ route('dashboard') }}" class="flex h-9 items-center gap-2 rounded-full bg-ruby-red px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                    <i class="fa-solid fa-gauge-high fa-sm"></i> {{ __('nav.dashboard') }}
                </a>
            @else
                <a href="{{ route('login') }}" class="flex h-9 items-center gap-2 rounded-full bg-ruby-red px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                    <i class="fa-solid fa-user fa-sm"></i> {{ __('nav.account') }}
                </a>
            @endauth

            {{-- Hamburger (mobiel) --}}
            <button type="button" data-menu-toggle aria-expanded="false" aria-label="Menu"
                    class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full text-prussian-blue transition hover:bg-prussian-blue/5 lg:hidden">
                <i class="fa-solid fa-bars" data-menu-icon></i>
            </button>
        </div>
    </div>

    {{-- Mobiel menu (fullscreen) --}}
    <div data-mobile-menu class="fixed inset-0 hidden overflow-y-auto bg-white/95 supports-[backdrop-filter]:bg-white/85 backdrop-blur-xl lg:hidden">
        <div class="flex min-h-full flex-col px-6 pt-28 pb-10 sm:px-8">
            <ul class="flex flex-col gap-1">
                @foreach ($navItems as $item)
                    <li>
                        <a href="{{ $item['url'] }}"
                           @if ($item['active']) aria-current="page" @endif
                           @class([
                               'flex items-center justify-between rounded-2xl px-4 py-4 text-2xl font-bold transition',
                               'bg-prussian-blue/5 text-prussian-blue' => $item['active'],
                               'text-prussian-blue/60 hover:bg-prussian-blue/5 hover:text-prussian-blue' => ! $item['active'],
                           ])>
                            {{ $item['label'] }}
                            @if ($item['active'])
                                <i class="fa-solid fa-circle text-[8px] text-ruby-red"></i>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="mt-auto flex items-center gap-2 border-t border-prussian-blue/10 px-4 pt-6">
                <i class="fa-solid fa-globe text-prussian-blue/40"></i>
                @foreach (config('localization.supported') as $code => $label)
                    <a href="{{ route('language.switch', $code) }}"
                       @class([
                           'rounded-full px-4 py-2 text-sm font-bold uppercase transition',
                           'bg-prussian-blue/10 text-prussian-blue' => app()->getLocale() === $code,
                           'text-prussian-blue/50 hover:text-prussian-blue' => app()->getLocale() !== $code,
                       ])>{{ $code }}</a>
                @endforeach
            </div>
        </div>
    </div>
</div>
