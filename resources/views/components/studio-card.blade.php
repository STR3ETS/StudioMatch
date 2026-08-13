@props(['studio'])

@php
    $photos = $studio['photos'] ?? [$studio['photo'] ?? '/temp-studio-1.webp'];
@endphp

<a href="{{ $studio['url'] }}" class="group block w-full">
    <div data-carousel class="relative aspect-[1] overflow-hidden rounded-2xl bg-prussian-blue/5">
        <div data-carousel-track class="flex h-full snap-x snap-mandatory overflow-x-auto scroll-smooth [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            @foreach ($photos as $photo)
                {{-- Eigen wrapper met overflow-hidden, zodat de hoverzoom niet de buurfoto in beeld duwt --}}
                <div class="h-full w-full shrink-0 snap-start overflow-hidden">
                    <img src="{{ $photo }}" alt="{{ $studio['name'] }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" @if (! $loop->first) loading="lazy" @endif>
                </div>
            @endforeach
        </div>
        <span class="absolute left-2 top-2 rounded-full bg-white/90 px-2 py-0.5 text-[10px] font-semibold text-prussian-blue">{{ $studio['type'] }}</span>

        @if (count($photos) > 1)
            <button type="button" data-carousel-prev aria-label="{{ __('home.studios.prev') }}"
                    class="absolute left-2 top-1/2 hidden h-8 w-8 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full bg-white/90 text-prussian-blue opacity-0 shadow transition hover:bg-white group-hover:opacity-100 sm:flex">
                <i class="fa-solid fa-chevron-left text-xs"></i>
            </button>
            <button type="button" data-carousel-next aria-label="{{ __('home.studios.next') }}"
                    class="absolute right-2 top-1/2 hidden h-8 w-8 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full bg-white/90 text-prussian-blue opacity-0 shadow transition hover:bg-white group-hover:opacity-100 sm:flex">
                <i class="fa-solid fa-chevron-right text-xs"></i>
            </button>
            <div class="pointer-events-none absolute inset-x-0 bottom-2 flex justify-center gap-1">
                @foreach ($photos as $photo)
                    <span data-carousel-dot @class(['h-1.5 w-1.5 rounded-full shadow transition', 'bg-white' => $loop->first, 'bg-white/50' => ! $loop->first])></span>
                @endforeach
            </div>
        @endif
    </div>
    <div class="mt-2">
        <div class="flex items-center justify-between gap-2">
            <h3 class="truncate text-sm font-semibold text-prussian-blue transition-colors group-hover:text-ruby-red">{{ $studio['name'] }}</h3>
            @isset($studio['rating'])
                <span class="flex shrink-0 items-center gap-1 text-xs text-prussian-blue">
                    <i class="fa-solid fa-star text-[10px] text-ruby-red"></i>
                    <span class="font-semibold">{{ $studio['rating'] }}</span>
                    <span class="text-prussian-blue/40">({{ $studio['reviews'] }})</span>
                </span>
            @endisset
        </div>
        <p class="truncate text-xs text-prussian-blue/50">{{ $studio['city'] }}@isset($studio['distance']) ({{ $studio['distance'] }} km)@endisset</p>
        <p class="mt-0.5 text-sm font-semibold text-prussian-blue">&euro;{{ $studio['price'] }} <span class="font-normal text-prussian-blue/50">{{ __('home.studios.per_hour') }}</span></p>
    </div>
</a>
