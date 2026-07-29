@props(['label' => ''])

{{-- iPhone-frame met een placeholder die aangeeft welke screenshot hier moet komen. --}}
<div {{ $attributes->merge(['class' => 'relative mx-auto w-[190px] shrink-0 sm:w-[210px] transition-transform duration-500 hover:rotate-0']) }}>
    <div class="relative rounded-[2.75rem] bg-prussian-blue p-3 shadow-2xl shadow-prussian-blue/25 ring-1 ring-white/10">
        <div class="flex aspect-[9/19] flex-col items-center justify-center gap-3 overflow-hidden rounded-[2rem] border-2 border-dashed border-prussian-blue/20 bg-white p-4 text-center">
            <i class="fa-solid fa-image text-3xl text-prussian-blue/25"></i>
            <p class="text-xs font-semibold leading-relaxed text-prussian-blue/45">{{ $label }}</p>
        </div>
        {{-- Notch: op het frame gelegd, over de schermrand heen, zodat er geen naad zichtbaar is. --}}
        <div class="absolute left-1/2 top-3 h-6 w-28 -translate-x-1/2 rounded-b-2xl bg-prussian-blue"></div>
    </div>
</div>
