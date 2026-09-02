<x-host-layout :title="__('host.studios.create_title')" active="studios">
    @php
        $label = 'block text-xs font-bold uppercase tracking-wide text-prussian-blue/50';
        $field = 'mt-2 w-full rounded-xl border border-prussian-blue/15 bg-white px-4 py-2.5 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none';
        $check = 'flex cursor-pointer items-center gap-2.5 rounded-xl border border-prussian-blue/15 px-4 py-3 text-sm font-medium text-prussian-blue transition has-checked:border-ruby-red has-checked:bg-ruby-red/5';
        $steps = ['studio', 'room', 'pricing', 'equipment', 'photos'];
    @endphp

    <nav class="text-sm text-prussian-blue/50">
        <a href="{{ route('host.studios.index') }}" class="hover:text-prussian-blue">{{ __('host.nav.studios') }}</a>
        <span class="px-1">/</span>
        <span class="text-prussian-blue/70">{{ __('host.studios.create_title') }}</span>
    </nav>

    <h1 class="mt-3 text-2xl font-bold text-prussian-blue">{{ __('host.wizard.title') }}</h1>
    <p class="mt-2 text-prussian-blue/60">{{ __('host.wizard.subtitle') }}</p>

    @if ($errors->isNotEmpty())
        <p class="mt-4 rounded-xl bg-ruby-red/10 px-4 py-3 text-sm font-semibold text-ruby-red">{{ __('host.wizard.errors_intro') }}</p>
    @endif

    <form method="POST" action="{{ route('host.studios.store') }}" enctype="multipart/form-data" data-wizard class="mt-6">
        @csrf

        <div class="flex items-center justify-between">
            <p class="text-sm font-semibold text-prussian-blue/60" data-wizard-progress></p>
            <div class="flex items-center gap-1.5">
                @foreach ($steps as $index => $step)
                    <span data-wizard-dot class="h-1.5 w-8 rounded-full bg-prussian-blue/10 transition"></span>
                @endforeach
            </div>
        </div>

        <div data-wizard-step data-step-title="{{ __('host.wizard.steps.studio') }}" class="mt-4 rounded-2xl border border-prussian-blue/10 bg-white p-6 sm:p-8">
            <h2 class="font-bold text-prussian-blue">{{ __('host.wizard.steps.studio') }}</h2>
            <p class="mt-1 text-sm text-prussian-blue/60">{{ __('host.studios.form_subtitle') }}</p>
            <div class="mt-5 space-y-5">
                <div>
                    <label for="name" class="{{ $label }}">{{ __('host.studios.fields.name') }}</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('host.studios.fields.name_placeholder') }}" class="{{ $field }}" required>
                    <x-input-error field="name" />
                </div>
                <div>
                    <label for="phone" class="{{ $label }}">{{ __('host.studios.fields.phone') }}</label>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" placeholder="06 12345678" class="{{ $field }}">
                    <p class="mt-1.5 text-xs text-prussian-blue/50">{{ __('host.studios.fields.phone_hint') }}</p>
                    <x-input-error field="phone" />
                </div>
                <div data-address-autocomplete data-url="{{ route('address.suggest') }}" class="relative">
                    <label for="street" class="{{ $label }}">{{ __('host.studios.fields.street') }}</label>
                    <input id="street" type="text" name="street" value="{{ old('street') }}" placeholder="Prinsengracht 263" class="{{ $field }}" autocomplete="off" data-address-street required>
                    <div data-address-suggestions class="absolute left-0 right-0 top-full z-30 mt-1 hidden overflow-hidden rounded-xl border border-prussian-blue/10 bg-white shadow-xl"></div>
                    <x-input-error field="street" />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="postal_code" class="{{ $label }}">{{ __('host.studios.fields.postal_code') }}</label>
                        <input id="postal_code" type="text" name="postal_code" value="{{ old('postal_code') }}" placeholder="1234 AB" class="{{ $field }}" data-address-postal required>
                        <x-input-error field="postal_code" />
                    </div>
                    <div>
                        <label for="city" class="{{ $label }}">{{ __('host.studios.fields.city') }}</label>
                        <input id="city" type="text" name="city" value="{{ old('city') }}" placeholder="Amsterdam" class="{{ $field }}" data-address-city required>
                        <x-input-error field="city" />
                    </div>
                </div>
                <p data-address-error class="hidden text-xs font-semibold text-ruby-red">{{ __('host.studios.address_invalid') }}</p>
                <x-info-note>{{ __('host.wizard.address_note') }}</x-info-note>
            </div>
        </div>

        <div data-wizard-step data-step-title="{{ __('host.wizard.steps.room') }}" class="mt-4 hidden rounded-2xl border border-prussian-blue/10 bg-white p-6 sm:p-8">
            <h2 class="font-bold text-prussian-blue">{{ __('host.wizard.steps.room') }}</h2>
            <p class="mt-1 text-sm text-prussian-blue/60">{{ __('host.wizard.room_note') }}</p>
            <div class="mt-5 space-y-5">
                <div>
                    <label for="title" class="{{ $label }}">{{ __('host.rooms.fields.title') }}</label>
                    <input id="title" type="text" name="title" value="{{ old('title') }}" placeholder="{{ __('host.rooms.fields.title_placeholder') }}" class="{{ $field }}" required>
                    <x-input-error field="title" />
                </div>
                <div>
                    <label for="description" class="{{ $label }}">{{ __('host.rooms.fields.description') }}</label>
                    <textarea id="description" name="description" rows="5" placeholder="{{ __('host.rooms.fields.description_placeholder') }}" class="{{ $field }}" required>{{ old('description') }}</textarea>
                    <x-input-error field="description" />
                </div>
                <div>
                    <span class="{{ $label }}">{{ __('host.rooms.fields.type') }}</span>
                    <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach (\App\Enums\RoomType::cases() as $type)
                            <label>
                                <input type="radio" name="type" value="{{ $type->value }}" class="peer sr-only" @checked(old('type', 'opname') === $type->value)>
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

        <div data-wizard-step data-step-title="{{ __('host.wizard.steps.pricing') }}" class="mt-4 hidden rounded-2xl border border-prussian-blue/10 bg-white p-6 sm:p-8">
            <h2 class="font-bold text-prussian-blue">{{ __('host.wizard.steps.pricing') }}</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-3">
                <div>
                    <label for="hourly_rate" class="{{ $label }}">{{ __('host.rooms.fields.hourly_rate') }}</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-4 top-1/2 mt-1 -translate-y-1/2 text-sm text-prussian-blue/50">&euro;</span>
                        <input id="hourly_rate" type="number" name="hourly_rate" step="0.50" min="1" max="1000" value="{{ old('hourly_rate') }}" class="{{ $field }} pl-9" required>
                    </div>
                    <x-input-error field="hourly_rate" />
                </div>
                <div>
                    <label for="min_hours" class="{{ $label }}">{{ __('host.rooms.fields.min_hours') }}</label>
                    <select id="min_hours" name="min_hours" class="{{ $field }} cursor-pointer">
                        @for ($h = 2; $h <= 8; $h++)
                            <option value="{{ $h }}" @selected((int) old('min_hours', 2) === $h)>{{ __('host.rooms.hours', ['count' => $h]) }}</option>
                        @endfor
                    </select>
                    <x-input-error field="min_hours" />
                </div>
                <div>
                    <label for="capacity" class="{{ $label }}">{{ __('host.rooms.fields.capacity') }}</label>
                    <input id="capacity" type="number" name="capacity" min="1" max="50" value="{{ old('capacity') }}" class="{{ $field }}" required>
                    <x-input-error field="capacity" />
                </div>
            </div>

            @php $engineerOption = old('engineer_option', 'none'); @endphp
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
                        <input id="engineer_rate" type="number" name="engineer_rate" min="1" max="500" step="0.50" value="{{ old('engineer_rate') }}" class="{{ $field }} !mt-0 max-w-40">
                        <span class="text-sm text-prussian-blue/50">{{ __('host.rooms.per_hour') }}</span>
                    </div>
                    <x-input-error field="engineer_rate" />
                </div>
            </div>

            <x-info-note class="mt-5">{{ __('host.rooms.pricing_note') }}</x-info-note>
        </div>

        <div data-wizard-step data-step-title="{{ __('host.wizard.steps.equipment') }}" class="mt-4 hidden rounded-2xl border border-prussian-blue/10 bg-white p-6 sm:p-8">
            <h2 class="font-bold text-prussian-blue">{{ __('host.wizard.steps.equipment') }}</h2>
            <p class="mt-1 text-sm text-prussian-blue/60">{{ __('host.wizard.equipment_note') }}</p>
            <div class="mt-5 grid gap-2 sm:grid-cols-2">
                @foreach (config('studio.equipment') as $item)
                    <label class="{{ $check }}">
                        <input type="checkbox" name="equipment[]" value="{{ $item }}" class="h-4 w-4 rounded border-prussian-blue/30 accent-ruby-red" @checked(in_array($item, old('equipment', [])))>
                        {{ __('studios.equipment.' . $item) }}
                    </label>
                @endforeach
            </div>
            <div class="mt-4">
                <label for="equipment_extra" class="{{ $label }}">{{ __('host.rooms.fields.equipment_extra') }}</label>
                <input id="equipment_extra" type="text" name="equipment_extra" value="{{ old('equipment_extra') }}" placeholder="{{ __('host.rooms.fields.equipment_extra_placeholder') }}" class="{{ $field }}">
            </div>

            <h3 class="mt-6 text-sm font-bold text-prussian-blue">{{ __('host.rooms.fields.daws') }}</h3>
            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                @foreach (config('studio.daws') as $daw)
                    <label class="{{ $check }}">
                        <input type="checkbox" name="daws[]" value="{{ $daw }}" class="h-4 w-4 rounded border-prussian-blue/30 accent-ruby-red" @checked(in_array($daw, old('daws', [])))>
                        {{ $daw }}
                    </label>
                @endforeach
            </div>

            <h3 class="mt-6 text-sm font-bold text-prussian-blue">{{ __('host.rooms.section_extras') }}</h3>
            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                @foreach (config('studio.facilities') as $facility)
                    <label class="{{ $check }}">
                        <input type="checkbox" name="facilities[]" value="{{ $facility }}" class="h-4 w-4 rounded border-prussian-blue/30 accent-ruby-red" @checked(in_array($facility, old('facilities', [])))>
                        {{ __('studios.facilities.' . $facility) }}
                    </label>
                @endforeach
            </div>
            <div class="mt-4">
                <label for="house_rules" class="{{ $label }}">{{ __('host.rooms.fields.house_rules') }}</label>
                <textarea id="house_rules" name="house_rules" rows="4" placeholder="{{ __('host.rooms.fields.house_rules_placeholder') }}" class="{{ $field }}">{{ old('house_rules') }}</textarea>
                <p class="mt-1.5 text-xs text-prussian-blue/50">{{ __('host.rooms.fields.house_rules_hint') }}</p>
            </div>
        </div>

        <div data-wizard-step data-step-title="{{ __('host.wizard.steps.photos') }}" class="mt-4 hidden rounded-2xl border border-prussian-blue/10 bg-white p-6 sm:p-8">
            <h2 class="font-bold text-prussian-blue">{{ __('host.wizard.steps.photos') }}</h2>
            <p class="mt-1 text-sm text-prussian-blue/60">{{ __('host.rooms.photos_hint') }}</p>
            <label class="mt-5 flex cursor-pointer flex-col items-center rounded-xl border border-dashed border-prussian-blue/25 px-6 py-8 text-center transition hover:border-prussian-blue/50 hover:bg-prussian-blue/[0.02]">
                <i class="fa-solid fa-cloud-arrow-up text-xl text-prussian-blue/40"></i>
                <span class="mt-2 text-sm font-semibold text-prussian-blue">{{ __('host.rooms.photos_upload') }}</span>
                <span class="mt-1 text-xs text-prussian-blue/50">{{ __('host.rooms.photos_formats') }}</span>
                <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="sr-only" data-photo-input data-selected-label="{{ __('host.rooms.photos_selected') }}" required>
                <span data-file-count class="mt-2 text-xs font-bold text-ruby-red"></span>
            </label>
            <div data-photo-preview data-drag-label="{{ __('host.rooms.photo_move') }}" data-remove-label="{{ __('host.rooms.photo_delete') }}" class="mt-4 hidden grid-cols-3 gap-2 sm:grid-cols-5"></div>
            <p class="mt-2 text-xs text-prussian-blue/50">{{ __('host.rooms.photo_order_hint') }}</p>
            <x-input-error field="photos" />
            <x-input-error field="photos.*" />
        </div>

        <div class="mt-6 flex items-center justify-between gap-3">
            <button type="button" data-wizard-back class="invisible cursor-pointer rounded-full border border-prussian-blue/20 px-6 py-3 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">
                <i class="fa-solid fa-arrow-left fa-sm mr-1.5"></i>{{ __('host.wizard.back') }}
            </button>
            <div class="flex items-center gap-3">
                <button type="button" data-wizard-next class="cursor-pointer rounded-full bg-ruby-red px-8 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                    {{ __('host.wizard.next') }}<i class="fa-solid fa-arrow-right fa-sm ml-1.5"></i>
                </button>
                <button type="submit" data-wizard-submit class="hidden cursor-pointer rounded-full bg-ruby-red px-8 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                    <i class="fa-solid fa-check fa-sm mr-1.5"></i>{{ __('host.wizard.submit') }}
                </button>
            </div>
        </div>
    </form>

    <script>
        (() => {
            const form = document.querySelector('[data-wizard]');
            if (! form) return;

            const steps = [...form.querySelectorAll('[data-wizard-step]')];
            const dots = [...form.querySelectorAll('[data-wizard-dot]')];
            const progress = form.querySelector('[data-wizard-progress]');
            const back = form.querySelector('[data-wizard-back]');
            const next = form.querySelector('[data-wizard-next]');
            const submit = form.querySelector('[data-wizard-submit]');
            const progressLabel = @json(__('host.wizard.progress'));
            let current = 0;

            const render = () => {
                steps.forEach((step, index) => step.classList.toggle('hidden', index !== current));
                dots.forEach((dot, index) => {
                    dot.classList.toggle('bg-ruby-red', index <= current);
                    dot.classList.toggle('bg-prussian-blue/10', index > current);
                });
                progress.textContent = progressLabel
                    .replace(':current', current + 1)
                    .replace(':total', steps.length)
                    .replace(':title', steps[current].dataset.stepTitle);
                back.classList.toggle('invisible', current === 0);
                next.classList.toggle('hidden', current === steps.length - 1);
                submit.classList.toggle('hidden', current !== steps.length - 1);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };

            const stepValid = () => {
                const fields = steps[current].querySelectorAll('input, select, textarea');
                for (const el of fields) {
                    if (! el.checkValidity()) {
                        el.reportValidity();
                        return false;
                    }
                }
                return true;
            };

            const addressError = form.querySelector('[data-address-error]');

            const checkAddress = async () => {
                try {
                    const response = await fetch(@json(route('host.studios.address-check')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                        },
                        body: JSON.stringify({
                            street: form.elements.street.value,
                            postal_code: form.elements.postal_code.value,
                            city: form.elements.city.value,
                        }),
                    });
                    if (! response.ok) return true;
                    return (await response.json()).found;
                } catch (error) {
                    return true;
                }
            };

            next.addEventListener('click', async () => {
                if (! stepValid()) return;

                if (current === 0) {
                    next.disabled = true;
                    const found = await checkAddress();
                    next.disabled = false;
                    addressError.classList.toggle('hidden', found);
                    if (! found) return;
                }

                current = Math.min(steps.length - 1, current + 1);
                render();
            });

            back.addEventListener('click', () => {
                current = Math.max(0, current - 1);
                render();
            });

            const firstError = steps.findIndex((step) => step.querySelector('[data-input-error]'));
            if (firstError > 0) current = firstError;

            render();

            const options = form.querySelector('[data-engineer-options]');
            const rate = form.querySelector('[data-engineer-rate]');
            options?.querySelectorAll('input[type="radio"]').forEach((radio) => {
                radio.addEventListener('change', () => rate.classList.toggle('hidden', radio.value !== 'optional'));
            });

        })();
    </script>
</x-host-layout>