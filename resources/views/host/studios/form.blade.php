<x-host-layout :title="$studio->exists ? __('host.studios.edit_title') : __('host.studios.create_title')" active="studios">
    @php
        $label = 'block text-xs font-bold uppercase tracking-wide text-prussian-blue/50';
        $field = 'mt-2 w-full rounded-xl border border-prussian-blue/15 bg-white px-4 py-2.5 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none';
    @endphp

    <nav class="text-sm text-prussian-blue/50">
        <a href="{{ route('host.studios.index') }}" class="hover:text-prussian-blue">{{ __('host.nav.studios') }}</a>
        <span class="px-1">/</span>
        <span class="text-prussian-blue/70">{{ $studio->exists ? $studio->name : __('host.studios.create_title') }}</span>
    </nav>

    <h1 class="mt-3 text-2xl font-bold text-prussian-blue">{{ $studio->exists ? __('host.studios.edit_title') : __('host.studios.create_title') }}</h1>
    <p class="mt-2 text-prussian-blue/60">{{ __('host.studios.form_subtitle') }}</p>

    <form method="POST" action="{{ $studio->exists ? route('host.studios.update', $studio) : route('host.studios.store') }}" class="mt-8 space-y-8">
        @csrf
        @if ($studio->exists)
            @method('PUT')
        @endif

        <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6 sm:p-8">
            <h2 class="font-bold text-prussian-blue">{{ __('host.studios.section_general') }}</h2>
            <div class="mt-5 space-y-5">
                <div>
                    <label for="name" class="{{ $label }}">{{ __('host.studios.fields.name') }}</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $studio->name) }}" placeholder="{{ __('host.studios.fields.name_placeholder') }}" class="{{ $field }}" required>
                    <x-input-error field="name" />
                </div>
                <div>
                    <label for="phone" class="{{ $label }}">{{ __('host.studios.fields.phone') }}</label>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone', $studio->phone) }}" placeholder="06 12345678" class="{{ $field }}">
                    <p class="mt-1.5 text-xs text-prussian-blue/50">{{ __('host.studios.fields.phone_hint') }}</p>
                    <x-input-error field="phone" />
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6 sm:p-8">
            <h2 class="font-bold text-prussian-blue">{{ __('host.studios.section_address') }}</h2>
            <div class="mt-5 space-y-5">
                <div>
                    <label for="street" class="{{ $label }}">{{ __('host.studios.fields.street') }}</label>
                    <input id="street" type="text" name="street" value="{{ old('street', $studio->street) }}" placeholder="Prinsengracht 263" class="{{ $field }}" required>
                    <x-input-error field="street" />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="postal_code" class="{{ $label }}">{{ __('host.studios.fields.postal_code') }}</label>
                        <input id="postal_code" type="text" name="postal_code" value="{{ old('postal_code', $studio->postal_code) }}" placeholder="1234 AB" class="{{ $field }}" required>
                        <x-input-error field="postal_code" />
                    </div>
                    <div>
                        <label for="city" class="{{ $label }}">{{ __('host.studios.fields.city') }}</label>
                        <input id="city" type="text" name="city" value="{{ old('city', $studio->city) }}" placeholder="Amsterdam" class="{{ $field }}" required>
                        <x-input-error field="city" />
                    </div>
                </div>
                <x-info-note>{{ __('host.studios.address_note') }}</x-info-note>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="cursor-pointer rounded-full bg-ruby-red px-8 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                {{ $studio->exists ? __('host.studios.save') : __('host.studios.create') }}
            </button>
            <a href="{{ route('host.studios.index') }}" class="rounded-full border border-prussian-blue/20 px-6 py-3 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">
                {{ __('host.rooms.cancel') }}
            </a>
        </div>
    </form>
</x-host-layout>
