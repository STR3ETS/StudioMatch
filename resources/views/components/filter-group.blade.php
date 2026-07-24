@props(['title', 'open' => false])

<details @class(['group border-t border-prussian-blue/10 py-4']) @if ($open) open @endif>
    <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-bold text-prussian-blue [&::-webkit-details-marker]:hidden">
        {{ $title }}
        <i class="fa-solid fa-chevron-down text-xs text-prussian-blue/40 transition group-open:rotate-180"></i>
    </summary>
    <div class="mt-4">
        {{ $slot }}
    </div>
</details>
