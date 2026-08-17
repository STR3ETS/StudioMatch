@php
    $money = fn (int $cents) => '€ ' . number_format($cents / 100, 2, ',', '.');
@endphp

<x-admin-layout :title="__('admin.bookings.title')" active="bookings">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-prussian-blue">{{ __('admin.bookings.title') }}</h1>
            <p class="mt-2 text-prussian-blue/60">{{ __('admin.bookings.subtitle') }}</p>
        </div>
        <a href="{{ route('admin.bookings.export') }}" class="inline-flex items-center gap-2 rounded-full border border-prussian-blue/20 px-5 py-2.5 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">
            <i class="fa-solid fa-file-export fa-sm"></i> {{ __('admin.bookings.export') }}
        </a>
    </div>

    <form method="GET" action="{{ route('admin.bookings.index') }}" class="mt-6">
        <select name="status" onchange="this.form.requestSubmit()" class="cursor-pointer rounded-xl border border-prussian-blue/15 bg-white px-3 py-2 text-sm font-medium text-prussian-blue focus:border-prussian-blue/40 focus:outline-none">
            <option value="">{{ __('admin.bookings.all_statuses') }}</option>
            @foreach (\App\Enums\BookingStatus::cases() as $case)
                <option value="{{ $case->value }}" @selected($status === $case->value)>{{ __('booking.status.' . $case->value) }}</option>
            @endforeach
        </select>
    </form>

    @if ($bookings->isEmpty())
        <p class="mt-6 rounded-xl border border-dashed border-prussian-blue/20 bg-white px-4 py-8 text-center text-sm text-prussian-blue/50">{{ __('admin.bookings.empty') }}</p>
    @else
        <div class="mt-6 space-y-3">
            @foreach ($bookings as $booking)
                <div class="flex flex-wrap items-center gap-4 rounded-2xl border border-prussian-blue/10 bg-white p-5 xl:flex-nowrap">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-bold text-prussian-blue">#{{ $booking->id }} {{ $booking->room->studio->name }} - {{ $booking->room->title }}</p>
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $booking->status->badgeClasses() }}">{{ __('booking.status.' . $booking->status->value) }}</span>
                        </div>
                        <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-prussian-blue/60">
                            <span><i class="fa-solid fa-calendar-days fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->date->translatedFormat('D j M Y') }} {{ $booking->timeRange() }}</span>
                            <span><i class="fa-solid fa-music fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->user->name }}</span>
                            <span><i class="fa-solid fa-euro-sign fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $money($booking->total_cents) }}</span>
                        </p>
                    </div>

                    <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" data-confirm="{{ __('admin.bookings.change_confirm') }}" data-confirm-accept="{{ __('admin.bookings.change_accept') }}" class="flex shrink-0 items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="filter_status" value="{{ $status }}">
                        <select name="status" class="cursor-pointer rounded-xl border border-prussian-blue/15 px-3 py-2 text-sm text-prussian-blue focus:border-prussian-blue/40 focus:outline-none">
                            @foreach (\App\Enums\BookingStatus::cases() as $case)
                                <option value="{{ $case->value }}" @selected($booking->status === $case)>{{ __('booking.status.' . $case->value) }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="cursor-pointer rounded-full border border-prussian-blue/20 px-4 py-2 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">
                            {{ __('admin.bookings.change') }}
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
</x-admin-layout>
