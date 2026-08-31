@props(['current' => 1])

@php
    $steps = [
        1 => __('auth.steps.account'),
        2 => __('auth.steps.verify'),
        3 => __('auth.steps.profile'),
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-prussian-blue/10 bg-prussian-blue/[0.03] px-4 py-3.5']) }}>
    <span class="block text-[10px] font-bold uppercase tracking-wide text-prussian-blue/40">{{ __('auth.steps.label') }}</span>
    <ol class="mt-2.5 flex items-start gap-1">
        @foreach ($steps as $number => $title)
            @php
                $done = $number < $current;
                $active = $number === $current;
            @endphp
            <li class="flex flex-1 flex-col items-center gap-1.5 text-center">
                <div class="flex w-full items-center gap-1">
                    <span class="h-0.5 flex-1 rounded-full {{ $number === 1 ? 'bg-transparent' : ($done || $active ? 'bg-ruby-red' : 'bg-prussian-blue/10') }}"></span>
                    <span @class([
                        'flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold',
                        'bg-ruby-red text-white' => $done || $active,
                        'bg-prussian-blue/10 text-prussian-blue/40' => ! $done && ! $active,
                    ])>
                        @if ($done)
                            <i class="fa-solid fa-check fa-2xs"></i>
                        @else
                            {{ $number }}
                        @endif
                    </span>
                    <span class="h-0.5 flex-1 rounded-full {{ $number === count($steps) ? 'bg-transparent' : ($done ? 'bg-ruby-red' : 'bg-prussian-blue/10') }}"></span>
                </div>
                <span @class([
                    'text-[11px] leading-tight',
                    'font-bold text-prussian-blue' => $active,
                    'font-semibold text-prussian-blue/60' => $done,
                    'text-prussian-blue/40' => ! $done && ! $active,
                ])>{{ $title }}</span>
            </li>
        @endforeach
    </ol>
</div>
