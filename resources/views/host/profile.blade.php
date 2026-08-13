<x-host-layout :title="__('host.profile.title')" active="profile">
    @php
        $label = 'block text-xs font-bold uppercase tracking-wide text-prussian-blue/50';
        $field = 'mt-2 w-full rounded-xl border border-prussian-blue/15 bg-white px-4 py-2.5 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none';
    @endphp

    <h1 class="text-2xl font-bold text-prussian-blue">{{ __('host.profile.title') }}</h1>
    <p class="mt-2 text-prussian-blue/60">{{ __('host.profile.subtitle') }}</p>

    <form method="POST" action="{{ route('host.profile.update') }}" class="mt-8 space-y-8">
        @csrf
        @method('PUT')

        <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6 sm:p-8">
            <h2 class="font-bold text-prussian-blue">{{ __('host.profile.section_general') }}</h2>
            <div class="mt-5 space-y-5">
                <div>
                    <label for="name" class="{{ $label }}">{{ __('host.profile.name') }}</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $profile->name) }}" placeholder="{{ __('host.profile.name_placeholder') }}" class="{{ $field }}" required>
                    <x-input-error field="name" />
                </div>
                <div>
                    <label for="phone" class="{{ $label }}">{{ __('host.profile.phone') }}</label>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone', $profile->phone) }}" placeholder="06 12345678" class="{{ $field }}" required>
                    <x-input-error field="phone" />
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6 sm:p-8">
            <h2 class="font-bold text-prussian-blue">{{ __('host.profile.section_business') }}</h2>
            <div class="mt-5 space-y-5">
                <div>
                    <span class="{{ $label }}">{{ __('host.profile.owner_type') }}</span>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        @foreach (['particulier', 'ondernemer'] as $type)
                            <label>
                                <input type="radio" name="owner_type" value="{{ $type }}" class="peer sr-only" @checked(old('owner_type', $profile->owner_type?->value ?? 'particulier') === $type)>
                                <span class="flex cursor-pointer items-center gap-2 rounded-xl border border-prussian-blue/15 px-4 py-3 text-sm font-semibold text-prussian-blue transition peer-checked:border-ruby-red peer-checked:bg-ruby-red/5">
                                    <i class="fa-solid {{ $type === 'particulier' ? 'fa-user' : 'fa-briefcase' }} text-xs text-ruby-red"></i>
                                    {{ __('host.profile.owner_type_' . $type) }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error field="owner_type" />
                </div>

                <div>
                    <span class="{{ $label }}">{{ __('host.profile.btw_plichtig') }}</span>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        @foreach ([1 => __('host.profile.yes'), 0 => __('host.profile.no')] as $value => $text)
                            <label>
                                <input type="radio" name="btw_plichtig" value="{{ $value }}" class="peer sr-only" @checked((string) old('btw_plichtig', $profile->btw_plichtig ? '1' : '0') === (string) $value)>
                                <span class="flex cursor-pointer items-center justify-center rounded-xl border border-prussian-blue/15 px-4 py-3 text-sm font-semibold text-prussian-blue transition peer-checked:border-ruby-red peer-checked:bg-ruby-red/5">{{ $text }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error field="btw_plichtig" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="kvk_number" class="{{ $label }}">{{ __('host.profile.kvk') }}</label>
                        <input id="kvk_number" type="text" name="kvk_number" value="{{ old('kvk_number', $profile->kvk_number) }}" placeholder="12345678" class="{{ $field }}">
                        <p class="mt-1.5 text-xs text-prussian-blue/50">{{ __('host.profile.kvk_hint') }}</p>
                        <x-input-error field="kvk_number" />
                    </div>
                    <div>
                        <label for="vat_number" class="{{ $label }}">{{ __('host.profile.vat') }}</label>
                        <input id="vat_number" type="text" name="vat_number" value="{{ old('vat_number', $profile->vat_number) }}" placeholder="NL123456789B01" class="{{ $field }}">
                        <p class="mt-1.5 text-xs text-prussian-blue/50">{{ __('host.profile.vat_hint') }}</p>
                        <x-input-error field="vat_number" />
                    </div>
                </div>

                <x-info-note>{{ __('host.profile.stripe_note') }}</x-info-note>
            </div>
        </div>

        <button type="submit" class="cursor-pointer rounded-full bg-ruby-red px-8 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
            {{ __('host.profile.submit') }}
        </button>
    </form>
</x-host-layout>
