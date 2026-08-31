@php
    use App\Enums\BookingStatus;

    $money = fn (int $cents) => '€ ' . number_format($cents / 100, 2, ',', '.');
@endphp

<x-artist-layout :title="__('dashboard.artist.meta_title')" active="overview">
    <div data-reveal>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-ruby-red/10 px-3 py-1 text-xs font-bold uppercase tracking-wide text-ruby-red"><i class="fa-solid fa-music fa-xs"></i> {{ __('dashboard.artist.badge') }}</span>
        <x-dashboard-greeting />
        <p class="mt-2 text-prussian-blue/60">{{ __('dashboard.artist.subtitle') }}</p>
    </div>

    <section data-reveal style="--reveal-delay: .1s" class="mt-10">
        <h2 class="text-lg font-bold text-prussian-blue">{{ __('dashboard.artist.bookings_title') }}</h2>

        @if ($upcoming->isEmpty())
            <div class="mt-4 flex flex-col items-center rounded-2xl border border-dashed border-prussian-blue/20 bg-white px-6 py-14 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-prussian-blue/5 text-xl text-prussian-blue/40"><i class="fa-solid fa-calendar-days"></i></span>
                <p class="mt-4 font-bold text-prussian-blue">{{ __('dashboard.artist.bookings_empty_title') }}</p>
                <p class="mt-1 max-w-sm text-sm text-prussian-blue/60">{{ __('dashboard.artist.bookings_empty_text') }}</p>
                <a href="{{ route('studios') }}" class="mt-6 inline-flex items-center gap-2 rounded-full bg-ruby-red px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                    <i class="fa-solid fa-magnifying-glass fa-sm"></i> {{ __('dashboard.artist.bookings_cta') }}
                </a>
            </div>
        @else
            <div class="mt-4 space-y-3">
                @foreach ($upcoming as $booking)
                    @php $status = $booking->effectiveStatus(); @endphp
                    <div class="rounded-2xl border border-prussian-blue/10 bg-white p-5">
                        <div class="flex flex-wrap items-center gap-4 lg:flex-nowrap">
                            @if ($booking->room->photos->isNotEmpty())
                                <img src="{{ $booking->room->photos->first()->thumbUrl() }}" alt="{{ $booking->room->title }}" class="h-16 w-24 shrink-0 rounded-xl object-cover">
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('studios.show', $booking->room) }}" class="font-bold text-prussian-blue hover:text-ruby-red">{{ $booking->room->title }}</a>
                                    <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $status->badgeClasses() }}">{{ __('booking.status.' . $status->value) }}</span>
                                </div>
                                <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-prussian-blue/60">
                                    <span><i class="fa-solid fa-calendar-days fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->date->translatedFormat('D j M Y') }}</span>
                                    <span><i class="fa-solid fa-clock fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->timeRange() }}</span>
                                    <span><i class="fa-solid fa-euro-sign fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $money($booking->total_cents) }}</span>
                                </p>
                                @if ($status === BookingStatus::Confirmed)
                                    <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-prussian-blue/60">
                                        <span><i class="fa-solid fa-location-dot fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->room->studio->fullAddress() }}</span>
                                        @if ($booking->room->studio->phone)
                                            <span><i class="fa-solid fa-phone fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->room->studio->phone }}</span>
                                        @endif
                                    </p>
                                @elseif ($status === BookingStatus::PendingConfirmation)
                                    <p class="mt-1 text-sm text-prussian-blue/50">{{ __('booking.waiting_note') }}</p>
                                @endif
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                @if ($status === BookingStatus::PendingPayment)
                                    <a href="{{ route('bookings.payment', $booking) }}" class="rounded-full bg-ruby-red px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                                        {{ __('booking.finish_payment') }}
                                    </a>
                                @else
                                    @if ($status === BookingStatus::Confirmed)
                                        <a href="{{ route('bookings.ics', $booking) }}" title="{{ __('booking.ics_button') }}" class="rounded-full border border-prussian-blue/20 px-4 py-2.5 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">
                                            <i class="fa-solid fa-calendar-plus fa-sm"></i>
                                        </a>
                                    @endif
                                    @if ($booking->canReschedule())
                                        <a href="{{ route('bookings.reschedule', $booking) }}" class="rounded-full border border-prussian-blue/20 px-5 py-2.5 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">
                                            {{ __('booking.reschedule_button') }}
                                        </a>
                                    @endif
                                    @if (in_array($status, [BookingStatus::PendingConfirmation, BookingStatus::Confirmed], true))
                                        <form method="POST" action="{{ route('bookings.cancel', $booking) }}" data-confirm="{{ __('booking.cancel_confirm') }}">
                                            @csrf
                                            <button type="submit" class="cursor-pointer rounded-full border border-prussian-blue/20 px-5 py-2.5 text-sm font-semibold text-prussian-blue transition hover:bg-ruby-red/10 hover:text-ruby-red">
                                                {{ __('booking.cancel_button') }}
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </div>

                        @if ($booking->canReportProblem())
                            <details class="mt-4 border-t border-prussian-blue/10 pt-4">
                                <summary class="cursor-pointer text-sm font-semibold text-ruby-red hover:underline">
                                    <i class="fa-solid fa-triangle-exclamation fa-sm mr-1"></i>{{ __('booking.problem.title') }}
                                </summary>
                                <form method="POST" action="{{ route('bookings.problem', $booking) }}" enctype="multipart/form-data" class="mt-3">
                                    @csrf
                                    <label class="block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('booking.problem.situation_label') }}</label>
                                    <textarea name="dispute_reason" rows="3" required minlength="10" placeholder="{{ __('booking.problem.placeholder') }}" class="mt-1.5 w-full rounded-xl border border-prussian-blue/15 px-4 py-2.5 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none">{{ old('dispute_reason') }}</textarea>
                                    <x-input-error field="dispute_reason" />
                                    <label class="mt-3 block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('booking.problem.studio_response_label') }}</label>
                                    <textarea name="dispute_studio_response" rows="2" placeholder="{{ __('booking.problem.studio_response_placeholder') }}" class="mt-1.5 w-full rounded-xl border border-prussian-blue/15 px-4 py-2.5 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none">{{ old('dispute_studio_response') }}</textarea>
                                    <x-input-error field="dispute_studio_response" />
                                    <label class="mt-2 flex w-fit cursor-pointer items-center gap-2 text-sm font-semibold text-prussian-blue/70 transition hover:text-prussian-blue">
                                        <i class="fa-solid fa-paperclip fa-sm"></i> {{ __('booking.problem.photos_label') }}
                                        <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="sr-only" onchange="this.closest('label').querySelector('[data-file-count]').textContent = this.files.length ? '(' + this.files.length + ')' : ''">
                                        <span data-file-count class="text-xs font-bold text-ruby-red"></span>
                                    </label>
                                    <x-input-error field="photos" />
                                    <x-input-error field="photos.*" />
                                    <p class="mt-1.5 text-xs text-prussian-blue/50">{{ __('booking.problem.note') }}</p>
                                    <button type="submit" class="mt-2 cursor-pointer rounded-full bg-ruby-red px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-ruby-red/90">{{ __('booking.problem.submit') }}</button>
                                </form>
                            </details>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    @if ($past->isNotEmpty())
        <section data-reveal style="--reveal-delay: .2s" class="mt-10">
            <h2 class="text-lg font-bold text-prussian-blue">{{ __('dashboard.artist.history_title') }}</h2>
            <div class="mt-4 space-y-3">
                @foreach ($past as $booking)
                    @php $status = $booking->effectiveStatus(); @endphp
                    <div class="flex flex-wrap items-center gap-4 rounded-2xl border border-prussian-blue/10 bg-white p-4 opacity-80 lg:flex-nowrap">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('studios.show', $booking->room) }}" class="text-sm font-bold text-prussian-blue hover:text-ruby-red">{{ $booking->room->title }}</a>
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $status->badgeClasses() }}">{{ __('booking.status.' . $status->value) }}</span>
                            </div>
                            <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-prussian-blue/60">
                                <span><i class="fa-solid fa-calendar-days fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->date->translatedFormat('D j M Y') }}</span>
                                <span><i class="fa-solid fa-clock fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->timeRange() }}</span>
                                <span><i class="fa-solid fa-euro-sign fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $money($booking->total_cents) }}</span>
                            </p>

                            @php $outcome = $booking->disputeOutcome(); @endphp
                            @if ($outcome === 'dismissed')
                                <p class="mt-2 flex items-start gap-2 text-sm font-medium text-ruby-red">
                                    <i class="fa-solid fa-circle-xmark mt-0.5"></i>
                                    {{ __('booking.problem.dismissed', ['date' => $booking->disputed_at->translatedFormat('j F')]) }}
                                </p>
                            @elseif ($outcome === 'partial')
                                <p class="mt-2 flex items-start gap-2 text-sm font-medium text-amber-600">
                                    <i class="fa-solid fa-scale-balanced mt-0.5"></i>
                                    {{ __('booking.problem.partial', ['date' => $booking->disputed_at->translatedFormat('j F'), 'amount' => $money($booking->refunded_cents)]) }}
                                </p>
                            @elseif ($outcome === 'upheld')
                                <p class="mt-2 flex items-start gap-2 text-sm font-medium text-emerald-600">
                                    <i class="fa-solid fa-circle-check mt-0.5"></i>
                                    {{ __('booking.problem.upheld', ['date' => $booking->disputed_at->translatedFormat('j F')]) }}
                                </p>
                            @endif
                            @if ($outcome !== null && $booking->resolution_note)
                                <p class="mt-1 text-sm text-prussian-blue/60">{{ __('booking.problem.note_label') }}: {{ $booking->resolution_note }}</p>
                            @endif

                            @if ($booking->canReportProblem())
                                <details class="mt-3">
                                    <summary class="cursor-pointer text-sm font-semibold text-ruby-red hover:underline">
                                        <i class="fa-solid fa-triangle-exclamation fa-sm mr-1"></i>{{ __('booking.problem.title') }}</summary>
                                    <form method="POST" action="{{ route('bookings.problem', $booking) }}" enctype="multipart/form-data" class="mt-3">
                                        @csrf
                                        <label class="block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('booking.problem.situation_label') }}</label>
                                        <textarea name="dispute_reason" rows="3" required minlength="10" placeholder="{{ __('booking.problem.placeholder') }}" class="mt-1.5 w-full rounded-xl border border-prussian-blue/15 px-4 py-2.5 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none">{{ old('dispute_reason') }}</textarea>
                                        <x-input-error field="dispute_reason" />
                                        <label class="mt-3 block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('booking.problem.studio_response_label') }}</label>
                                        <textarea name="dispute_studio_response" rows="2" placeholder="{{ __('booking.problem.studio_response_placeholder') }}" class="mt-1.5 w-full rounded-xl border border-prussian-blue/15 px-4 py-2.5 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none">{{ old('dispute_studio_response') }}</textarea>
                                        <x-input-error field="dispute_studio_response" />
                                        <label class="mt-2 flex w-fit cursor-pointer items-center gap-2 text-sm font-semibold text-prussian-blue/70 transition hover:text-prussian-blue">
                                            <i class="fa-solid fa-paperclip fa-sm"></i> {{ __('booking.problem.photos_label') }}
                                            <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="sr-only" onchange="this.closest('label').querySelector('[data-file-count]').textContent = this.files.length ? '(' + this.files.length + ')' : ''">
                                            <span data-file-count class="text-xs font-bold text-ruby-red"></span>
                                        </label>
                                        <x-input-error field="photos" />
                                        <x-input-error field="photos.*" />
                                        <p class="mt-1.5 text-xs text-prussian-blue/50">{{ __('booking.problem.note') }}</p>
                                        <button type="submit" class="mt-2 cursor-pointer rounded-full bg-ruby-red px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-ruby-red/90">{{ __('booking.problem.submit') }}</button>
                                    </form>
                                </details>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</x-artist-layout>
