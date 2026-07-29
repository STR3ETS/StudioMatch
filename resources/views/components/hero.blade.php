@props(['compact' => false])

<div @class([
    'relative w-full overflow-hidden bg-ruby-red',
    'pt-38 pb-20' => ! $compact,
    'pt-37 pb-20' => $compact,
])>
    <x-floating-icons />

    <div data-reveal class="relative z-10 mx-auto max-w-7xl px-6 text-center">
        {{ $slot }}
    </div>
</div>
