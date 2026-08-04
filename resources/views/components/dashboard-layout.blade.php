@props(['title'])
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
    </head>
    <body class="min-h-screen bg-prussian-blue/[0.04]">
        <header class="border-b border-prussian-blue/10 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-6 py-4">
                <a href="{{ route('dashboard') }}"><img src="/logos/sm-primary-logo-blauw.png" alt="StudioMatch" class="h-9 w-auto"></a>

                <div class="flex items-center gap-1.5 sm:gap-2">
                    <a href="{{ route('home') }}" class="flex h-9 items-center gap-2 rounded-full px-3 text-sm font-semibold text-prussian-blue/60 transition hover:bg-prussian-blue/5 hover:text-prussian-blue">
                        <i class="fa-solid fa-arrow-up-right-from-square fa-xs"></i>
                        <span class="max-sm:hidden">{{ __('dashboard.nav.to_site') }}</span>
                    </a>

                    {{-- Taal-dropdown --}}
                    <details data-dropdown class="group relative">
                        <summary class="list-none [&::-webkit-details-marker]:hidden flex h-9 cursor-pointer select-none items-center gap-2 rounded-full px-3 text-sm font-semibold text-prussian-blue/60 transition hover:bg-prussian-blue/5 hover:text-prussian-blue">
                            <i class="fa-solid fa-globe fa-sm"></i>
                            <span class="uppercase">{{ app()->getLocale() }}</span>
                            <i class="fa-solid fa-chevron-down fa-2xs text-prussian-blue/40 transition group-open:rotate-180"></i>
                        </summary>
                        <div class="absolute right-0 z-50 mt-2 flex w-44 flex-col gap-1 rounded-2xl border border-prussian-blue/10 bg-white p-1.5 shadow-lg">
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

                    <div class="flex items-center gap-2 rounded-full bg-prussian-blue/5 py-1 pl-1 pr-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-prussian-blue text-xs font-bold text-white">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="max-w-32 truncate text-sm font-semibold text-prussian-blue max-sm:hidden">{{ auth()->user()->firstName() }}</span>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="{{ __('dashboard.nav.logout') }}" class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full text-prussian-blue/60 transition hover:bg-ruby-red/10 hover:text-ruby-red">
                            <i class="fa-solid fa-arrow-right-from-bracket fa-sm"></i>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl px-6 py-10">
            {{ $slot }}
        </main>

        {{-- Bevestigingsmodal voor formulieren met data-confirm="..." --}}
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

        {{-- Toastmelding voor succes (session status) en validatiefouten --}}
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
