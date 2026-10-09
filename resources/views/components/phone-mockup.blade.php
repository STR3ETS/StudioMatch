@props(['label' => '', 'image' => null])

<div {{ $attributes->merge(['class' => 'relative mx-auto w-[190px] shrink-0 sm:w-[210px] transition-transform duration-500 hover:rotate-0']) }}>
    <div class="relative rounded-[2.75rem] bg-prussian-blue p-3 shadow-2xl shadow-prussian-blue/25 ring-1 ring-white/10">
        @if ($image)
            {{-- De schermafbeeldingen zijn 9:19, precies de verhouding van dit lijstje. --}}
            <img src="{{ asset($image) }}" alt="{{ \Illuminate\Support\Str::after($label, 'Screenshot: ') }}"
                 width="420" height="887" loading="lazy"
                 class="block aspect-[9/19] w-full rounded-[2rem] bg-white object-cover object-top">
        @else
            <div class="flex aspect-[9/19] flex-col items-center justify-center gap-3 overflow-hidden rounded-[2rem] border-2 border-dashed border-prussian-blue/20 bg-white p-4 text-center">
                <i class="fa-solid fa-image text-3xl text-prussian-blue/25"></i>
                <p class="text-xs font-semibold leading-relaxed text-prussian-blue/45">{{ $label }}</p>
            </div>
        @endif
        <div class="absolute left-1/2 top-3 h-6 w-28 -translate-x-1/2 rounded-b-2xl bg-prussian-blue"></div>
    </div>
</div>
