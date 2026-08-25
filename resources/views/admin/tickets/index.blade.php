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

                            @if ($booking->dispute_studio_response)
                                <p class="mt-2 rounded-xl bg-prussian-blue/[0.03] px-4 py-3 text-sm leading-relaxed text-prussian-blue/80">
                                    <span class="font-semibold">{{ __('admin.tickets.studio_response') }}:</span> {{ $booking->dispute_studio_response }}
                                </p>
                            @endif

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

                    <form method="POST" action="{{ route('admin.tickets.resolve', $booking) }}" data-confirm="{{ __('admin.tickets.resolve_confirm') }}" data-confirm-accept="{{ __('admin.tickets.resolve_accept') }}" class="mt-5 rounded-xl border border-prussian-blue/10 bg-prussian-blue/[0.02] p-4">
                        @csrf
                        @method('PATCH')
                        <p class="text-sm font-bold text-prussian-blue">{{ __('admin.tickets.resolve_title') }}</p>
                        <div class="mt-3 flex flex-wrap items-end gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('admin.tickets.percent_label') }}</label>
                                <div class="mt-2 flex items-center gap-2">
                                    <input type="number" name="refund_percent" value="{{ old('refund_percent', 100) }}" min="0" max="100" step="5" class="w-24 rounded-xl border border-prussian-blue/15 bg-white px-3 py-2 text-sm text-prussian-blue focus:border-prussian-blue/40 focus:outline-none" required>
                                    <span class="text-sm text-prussian-blue/60">%</span>
                                </div>
                            </div>
                            <p class="pb-1 text-xs leading-relaxed text-prussian-blue/50">{{ __('admin.tickets.percent_hint') }}</p>
                        </div>
                        <x-input-error field="refund_percent" />
                        <div class="mt-3">
                            <label class="block text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('admin.tickets.note_label') }}</label>
                            <textarea name="resolution_note" rows="2" required minlength="10" placeholder="{{ __('admin.tickets.note_placeholder') }}" class="mt-2 w-full rounded-xl border border-prussian-blue/15 bg-white px-4 py-2.5 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none">{{ old('resolution_note') }}</textarea>
                            <x-input-error field="resolution_note" />
                        </div>
                        <button type="submit" class="mt-3 cursor-pointer rounded-full bg-ruby-red px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                            <i class="fa-solid fa-gavel fa-sm mr-1.5"></i>{{ __('admin.tickets.resolve_button') }}
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif

    @if ($damages->isNotEmpty())
        <section class="mt-12">
            <h2 class="text-lg font-bold text-prussian-blue">{{ __('admin.tickets.damage_title') }}</h2>
            <p class="mt-1 text-sm text-prussian-blue/60">{{ __('admin.tickets.damage_text') }}</p>

            <div class="mt-4 space-y-4">
                @foreach ($damages as $booking)
                    <div class="rounded-2xl border border-amber-500/40 bg-white p-6">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-bold text-prussian-blue">{{ $booking->room->studio->name }} - {{ $booking->room->title }}</h3>
                            <span class="rounded-full bg-amber-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-amber-700">{{ __('host.damage.badge') }}</span>
                        </div>
                        <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-prussian-blue/60">
                            <span><i class="fa-solid fa-calendar-days fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->date->translatedFormat('D j M Y') }} {{ $booking->timeRange() }}</span>
                            <span><i class="fa-solid fa-building fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->room->studio->user->name }} ({{ $booking->room->studio->user->email }})</span>
                            <span><i class="fa-solid fa-music fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->user->name }} ({{ $booking->user->email }})</span>
                            <span><i class="fa-solid fa-clock fa-xs mr-1.5 text-prussian-blue/40"></i>{{ __('admin.tickets.reported', ['date' => $booking->damage_reported_at->translatedFormat('j M Y H:i')]) }}</span>
                        </p>
                        <p class="mt-3 rounded-xl bg-prussian-blue/[0.03] px-4 py-3 text-sm leading-relaxed text-prussian-blue/80">
                            <span class="font-semibold">{{ __('admin.tickets.reason') }}:</span> {{ $booking->damage_reason }}
                        </p>
                        @if ($booking->damage_photos)
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($booking->damage_photos as $photo)
                                    <a href="{{ Storage::disk('public')->url($photo) }}" target="_blank" rel="noopener" class="group">
                                        <img src="{{ Storage::disk('public')->url($photo) }}" alt="" class="h-20 w-28 rounded-xl border border-prussian-blue/10 object-cover transition group-hover:brightness-90">
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</x-admin-layout>
