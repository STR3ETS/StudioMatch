<x-host-layout :title="__('host.damage.title')" active="damage">
    <h1 class="text-2xl font-bold text-prussian-blue">{{ __('host.damage.title') }}</h1>
    <p class="mt-2 text-prussian-blue/60">{{ __('host.damage.subtitle') }}</p>

    <x-info-note class="mt-4">{{ __('host.damage.note') }}</x-info-note>

    <section class="mt-8">
        <h2 class="text-lg font-bold text-prussian-blue">{{ __('host.damage.eligible_title') }}</h2>

        @if ($eligible->isEmpty())
            <p class="mt-4 rounded-xl border border-dashed border-prussian-blue/20 bg-white px-4 py-6 text-center text-sm text-prussian-blue/50">{{ __('host.damage.eligible_empty') }}</p>
        @else
            <div class="mt-4 space-y-3">
                @foreach ($eligible as $booking)
                    <div class="rounded-2xl border border-prussian-blue/10 bg-white p-5">
                        <p class="font-bold text-prussian-blue">{{ $booking->room->title }}</p>
                        <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-prussian-blue/60">
                            <span><i class="fa-solid fa-calendar-days fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->date->translatedFormat('D j M Y') }} {{ $booking->timeRange() }}</span>
                            <span><i class="fa-solid fa-user fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->user->name }}</span>
                        </p>

                        <details class="mt-3">
                            <summary class="cursor-pointer text-sm font-semibold text-ruby-red hover:underline">
                                <i class="fa-solid fa-triangle-exclamation fa-sm mr-1"></i>{{ __('host.damage.report_button') }}
                            </summary>
                            <form method="POST" action="{{ route('host.damage.store', $booking) }}" enctype="multipart/form-data" class="mt-3">
                                @csrf
                                <textarea name="damage_reason" rows="3" required minlength="10" placeholder="{{ __('host.damage.placeholder') }}" class="w-full rounded-xl border border-prussian-blue/15 px-4 py-2.5 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none">{{ old('damage_reason') }}</textarea>
                                <x-input-error field="damage_reason" />
                                <label class="mt-2 flex w-fit cursor-pointer items-center gap-2 text-sm font-semibold text-prussian-blue/70 transition hover:text-prussian-blue">
                                    <i class="fa-solid fa-paperclip fa-sm"></i> {{ __('booking.problem.photos_label') }}
                                    <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="sr-only" onchange="this.closest('label').querySelector('[data-file-count]').textContent = this.files.length ? '(' + this.files.length + ')' : ''">
                                    <span data-file-count class="text-xs font-bold text-ruby-red"></span>
                                </label>
                                <x-input-error field="photos" />
                                <x-input-error field="photos.*" />
                                <p class="mt-1.5 text-xs text-prussian-blue/50">{{ __('host.damage.window_note') }}</p>
                                <button type="submit" class="mt-2 cursor-pointer rounded-full bg-ruby-red px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-ruby-red/90">{{ __('host.damage.submit') }}</button>
                            </form>
                        </details>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    @if ($reported->isNotEmpty())
        <section class="mt-10">
            <h2 class="text-lg font-bold text-prussian-blue">{{ __('host.damage.reported_title') }}</h2>
            <div class="mt-4 space-y-3">
                @foreach ($reported as $booking)
                    <div class="rounded-2xl border border-prussian-blue/10 bg-white p-5">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-bold text-prussian-blue">{{ $booking->room->title }}</p>
                            <span class="rounded-full bg-amber-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-amber-600">{{ __('host.damage.badge') }}</span>
                        </div>
                        <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-prussian-blue/60">
                            <span><i class="fa-solid fa-calendar-days fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->date->translatedFormat('D j M Y') }} {{ $booking->timeRange() }}</span>
                            <span><i class="fa-solid fa-user fa-xs mr-1.5 text-prussian-blue/40"></i>{{ $booking->user->name }}</span>
                            <span><i class="fa-solid fa-clock fa-xs mr-1.5 text-prussian-blue/40"></i>{{ __('host.damage.reported_on', ['date' => $booking->damage_reported_at->translatedFormat('j M Y')]) }}</span>
                        </p>
                        <p class="mt-2 rounded-xl bg-prussian-blue/[0.03] px-4 py-3 text-sm leading-relaxed text-prussian-blue/70">{{ $booking->damage_reason }}</p>
                        <p class="mt-2 flex items-start gap-2 text-sm text-prussian-blue/60">
                            <i class="fa-solid fa-circle-info mt-0.5 text-prussian-blue/40"></i> {{ __('host.damage.followup') }}
                        </p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</x-host-layout>
