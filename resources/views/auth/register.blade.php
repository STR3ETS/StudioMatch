<x-auth-layout :title="__('auth.register.title')">
    @php
        $label = 'block text-xs font-bold uppercase tracking-wide text-prussian-blue/50';
        $field = 'mt-2 w-full rounded-xl border border-prussian-blue/15 px-4 py-2.5 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none';

        $role = old('role', request('rol') === 'verhuurder' ? 'verhuurder' : 'artiest');
    @endphp

    <h1 class="text-3xl font-bold text-prussian-blue">{{ __('auth.register.title') }}</h1>
    <p class="mt-2 text-prussian-blue/60">{{ __('auth.register.subtitle') }}</p>

    <form method="POST" action="{{ route('register.store') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <span class="{{ $label }}">{{ __('auth.register.role') }}</span>
            <div class="mt-2 grid grid-cols-2 gap-2">
                <label>
                    <input type="radio" name="role" value="artiest" class="peer sr-only" @checked($role === 'artiest')>
                    <span class="flex cursor-pointer flex-col rounded-xl border border-prussian-blue/15 px-4 py-3 transition peer-checked:border-ruby-red peer-checked:bg-ruby-red/5">
                        <span class="flex items-center gap-2 text-sm font-semibold text-prussian-blue"><i class="fa-solid fa-music text-xs text-ruby-red"></i> {{ __('auth.register.role_artist') }}</span>
                        <span class="mt-0.5 text-xs text-prussian-blue/50">{{ __('auth.register.role_artist_sub') }}</span>
                    </span>
                </label>
                <label>
                    <input type="radio" name="role" value="verhuurder" class="peer sr-only" @checked($role === 'verhuurder')>
                    <span class="flex cursor-pointer flex-col rounded-xl border border-prussian-blue/15 px-4 py-3 transition peer-checked:border-ruby-red peer-checked:bg-ruby-red/5">
                        <span class="flex items-center gap-2 text-sm font-semibold text-prussian-blue"><i class="fa-solid fa-door-open text-xs text-ruby-red"></i> {{ __('auth.register.role_host') }}</span>
                        <span class="mt-0.5 text-xs text-prussian-blue/50">{{ __('auth.register.role_host_sub') }}</span>
                    </span>
                </label>
            </div>
            <x-input-error field="role" />
        </div>

        <div>
            <label for="name" class="{{ $label }}">{{ __('auth.fields.name') }}</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('auth.fields.name_placeholder') }}" class="{{ $field }}" required>
            <x-input-error field="name" />
        </div>

        <div>
            <label for="email" class="{{ $label }}">{{ __('auth.fields.email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('auth.fields.email_placeholder') }}" class="{{ $field }}" required>
            <x-input-error field="email" />
        </div>

        <div>
            <label for="password" class="{{ $label }}">{{ __('auth.fields.password') }}</label>
            <input id="password" type="password" name="password" placeholder="{{ __('auth.fields.password_placeholder') }}" class="{{ $field }}" required minlength="8">
            <p class="mt-1.5 text-xs text-prussian-blue/50">{{ __('auth.register.password_hint') }}</p>
            <x-input-error field="password" />
        </div>

        <label class="flex cursor-pointer items-start gap-2.5 text-sm text-prussian-blue/80">
            <input type="checkbox" name="terms" value="1" class="mt-0.5 h-4 w-4 shrink-0 rounded border-prussian-blue/30 accent-ruby-red" required>
            <span>{!! __('auth.register.terms', [
                'terms' => '<a href="#" class="font-semibold text-ruby-red hover:underline">' . __('auth.register.terms_link') . '</a>',
                'privacy' => '<a href="#" class="font-semibold text-ruby-red hover:underline">' . __('auth.register.privacy_link') . '</a>',
            ]) !!}</span>
        </label>
        <x-input-error field="terms" class="!mt-1.5" />

        <button type="submit" class="w-full cursor-pointer rounded-full bg-ruby-red py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
            {{ __('auth.register.submit') }}
        </button>
    </form>

    <p class="mt-8 text-center text-sm text-prussian-blue/60">
        {{ __('auth.register.have_account') }}
        <a href="{{ route('login') }}" class="font-semibold text-ruby-red hover:underline">{{ __('auth.register.login') }}</a>
    </p>
</x-auth-layout>
