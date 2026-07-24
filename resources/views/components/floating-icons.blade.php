@php
    $floatIcons = [
        ['icon' => 'fa-music', 'left' => 5, 'size' => 1.6, 'duration' => 13, 'delay' => 7],
        ['icon' => 'fa-microphone', 'left' => 15, 'size' => 2.2, 'duration' => 16, 'delay' => 3],
        ['icon' => 'fa-headphones', 'left' => 26, 'size' => 1.8, 'duration' => 11, 'delay' => 8],
        ['icon' => 'fa-compact-disc', 'left' => 36, 'size' => 2.6, 'duration' => 18, 'delay' => 5],
        ['icon' => 'fa-sliders', 'left' => 47, 'size' => 1.5, 'duration' => 12, 'delay' => 10],
        ['icon' => 'fa-guitar', 'left' => 57, 'size' => 2.0, 'duration' => 15, 'delay' => 2],
        ['icon' => 'fa-wave-square', 'left' => 67, 'size' => 1.7, 'duration' => 10, 'delay' => 6],
        ['icon' => 'fa-record-vinyl', 'left' => 76, 'size' => 2.4, 'duration' => 17, 'delay' => 12],
        ['icon' => 'fa-drum', 'left' => 86, 'size' => 1.6, 'duration' => 13, 'delay' => 4],
        ['icon' => 'fa-microphone-lines', 'left' => 93, 'size' => 2.0, 'duration' => 14, 'delay' => 9],
        ['icon' => 'fa-music', 'left' => 32, 'size' => 1.4, 'duration' => 19, 'delay' => 14],
        ['icon' => 'fa-headphones', 'left' => 62, 'size' => 1.9, 'duration' => 16, 'delay' => 6],
    ];
@endphp

<div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
    @foreach ($floatIcons as $icon)
        <i class="fa-solid {{ $icon['icon'] }} hero-float text-white/10"
           style="left: {{ $icon['left'] }}%; font-size: {{ $icon['size'] }}rem; animation-duration: {{ $icon['duration'] }}s; animation-delay: -{{ $icon['delay'] }}s;"></i>
    @endforeach
</div>
