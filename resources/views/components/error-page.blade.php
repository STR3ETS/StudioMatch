@props(['code', 'heading', 'text'])

{{--
    Foutpagina binnen de gewone site, zodat de bezoeker gewoon verder kan klikken.
    Alleen voor 4xx: daarbij werkt de applicatie zelf nog, dus header en footer zijn veilig
    te tonen. Voor 5xx staat er een losse variant in x-error-bare.
--}}
<x-layout :title="$heading" :description="$text" robots="noindex, nofollow">
    <section class="relative flex flex-1 items-center overflow-hidden bg-ruby-red pt-37 pb-20">
        <x-floating-icons />

        <div data-reveal class="relative z-10 mx-auto w-full max-w-7xl px-6 text-center">
            <p class="text-outline-white select-none text-[5.5rem] font-black leading-none sm:text-[9rem]">{{ $code }}</p>

            <h1 class="mt-2 text-3xl font-bold text-white sm:text-4xl">{{ $heading }}</h1>
            <p class="mx-auto mt-4 max-w-xl text-white/60">{{ $text }}</p>

            <div class="mt-9 flex flex-wrap items-center justify-center gap-3">
                @inapp
                    {{-- In de app: terug naar de app zelf, niet naar de website (de app heeft eigen schermen). --}}
                    <a href="{{ route('app.back') }}" class="inline-flex min-h-11 items-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-semibold text-ruby-red shadow-sm transition hover:bg-white/90">
                        <i class="fa-solid fa-arrow-left fa-sm"></i> {{ __('app.back_to_app') }}
                    </a>
                    @if (auth()->user()?->isArtist())
                        <a href="{{ route('app.back', ['scherm' => 'studios']) }}" class="inline-flex min-h-11 items-center gap-2 rounded-full border border-white/25 px-6 py-3 text-sm font-semibold text-white/85 transition hover:bg-white/10 hover:text-white">
                            <i class="fa-solid fa-magnifying-glass fa-sm"></i> {{ __('errors.studios') }}
                        </a>
                    @endif
                @elseif (trim($slot) === '')
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-semibold text-ruby-red shadow-sm transition hover:bg-white/90">
                        <i class="fa-solid fa-house fa-sm"></i> {{ __('errors.home') }}
                    </a>
                    <a href="{{ route('studios') }}" class="inline-flex items-center gap-2 rounded-full border border-white/25 px-6 py-3 text-sm font-semibold text-white/85 transition hover:bg-white/10 hover:text-white">
                        <i class="fa-solid fa-magnifying-glass fa-sm"></i> {{ __('errors.studios') }}
                    </a>
                @else
                    {{ $slot }}
                @endinapp
            </div>
        </div>
    </section>
</x-layout>
