@php
    $money = fn (int $cents) => '€ ' . number_format($cents / 100, 2, ',', '.');
@endphp

<x-admin-layout :title="__('admin.tickets.title')" active="tickets">
    <h1 class="text-2xl font-bold text-prussian-blue">{{ __('admin.tickets.title') }}</h1>
    <p class="mt-2 text-prussian-blue/60">{{ __('admin.tickets.subtitle') }}</p>

    @if ($tickets->isEmpty())
        <div class="mt-8 flex flex-col items-center rounded-2xl border border-dashed border-prussian-blue/20 bg-white px-6 py-14 text-center">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-500/10 text-xl text-emerald-600"><i class="fa-solid fa-circle-check"></i></span>
            <p class="mt-4 font-bold text-prussian-blue">{{ __('admin.tickets.empty_title') }}</p>
            <p class="mt-1 max-w-sm text-sm text-prussian-blue/60">{{ __('admin.tickets.empty_text') }}</p>
        </div>
    @else
        <div class="mt-8 space-y-4">
            @foreach ($tickets as $booking)
                <div class="rounded-2xl border border-ruby-red/30 bg-white p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-bold text-prussian-blue">{{ $booking->room->studio->name }} - {{ $booking->room->title }}</h2>
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $booking->status->badgeClasses() }}">{{ __('booking.status.' . $booking->status->value) }}</span>
                            </div>
                            <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-prussian-blue/60">
                                <span><i class="fa-solid fa-calendar-days fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->date->translatedFormat('D j M Y') }} {{ $booking->timeRange() }}</span>
                                <span><i class="fa-solid fa-music fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->user->name }} ({{ $booking->user->email }})</span>
                                <span><i class="fa-solid fa-building fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->room->studio->user->name }} ({{ $booking->room->studio->user->email }})</span>
                                <span><i class="fa-solid fa-euro-sign fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $money($booking->total_cents) }}</span>
                                <span><i class="fa-solid fa-clock fa-xs mr-1.5 text-prussian-blue/40"></i>{{ __('admin.tickets.reported', ['date' => $booking->disputed_at->translatedFormat('j M Y H:i')]) }}</span>
                            </p>
                            <p class="mt-3 rounded-xl bg-prussian-blue/[0.03] px-4 py-3 text-sm leading-relaxed text-prussian-blue/80">
                                <span class="font-semibold">{{ __('admin.tickets.reason') }}:</span> {{ $booking->dispute_reason }}
                            </p>

                            @if ($booking->dispute_photos)
                                <div class="mt-3">
                                    <p class="text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('admin.tickets.photos') }}</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach ($booking->dispute_photos as $photo)
                                            <a href="{{ Storage::disk('public')->url($photo) }}" target="_blank" rel="noopener" class="group">
                                                <img src="{{ Storage::disk('public')->url($photo) }}" alt="" class="h-20 w-28 rounded-xl border border-prussian-blue/10 object-cover transition group-hover:brightness-90">
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <form method="POST" action="{{ route('admin.tickets.release', $booking) }}" data-confirm="{{ __('admin.tickets.release_confirm') }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="cursor-pointer rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                <i class="fa-solid fa-check fa-sm mr-1"></i>{{ __('admin.tickets.release_button') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.tickets.cancel', $booking) }}" data-confirm="{{ __('admin.tickets.cancel_confirm') }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="cursor-pointer rounded-full border border-prussian-blue/20 px-5 py-2.5 text-sm font-semibold text-prussian-blue transition hover:bg-ruby-red/10 hover:text-ruby-red">
                                {{ __('admin.tickets.cancel_button') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-admin-layout>
