@props(['studios'])

<div id="studio-map"
     data-studios='@json($studios)'
     data-per-hour="{{ __('home.studios.per_hour') }}"
     data-tiles="{{ config('services.carto.tiles') }}"
     data-tile-key="{{ config('services.carto.key') }}"
     {{ $attributes->merge(['class' => 'relative z-0 w-full overflow-hidden rounded-2xl']) }}></div>
