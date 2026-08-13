@props(['title', 'nav' => [], 'active' => null, 'langPrefix' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <meta name="theme-color" content="#ad0924">

        <title>{{ $title }} · {{ config('app.name', 'StudioMatch') }}</title>

        <link rel="icon" type="image/png" href="/logos/sm-sub-mark-logo-blauw.png">
        <link rel="apple-touch-icon" href="/logos/sm-sub-mark-logo-blauw.png">

        <link rel="preload" href="{{ asset('fontawesome/css/all.min.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
        <noscript><link rel="stylesheet" href="{{ asset('fontawesome/css/all.min.css') }}"></noscript>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            @view-transition { navigation: auto; }

            @media (prefers-reduced-motion: no-preference) {
                ::view-transition-old(dash-content) { animation: 120ms ease both dash-leave; }
                ::view-transition-new(dash-content) { animation: 220ms ease 60ms both dash-enter; }

                @keyframes dash-enter {
                    from { opacity: 0; transform: translateY(10px); }
                }

                @keyframes dash-leave {
                    to { opacity: 0; }
                }
            }

            @media (prefers-reduced-motion: reduce) {
                ::view-transition-group(*),
                ::view-transition-old(*),
                ::view-transition-new(*) { animation: none !important; }
            }
        </style>
    </head>
    <body class="min-h-screen bg-prussian-blue/[0.04]">
        <div class="lg:flex">
            <aside data-sidebar class="fixed inset-y-0 left-0 z-[1300] w-72 -translate-x-full transition-transform duration-300 lg:sticky lg:inset-y-auto lg:top-4 lg:my-4 lg:ml-4 lg:h-[calc(100vh-2rem)] lg:shrink-0 lg:translate-x-0 lg:transition-none [view-transition-name:dash-sidebar]">
                <div class="flex h-full flex-col rounded-r-3xl bg-ruby-red shadow-xl shadow-ruby-red/25 lg:rounded-3xl">
                    <div class="flex items-center justify-between px-6 pb-2 pt-6">
                        <a href="{{ route('dashboard') }}"><img src="/logos/sm-primary-logo-wit.png" alt="StudioMatch" class="h-8 w-auto"></a>
                        <button type="button" data-sidebar-close aria-label="{{ __('dashboard.confirm.cancel') }}" class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full text-white/70 transition hover:bg-white/10 hover:text-white lg:hidden">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <nav class="mt-4 flex-1 space-y-1 overflow-y-auto px-4 pb-2 [scrollbar-width:thin]">
                        @foreach ($nav as $item)
                            @if ($item['url'])
                                <a href="{{ $item['url'] }}"
                                   @class([
                                       'flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition',
                                       'bg-white text-ruby-red shadow-sm' => $active === $item['key'],
                                       'text-white/75 hover:bg-white/10 hover:text-white' => $active !== $item['key'],
                                   ])>
                                    <i class="fa-solid {{ $item['icon'] }} fa-sm w-4 text-center"></i> {{ __($langPrefix . $item['key']) }}
                                </a>
                            @else
                                <span class="flex cursor-default items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold text-white/30">
                                    <i class="fa-solid {{ $item['icon'] }} fa-sm w-4 text-center"></i> {{ __($langPrefix . $item['key']) }}
                                </span>
                            @endif
                        @endforeach
                    </nav>

                    <div class="space-y-1 border-t border-white/15 px-4 py-4">
                        <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold text-white/70 transition hover:bg-white/10 hover:text-white">
                            <i class="fa-solid fa-arrow-up-right-from-square fa-sm w-4 text-center"></i> {{ __('dashboard.nav.to_site') }}
                        </a>

                        <details data-dropdown class="group relative">
                            <summary class="flex cursor-pointer select-none list-none items-center justify-between gap-3 rounded-xl px-4 py-2 text-sm font-semibold text-white/70 transition hover:bg-white/10 hover:text-white [&::-webkit-details-marker]:hidden">
                                <span class="flex items-center gap-3">
                                    <i class="fa-solid fa-globe fa-sm w-4 text-center"></i> {{ config('localization.supported')[app()->getLocale()] ?? strtoupper(app()->getLocale()) }}
                                </span>
                                <i class="fa-solid fa-chevron-down fa-2xs text-white/40 transition group-open:rotate-180"></i>
                            </summary>
                            <div class="absolute bottom-full left-0 z-50 mb-2 flex w-full flex-col gap-1 rounded-2xl border border-prussian-blue/10 bg-white p-1.5 shadow-lg">
                                @foreach (config('localization.supported') as $code => $localeLabel)
                                    <a href="{{ route('language.switch', $code) }}"
                                       @class([
                                           'flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-sm transition',
                                           'bg-prussian-blue/5 font-semibold text-prussian-blue' => app()->getLocale() === $code,
                                           'font-medium text-prussian-blue/70 hover:bg-prussian-blue/5 hover:text-prussian-blue' => app()->getLocale() !== $code,
                                       ])>
                                        <span class="flex items-center gap-2.5">
                                            <span class="w-5 text-xs font-bold uppercase text-prussian-blue/40">{{ $code }}</span>
                                            {{ $localeLabel }}
                                        </span>
                                        @if (app()->getLocale() === $code)
                                            <i class="fa-solid fa-check fa-sm text-ruby-red"></i>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </details>

                        <div class="!mt-3 flex items-center gap-2.5 rounded-2xl bg-prussian-blue p-2.5 shadow-sm">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-sm font-bold text-ruby-red">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-white">{{ auth()->user()->firstName() }}</p>
                                <p class="truncate text-[11px] text-white/50">{{ auth()->user()->email }}</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" title="{{ __('dashboard.nav.logout') }}" class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full text-white/60 transition hover:bg-white/15 hover:text-white">
                                    <i class="fa-solid fa-arrow-right-from-bracket fa-sm"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </aside>

            <div data-sidebar-backdrop class="fixed inset-0 z-[1250] hidden bg-prussian-blue/60 backdrop-blur-sm lg:hidden"></div>

            <div class="min-w-0 flex-1">
                <header class="sticky top-0 z-[1200] border-b border-prussian-blue/10 bg-white lg:hidden [view-transition-name:dash-topbar]">
                    <div class="flex items-center justify-between px-5 py-3.5">
                        <a href="{{ route('dashboard') }}"><img src="/logos/sm-primary-logo-blauw.png" alt="StudioMatch" class="h-8 w-auto"></a>
                        <button type="button" data-sidebar-open aria-label="Menu" class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-full bg-ruby-red/10 text-ruby-red transition hover:bg-ruby-red/15">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                    </div>
                </header>

                <main class="mx-auto w-full max-w-6xl px-5 py-6 sm:px-6 lg:px-10 lg:py-10 [view-transition-name:dash-content]">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <script>
            (() => {
                const sidebar = document.querySelector('[data-sidebar]');
                const backdrop = document.querySelector('[data-sidebar-backdrop]');
                if (! sidebar || ! backdrop) return;
                const open = () => {
                    sidebar.classList.remove('-translate-x-full');
                    backdrop.classList.remove('hidden');
                };
                const close = () => {
                    sidebar.classList.add('-translate-x-full');
                    backdrop.classList.add('hidden');
                };
                document.querySelectorAll('[data-sidebar-open]').forEach((button) => button.addEventListener('click', open));
                document.querySelectorAll('[data-sidebar-close]').forEach((button) => button.addEventListener('click', close));
                backdrop.addEventListener('click', close);
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') close();
                });
            })();
        </script>

        <div data-confirm-modal class="fixed inset-0 z-[1500] hidden items-center justify-center bg-prussian-blue/50 p-6">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-ruby-red/10 text-ruby-red"><i class="fa-solid fa-triangle-exclamation"></i></span>
                <h2 class="mt-4 text-lg font-bold text-prussian-blue">{{ __('dashboard.confirm.title') }}</h2>
                <p data-confirm-message class="mt-2 text-sm leading-relaxed text-prussian-blue/60"></p>
                <div class="mt-6 grid grid-cols-2 gap-2">
                    <button type="button" data-confirm-cancel class="cursor-pointer rounded-full border border-prussian-blue/20 px-4 py-2.5 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">{{ __('dashboard.confirm.cancel') }}</button>
                    <button type="button" data-confirm-accept class="cursor-pointer rounded-full bg-ruby-red px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">{{ __('dashboard.confirm.accept') }}</button>
                </div>
            </div>
        </div>
        <script>
            (() => {
                const modal = document.querySelector('[data-confirm-modal]');
                if (! modal) return;
                let pendingForm = null;
                const close = () => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    pendingForm = null;
                };
                document.querySelectorAll('form[data-confirm]').forEach((form) => {
                    form.addEventListener('submit', (event) => {
                        if (form.dataset.confirmed === '1') return;
                        event.preventDefault();
                        pendingForm = form;
                        modal.querySelector('[data-confirm-message]').textContent = form.dataset.confirm;
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                    });
                });
                modal.querySelector('[data-confirm-accept]').addEventListener('click', () => {
                    if (! pendingForm) return;
                    pendingForm.dataset.confirmed = '1';
                    pendingForm.requestSubmit();
                });
                modal.querySelector('[data-confirm-cancel]').addEventListener('click', close);
                modal.addEventListener('click', (event) => {
                    if (event.target === modal) close();
                });
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') close();
                });
            })();
        </script>

        @if (session('status') || $errors->any())
            <div data-toast role="status" class="fixed bottom-5 right-5 z-[1400] flex w-[calc(100%-2.5rem)] max-w-sm items-start gap-3 rounded-2xl border border-prussian-blue/10 bg-white p-4 shadow-xl shadow-prussian-blue/15 transition duration-300">
                @if (session('status'))
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600"><i class="fa-solid fa-circle-check fa-sm"></i></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-prussian-blue">{{ __('dashboard.toast.success') }}</p>
                        <p class="mt-0.5 text-xs leading-relaxed text-prussian-blue/60">{{ session('status') }}</p>
                    </div>
                @else
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-ruby-red/10 text-ruby-red"><i class="fa-solid fa-circle-exclamation fa-sm"></i></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-prussian-blue">{{ __('dashboard.toast.error') }}</p>
                        <p class="mt-0.5 text-xs leading-relaxed text-prussian-blue/60">{{ __('dashboard.toast.error_text') }}</p>
                    </div>
                @endif
                <button type="button" data-toast-close aria-label="{{ __('dashboard.toast.close') }}" class="flex h-7 w-7 shrink-0 cursor-pointer items-center justify-center rounded-full text-prussian-blue/40 transition hover:bg-prussian-blue/5 hover:text-prussian-blue">
                    <i class="fa-solid fa-xmark fa-sm"></i>
                </button>
            </div>
            <script>
                (() => {
                    const toast = document.querySelector('[data-toast]');
                    if (! toast) return;
                    const hide = () => {
                        toast.classList.add('opacity-0', 'translate-y-2');
                        setTimeout(() => toast.remove(), 300);
                    };
                    toast.querySelector('[data-toast-close]').addEventListener('click', hide);
                    setTimeout(hide, 5000);
                })();
            </script>
        @endif
    </body>
</html>
