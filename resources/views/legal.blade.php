<x-layout :title="$title">
    <x-hero compact>
        <h1 class="text-4xl font-bold text-white sm:text-5xl">{{ $title }}</h1>
    </x-hero>

    <div class="py-16">
        <div class="mx-auto max-w-3xl px-6">
            {{-- Placeholder totdat de definitieve juridische teksten zijn aangeleverd (scope §6) --}}
            <div class="flex flex-col items-center rounded-2xl border border-dashed border-prussian-blue/20 bg-white px-6 py-16 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-prussian-blue/5 text-xl text-prussian-blue/40"><i class="fa-solid fa-file-contract"></i></span>
                <p class="mt-4 font-bold text-prussian-blue">{{ __('legal.pending_title') }}</p>
                <p class="mt-1 max-w-md text-sm leading-relaxed text-prussian-blue/60">{{ __('legal.pending_text') }}</p>
            </div>
        </div>
    </div>
</x-layout>
