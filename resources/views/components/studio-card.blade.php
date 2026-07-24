@props(['studio'])

<a href="{{ route('studios.show', str($studio['name'])->slug()) }}" class="group block w-full">
    <div class="relative aspect-[1] overflow-hidden rounded-2xl bg-prussian-blue/5">
        <img src="{{ $studio['photo'] ?? '/temp-studio-1.webp' }}" alt="{{ $studio['name'] }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
        <span class="absolute left-2 top-2 rounded-full bg-white/90 px-2 py-0.5 text-[10px] font-semibold text-prussian-blue">{{ $studio['type'] }}</span>
    </div>
    <div class="mt-2">
        <div class="flex items-center justify-between gap-2">
            <h3 class="truncate text-sm font-semibold text-prussian-blue">{{ $studio['name'] }}</h3>
            <span class="flex shrink-0 items-center gap-1 text-xs text-prussian-blue">
                <i class="fa-solid fa-star text-[10px] text-ruby-red"></i>
                <span class="font-semibold">{{ $studio['rating'] }}</span>
                <span class="text-prussian-blue/40">({{ $studio['reviews'] }})</span>
            </span>
        </div>
        <p class="truncate text-xs text-prussian-blue/50">{{ $studio['city'] }}</p>
        <p class="mt-0.5 text-sm font-semibold text-prussian-blue">&euro;{{ $studio['price'] }} <span class="font-normal text-prussian-blue/50">{{ __('home.studios.per_hour') }}</span></p>
    </div>
</a>
