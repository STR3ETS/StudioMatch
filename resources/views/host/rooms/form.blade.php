<x-host-layout :title="$room->exists ? __('host.rooms.edit_title') : __('host.rooms.create_title')" active="studios">
    @php
        $label = 'block text-xs font-bold uppercase tracking-wide text-prussian-blue/50';
        $field = 'mt-2 w-full rounded-xl border border-prussian-blue/15 bg-white px-4 py-2.5 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none';
        $check = 'flex cursor-pointer items-center gap-2.5 rounded-xl border border-prussian-blue/15 px-4 py-3 text-sm font-medium text-prussian-blue transition has-checked:border-ruby-red has-checked:bg-ruby-red/5';
    @endphp

    <nav class="text-sm text-prussian-blue/50">
        <a href="{{ route('host.studios.index') }}" class="hover:text-prussian-blue">{{ __('host.nav.studios') }}</a>
        <span class="px-1">/</span>
        <a href="{{ route('host.studios.show', $studio) }}" class="hover:text-prussian-blue">{{ $studio->name }}</a>
        <span class="px-1">/</span>
        <span class="text-prussian-blue/70">{{ $room->exists ? $room->title : __('host.rooms.create_title') }}</span>
    </nav>

    <div class="mt-3 flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-prussian-blue">{{ $room->exists ? __('host.rooms.edit_title') : __('host.rooms.create_title') }}</h1>
        @if ($room->exists)
            <span class="rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide {{ $room->status->badgeClasses() }}">{{ __('host.status.' . $room->status->value) }}</span>
        @endif
    </div>

    @if ($room->exists && $room->status === \App\Enums\RoomStatus::InReview)
        <div class="mt-6 flex items-start gap-3 rounded-2xl border border-amber-500/30 bg-amber-500/5 px-5 py-4">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-500/10 text-amber-600"><i class="fa-solid fa-hourglass-half fa-sm"></i></span>
            <div>
                <p class="text-sm font-bold text-prussian-blue">{{ __('host.rooms.in_review_title') }}</p>
                <p class="mt-0.5 text-sm leading-relaxed text-prussian-blue/60">{{ __('host.rooms.in_review_notice') }}</p>
            </div>
        </div>
    @elseif ($room->exists && $room->status === \App\Enums\RoomStatus::Afgekeurd)
        <div class="mt-6 flex items-start gap-3 rounded-2xl border border-ruby-red/30 bg-ruby-red/5 px-5 py-4">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-ruby-red/10 text-ruby-red"><i class="fa-solid fa-circle-exclamation fa-sm"></i></span>
            <div>
                <p class="text-sm font-bold text-prussian-blue">{{ __('host.rooms.rejected_title') }}</p>
                @if ($room->rejection_reason)
                    <p class="mt-1.5 rounded-xl bg-white px-3.5 py-2.5 text-sm leading-relaxed text-prussian-blue/80">
                        <span class="font-semibold">{{ __('host.rooms.rejected_reason_label') }}:</span> {{ $room->rejection_reason }}
                    </p>
                @endif
                <p class="mt-1.5 text-sm leading-relaxed text-prussian-blue/60">{{ __('host.rooms.rejected_notice') }}</p>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ $room->exists ? route('host.rooms.update', $room) : route('host.studios.rooms.store', $studio) }}" enctype="multipart/form-data" class="mt-8 space-y-8">
        @csrf
        @if ($room->exists)
            @method('PUT')
        @endif

        <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6 sm:p-8">
            <h2 class="font-bold text-prussian-blue">{{ __('host.rooms.section_general') }}</h2>
            <div class="mt-5 space-y-5">
                <div>
                    <label for="title" class="{{ $label }}">{{ __('host.rooms.fields.title') }}</label>
                    <input id="title" type="text" name="title" value="{{ old('title', $room->title) }}" placeholder="{{ __('host.rooms.fields.title_placeholder') }}" class="{{ $field }}" required>
                    <x-input-error field="title" />
                </div>

                <div>
                    <label for="description" class="{{ $label }}">{{ __('host.rooms.fields.description') }}</label>
                    <textarea id="description" name="description" rows="5" placeholder="{{ __('host.rooms.fields.description_placeholder') }}" class="{{ $field }}" required>{{ old('description', $room->description) }}</textarea>
                    <x-input-error field="description" />
                </div>

                <div>
                    <span class="{{ $label }}">{{ __('host.rooms.fields.type') }}</span>
                    <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach (\App\Enums\RoomType::cases() as $type)
                            <label>
                                <input type="radio" name="type" value="{{ $type->value }}" class="peer sr-only" @checked(old('type', $room->type?->value ?? 'opname') === $type->value)>
                                <span class="flex cursor-pointer items-center gap-2 rounded-xl border border-prussian-blue/15 px-4 py-3 text-sm font-semibold text-prussian-blue transition peer-checked:border-ruby-red peer-checked:bg-ruby-red/5">
                                    <i class="fa-solid {{ $type === \App\Enums\RoomType::Opname ? 'fa-microphone' : 'fa-sliders' }} text-xs text-ruby-red"></i>
                                    {{ __('host.types.' . $type->value) }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error field="type" />
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6 sm:p-8">
            <h2 class="font-bold text-prussian-blue">{{ __('host.rooms.section_pricing') }}</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-3">
                <div>
                    <label for="hourly_rate" class="{{ $label }}">{{ __('host.rooms.fields.hourly_rate') }}</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-4 top-1/2 mt-1 -translate-y-1/2 text-sm text-prussian-blue/50">€</span>
                        <input id="hourly_rate" type="number" name="hourly_rate" step="0.50" min="1" max="1000" value="{{ old('hourly_rate', $room->exists ? number_format($room->hourlyRateEuros(), 2, '.', '') : '') }}" class="{{ $field }} pl-9" required>
                    </div>
                    <x-input-error field="hourly_rate" />
                </div>
                <div>
                    <label for="min_hours" class="{{ $label }}">{{ __('host.rooms.fields.min_hours') }}</label>
                    <select id="min_hours" name="min_hours" class="{{ $field }} cursor-pointer">
                        @for ($h = 2; $h <= 8; $h++)
                            <option value="{{ $h }}" @selected((int) old('min_hours', $room->min_hours ?? 2) === $h)>{{ __('host.rooms.hours', ['count' => $h]) }}</option>
                        @endfor
                    </select>
                    <p class="mt-1.5 text-xs text-prussian-blue/50">{{ __('host.rooms.fields.min_hours_hint') }}</p>
                    <x-input-error field="min_hours" />
                </div>
                <div>
                    <label for="capacity" class="{{ $label }}">{{ __('host.rooms.fields.capacity') }}</label>
                    <input id="capacity" type="number" name="capacity" min="1" max="50" value="{{ old('capacity', $room->capacity) }}" class="{{ $field }}" required>
                    <x-input-error field="capacity" />
                </div>
            </div>

            @php
                $engineerOption = old('engineer_option', $room->engineer_included ? 'included' : ($room->engineer_rate_cents !== null ? 'optional' : 'none'));
            @endphp
            <div class="mt-5">
                <span class="{{ $label }}">{{ __('host.rooms.fields.engineer_option') }}</span>
                <div class="mt-2 flex flex-wrap gap-2" data-engineer-options>
                    @foreach (['none', 'included', 'optional'] as $option)
                        <label>
                            <input type="radio" name="engineer_option" value="{{ $option }}" class="peer sr-only" @checked($engineerOption === $option)>
                            <span class="inline-flex cursor-pointer items-center rounded-full border border-prussian-blue/15 px-4 py-2 text-sm font-semibold text-prussian-blue/70 transition peer-checked:border-ruby-red peer-checked:bg-ruby-red/5 peer-checked:text-prussian-blue">
                                {{ __('host.rooms.fields.engineer_' . $option) }}
                            </span>
                        </label>
                    @endforeach
                </div>
                <x-input-error field="engineer_option" />
                <div data-engineer-rate class="{{ $engineerOption === 'optional' ? 'mt-3' : 'mt-3 hidden' }}">
                    <label for="engineer_rate" class="{{ $label }}">{{ __('host.rooms.fields.engineer_rate') }}</label>
                    <div class="mt-2 flex items-center gap-2">
                        <span class="text-sm text-prussian-blue/50">&euro;</span>
                        <input id="engineer_rate" type="number" name="engineer_rate" min="1" max="500" step="0.50" value="{{ old('engineer_rate', $room->engineer_rate_cents !== null ? $room->engineer_rate_cents / 100 : '') }}" class="{{ $field }} !mt-0 max-w-40">
                        <span class="text-sm text-prussian-blue/50">{{ __('host.rooms.per_hour') }}</span>
                    </div>
                    <x-input-error field="engineer_rate" />
                </div>
            </div>

            <x-info-note class="mt-5">{{ __('host.rooms.pricing_note') }}</x-info-note>

            <script>
                (() => {
                    const options = document.querySelector('[data-engineer-options]');
                    const rate = document.querySelector('[data-engineer-rate]');
                    if (! options || ! rate) return;
                    options.querySelectorAll('input[type="radio"]').forEach((radio) => {
                        radio.addEventListener('change', () => rate.classList.toggle('hidden', radio.value !== 'optional'));
                    });
                })();
            </script>
        </div>

        <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6 sm:p-8">
            <h2 class="font-bold text-prussian-blue">{{ __('host.rooms.section_photos') }}</h2>
            <p class="mt-1 text-sm text-prussian-blue/60">{{ __('host.rooms.photos_hint') }}</p>

            @if ($room->exists && $room->photos->isNotEmpty())
                <div data-photos-grid data-reorder-url="{{ route('host.rooms.photos.reorder', $room) }}" data-token="{{ csrf_token() }}"
                     class="mt-5 grid grid-cols-2 gap-3 transition-opacity duration-200 sm:grid-cols-4">
                    @foreach ($room->photos as $photo)
                        <div data-sortable-item data-photo-id="{{ $photo->id }}" class="group relative select-none">
                            <img src="{{ $photo->url() }}" alt="" class="pointer-events-none aspect-[4/3] w-full rounded-xl object-cover">
                            <span class="absolute left-2 top-2 rounded-full bg-prussian-blue/80 px-2 py-0.5 text-[10px] font-bold text-white shadow">
                                <span data-photo-number>{{ $loop->iteration }}</span><span data-photo-main class="{{ $loop->first ? '' : 'hidden' }}"> &middot; {{ __('host.rooms.main_photo') }}</span>
                            </span>
                            <button type="submit" form="delete-photo-{{ $photo->id }}" title="{{ __('host.rooms.photo_delete') }}"
                                    class="absolute right-2 top-2 flex h-7 w-7 cursor-pointer items-center justify-center rounded-full bg-white/90 text-ruby-red shadow transition sm:opacity-0 sm:group-hover:opacity-100">
                                <i class="fa-solid fa-trash fa-xs"></i>
                            </button>
                            <button type="button" data-sortable-handle title="{{ __('host.rooms.photo_move') }}"
                                    class="sm-drag-handle absolute inset-x-2 bottom-2 flex h-7 items-center justify-center gap-1.5 rounded-full bg-white/90 text-xs font-semibold text-prussian-blue shadow transition hover:bg-white">
                                <i class="fa-solid fa-grip-lines fa-xs"></i>{{ __('host.rooms.photo_drag') }}
                            </button>
                        </div>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-prussian-blue/50">{{ __('host.rooms.photo_order_hint') }}</p>
            @endif

            <label class="mt-5 flex cursor-pointer flex-col items-center rounded-xl border border-dashed border-prussian-blue/25 px-6 py-8 text-center transition hover:border-prussian-blue/50 hover:bg-prussian-blue/[0.02]">
                <i class="fa-solid fa-cloud-arrow-up text-xl text-prussian-blue/40"></i>
                <span class="mt-2 text-sm font-semibold text-prussian-blue">{{ __('host.rooms.photos_upload') }}</span>
                <span class="mt-1 text-xs text-prussian-blue/50">{{ __('host.rooms.photos_formats') }}</span>
                <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="sr-only" data-photo-input data-selected-label="{{ __('host.rooms.photos_selected') }}">
                <span data-file-count class="mt-2 text-xs font-bold text-ruby-red"></span>
            </label>
            <div data-photo-preview data-drag-label="{{ __('host.rooms.photo_move') }}" data-remove-label="{{ __('host.rooms.photo_delete') }}" class="mt-4 hidden grid-cols-3 gap-2 sm:grid-cols-5"></div>
            <x-input-error field="photos" />
            <x-input-error field="photos.*" />
        </div>

        <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6 sm:p-8">
            <h2 class="font-bold text-prussian-blue">{{ __('host.rooms.section_equipment') }}</h2>
            <div class="mt-5 grid gap-2 sm:grid-cols-2">
                @foreach (config('studio.equipment') as $item)
                    <label class="{{ $check }}">
                        <input type="checkbox" name="equipment[]" value="{{ $item }}" class="h-4 w-4 rounded border-prussian-blue/30 accent-ruby-red" @checked(in_array($item, old('equipment', $room->equipment ?? [])))>
                        {{ __('studios.equipment.' . $item) }}
                    </label>
                @endforeach
            </div>
            <div class="mt-4">
                <label for="equipment_extra" class="{{ $label }}">{{ __('host.rooms.fields.equipment_extra') }}</label>
                <input id="equipment_extra" type="text" name="equipment_extra" value="{{ old('equipment_extra', $room->equipment_extra) }}" placeholder="{{ __('host.rooms.fields.equipment_extra_placeholder') }}" class="{{ $field }}">
                <x-input-error field="equipment_extra" />
            </div>

            <h3 class="mt-6 text-sm font-bold text-prussian-blue">{{ __('host.rooms.fields.daws') }}</h3>
            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                @foreach (config('studio.daws') as $daw)
                    <label class="{{ $check }}">
                        <input type="checkbox" name="daws[]" value="{{ $daw }}" class="h-4 w-4 rounded border-prussian-blue/30 accent-ruby-red" @checked(in_array($daw, old('daws', $room->daws ?? [])))>
                        {{ $daw }}
                    </label>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6 sm:p-8">
            <h2 class="font-bold text-prussian-blue">{{ __('host.rooms.section_extras') }}</h2>
            <div class="mt-5 grid gap-2 sm:grid-cols-2">
                @foreach (config('studio.facilities') as $facility)
                    <label class="{{ $check }}">
                        <input type="checkbox" name="facilities[]" value="{{ $facility }}" class="h-4 w-4 rounded border-prussian-blue/30 accent-ruby-red" @checked(in_array($facility, old('facilities', $room->facilities ?? [])))>
                        {{ __('studios.facilities.' . $facility) }}
                    </label>
                @endforeach
            </div>
            <div class="mt-4">
                <label for="house_rules" class="{{ $label }}">{{ __('host.rooms.fields.house_rules') }}</label>
                <textarea id="house_rules" name="house_rules" rows="4" placeholder="{{ __('host.rooms.fields.house_rules_placeholder') }}" class="{{ $field }}">{{ old('house_rules', $room->house_rules) }}</textarea>
                <p class="mt-1.5 text-xs text-prussian-blue/50">{{ __('host.rooms.fields.house_rules_hint') }}</p>
                <x-input-error field="house_rules" />
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="cursor-pointer rounded-full bg-ruby-red px-8 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                {{ $room->exists ? __('host.rooms.save') : __('host.rooms.create') }}
            </button>
            <a href="{{ route('host.studios.show', $studio) }}" class="rounded-full border border-prussian-blue/20 px-6 py-3 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">
                {{ __('host.rooms.cancel') }}
            </a>
        </div>
    </form>

    @if ($room->exists)
        @foreach ($room->photos as $photo)
            <form id="delete-photo-{{ $photo->id }}" method="POST" action="{{ route('host.rooms.photos.destroy', [$room, $photo]) }}">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    @endif

</x-host-layout>
