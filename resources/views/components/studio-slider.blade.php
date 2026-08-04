@props(['title', 'studios', 'first' => false, 'last' => false])

@php
    $cardWidth = 'w-[80%] sm:w-[calc((100%_-_1rem)/2)] md:w-[calc((100%_-_2rem)/3)] lg:w-[calc((100%_-_4rem)/5)]';
@endphp

<section data-slider @class(['w-full', 'pt-16' => $first, 'pb-16' => $last, 'pb-8' => ! $last])>
    <div class="mx-auto max-w-7xl px-6">
        <div class="mb-6 flex items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-prussian-blue">{{ $title }}</h2>
            </div>
            <div class="hidden shrink-0 items-center gap-2 sm:flex">
                <button type="button" data-slider-prev aria-label="{{ __('home.studios.prev') }}"
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-prussian-blue/15 text-prussian-blue transition hover:bg-prussian-blue/5 disabled:cursor-not-allowed disabled:opacity-30">
                    <i class="fa-solid fa-chevron-left fa-sm"></i>
                </button>
                <button type="button" data-slider-next aria-label="{{ __('home.studios.next') }}"
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-prussian-blue/15 text-prussian-blue transition hover:bg-prussian-blue/5 disabled:cursor-not-allowed disabled:opacity-30">
                    <i class="fa-solid fa-chevron-right fa-sm"></i>
                </button>
            </div>
        </div>

        <div data-slider-track class="-mx-6 flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-smooth px-6 pb-2 scroll-px-6 sm:mx-0 sm:px-0 sm:scroll-px-0 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            @foreach ($studios as $studio)
                <div class="shrink-0 snap-start {{ $cardWidth }}">
                    <x-studio-card :studio="$studio" />
                </div>
            @endforeach

            <a href="{{ route('studios') }}" class="group shrink-0 snap-start {{ $cardWidth }} h-fit">
                <div class="relative aspect-[1] border border-prussian-blue/10 rounded-2xl">
                    <img src="/temp-studio-2.webp" alt="" class="absolute left-[8%] top-[21%] aspect-square w-[47%] -rotate-[8deg] rounded-xl border-2 border-white object-cover shadow-lg transition-transform duration-300 group-hover:-rotate-[14deg]">
                    <img src="/temp-studio-4.jpg" alt="" class="absolute right-[8%] top-[9%] aspect-square w-[47%] rotate-[8deg] rounded-xl border-2 border-white object-cover shadow-lg transition-transform duration-300 group-hover:rotate-[14deg]">
                    <span class="absolute inset-x-0 bottom-4 text-center text-lg font-bold text-prussian-blue">{{ __('home.studios.view_all') }}</span>
                </div>
            </a>
        </div>
    </div>
</section>
