@php
    $user = auth()->user();
    $layout = $user->isHost() ? 'host-layout' : ($user->isAdmin() ? 'admin-layout' : 'artist-layout');

    $label = 'block text-xs font-bold uppercase tracking-wide text-prussian-blue/50';
    $field = 'mt-2 w-full rounded-xl border border-prussian-blue/15 bg-white px-4 py-2.5 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none';
@endphp

<x-dynamic-component :component="$layout" :title="__('account.title')" active="account">
    <h1 class="text-2xl font-bold text-prussian-blue">{{ __('account.title') }}</h1>
    <p class="mt-2 text-prussian-blue/60">{{ __('account.subtitle') }}</p>

    <div class="mt-8 space-y-8">
        <form method="POST" action="{{ route('account.profile.update') }}" class="rounded-2xl border border-prussian-blue/10 bg-white p-6 sm:p-8">
            @csrf
            @method('PUT')
            <h2 class="font-bold text-prussian-blue">{{ __('account.profile.title') }}</h2>
            <div class="mt-5 space-y-5">
                <div>
                    <label for="name" class="{{ $label }}">{{ __('account.profile.name') }}</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" class="{{ $field }}" required>
                    <x-input-error field="name" />
                </div>
                <div>
                    <label for="email" class="{{ $label }}">{{ __('account.profile.email') }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="{{ $field }}" required>
                    <x-input-error field="email" />
                </div>
                @if ($user->isArtist())
                    <div>
                        <label for="street" class="{{ $label }}">{{ __('account.profile.street') }}</label>
                        <input id="street" type="text" name="street" value="{{ old('street', $user->street) }}" class="{{ $field }}">
                        <p class="mt-1.5 text-xs text-prussian-blue/50">{{ __('account.profile.address_hint') }}</p>
                        <x-input-error field="street" />
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="postal_code" class="{{ $label }}">{{ __('account.profile.postal_code') }}</label>
                            <input id="postal_code" type="text" name="postal_code" value="{{ old('postal_code', $user->postal_code) }}" class="{{ $field }}">
                            <x-input-error field="postal_code" />
                        </div>
                        <div>
                            <label for="city" class="{{ $label }}">{{ __('account.profile.city') }}</label>
                            <input id="city" type="text" name="city" value="{{ old('city', $user->city) }}" class="{{ $field }}">
                            <x-input-error field="city" />
                        </div>
                    </div>
                @endif
            </div>
            <button type="submit" class="mt-6 cursor-pointer rounded-full bg-ruby-red px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                {{ __('account.profile.submit') }}
            </button>
        </form>

        <form method="POST" action="{{ route('account.password.update') }}" class="rounded-2xl border border-prussian-blue/10 bg-white p-6 sm:p-8">
            @csrf
            @method('PUT')
            <h2 class="font-bold text-prussian-blue">{{ __('account.password.title') }}</h2>
            <div class="mt-5 space-y-5">
                <div>
                    <label for="current_password" class="{{ $label }}">{{ __('account.password.current') }}</label>
                    <input id="current_password" type="password" name="current_password" class="{{ $field }}" required autocomplete="current-password">
                    <x-input-error field="current_password" />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="password" class="{{ $label }}">{{ __('account.password.new') }}</label>
                        <input id="password" type="password" name="password" class="{{ $field }}" required minlength="8" autocomplete="new-password">
                        <p class="mt-1.5 text-xs text-prussian-blue/50">{{ __('auth.register.password_hint') }}</p>
                        <x-input-error field="password" />
                    </div>
                    <div>
                        <label for="password_confirmation" class="{{ $label }}">{{ __('account.password.confirm') }}</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" class="{{ $field }}" required minlength="8" autocomplete="new-password">
                    </div>
                </div>
            </div>
            <button type="submit" class="mt-6 cursor-pointer rounded-full bg-ruby-red px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                {{ __('account.password.submit') }}
            </button>
        </form>

        <form method="POST" action="{{ route('account.destroy') }}" data-confirm="{{ __('account.delete.confirm') }}" class="rounded-2xl border border-ruby-red/30 bg-ruby-red/5 p-6 sm:p-8">
            @csrf
            @method('DELETE')
            <h2 class="font-bold text-prussian-blue">{{ __('account.delete.title') }}</h2>
            <p class="mt-1 text-sm leading-relaxed text-prussian-blue/60">{{ __('account.delete.text') }}</p>
            <div class="mt-5 max-w-sm">
                <label for="delete_password" class="{{ $label }}">{{ __('account.delete.password') }}</label>
                <input id="delete_password" type="password" name="delete_password" class="{{ $field }}" required autocomplete="current-password">
                <x-input-error field="delete_password" />
            </div>
            <button type="submit" class="mt-6 cursor-pointer rounded-full border border-ruby-red bg-white px-6 py-2.5 text-sm font-semibold text-ruby-red transition hover:bg-ruby-red hover:text-white">
                {{ __('account.delete.submit') }}
            </button>
        </form>
    </div>
</x-dynamic-component>
