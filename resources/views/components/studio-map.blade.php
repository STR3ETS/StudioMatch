@props(['studios'])

{{-- Interactieve studio-kaart; de bijbehorende logica staat in app.js (#studio-map). --}}
<div id="studio-map"
     data-studios='@json($studios)'
     data-per-hour="{{ __('home.studios.per_hour') }}"
     {{ $attributes->merge(['class' => 'relative z-0 w-full overflow-hidden rounded-2xl']) }}></div>
