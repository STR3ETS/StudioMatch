@php
    // De teksten worden gegenereerd uit de aangeleverde Word-documenten, zie het
    // commando php artisan legal:import. Staat er nog geen document, dan blijft de
    // pagina bestaan met de melding dat de tekst volgt.
    $partial = 'legal.partials.' . $key;
    $document = config('legal.' . $key);
    $hasText = view()->exists($partial);
@endphp

<x-layout :title="$title" :description="$hasText ? __('legal.meta_description', ['title' => $title]) : null">
    <x-hero compact>
        <h1 class="text-4xl font-bold text-white sm:text-5xl">{{ $title }}</h1>

        @if ($document['updated'] ?? null)
            <p class="mt-4 text-sm text-white/60">{{ __('legal.updated', ['date' => $document['updated']]) }}</p>
        @endif
    </x-hero>

    <div class="py-16">
        <div class="mx-auto max-w-3xl px-6">
            @if ($hasText)
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-prussian-blue/10 pb-6">
                    <p class="text-sm text-prussian-blue/50">
                        @if (app()->getLocale() !== 'nl')
                            <i class="fa-solid fa-circle-info fa-sm"></i> {{ __('legal.dutch_only') }}
                        @endif
                    </p>

                    @if ($document['pdf'] ?? null)
                        <a href="{{ asset($document['pdf']) }}" target="_blank" rel="noopener"
                           class="inline-flex shrink-0 items-center gap-2 rounded-full border border-prussian-blue/20 px-5 py-2.5 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">
                            <i class="fa-solid fa-file-pdf fa-sm"></i> {{ __('legal.download') }}
                        </a>
                    @endif
                </div>

                <div class="sm-prose sm-legal mt-8">
                    @include($partial)
                </div>
            @else
                <div class="flex flex-col items-center rounded-2xl border border-dashed border-prussian-blue/20 bg-white px-6 py-16 text-center">
                    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-prussian-blue/5 text-xl text-prussian-blue/40"><i class="fa-solid fa-file-contract"></i></span>
                    <p class="mt-4 font-bold text-prussian-blue">{{ __('legal.pending_title') }}</p>
                    <p class="mt-1 max-w-md text-sm leading-relaxed text-prussian-blue/60">{{ __('legal.pending_text') }}</p>
                </div>
            @endif
        </div>
    </div>
</x-layout>
