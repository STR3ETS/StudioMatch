<x-auth-layout :title="__('auth.reset.title')">
    @php
        $label = 'block text-xs font-bold uppercase tracking-wide text-prussian-blue/50';
        $field = 'mt-2 w-full rounded-xl border border-prussian-blue/15 px-4 py-2.5 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none';
    @endphp

    <h1 class="text-3xl font-bold text-prussian-blue">{{ __('auth.reset.title') }}</h1>
    <p class="mt-2 text-prussian-blue/60">{{ __('auth.reset.subtitle') }}</p>

    <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="{{ $label }}">{{ __('auth.fields.email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->query('email')) }}" placeholder="{{ __('auth.fields.email_placeholder') }}" class="{{ $field }}" required autofocus>
            <x-input-error field="email" />
        </div>

        <div>
            <label for="password" class="{{ $label }}">{{ __('auth.reset.password') }}</label>
            <input id="password" type="password" name="password" placeholder="{{ __('auth.fields.password_placeholder') }}" class="{{ $field }}" required minlength="8">
            <p class="mt-1.5 text-xs text-prussian-blue/50">{{ __('auth.register.password_hint') }}</p>
            <x-input-error field="password" />
        </div>

        <div>
            <label for="password_confirmation" class="{{ $label }}">{{ __('auth.reset.password_confirm') }}</label>
            <input id="password_confirmation" type="password" name="password_confirmation" placeholder="{{ __('auth.fields.password_placeholder') }}" class="{{ $field }}" required minlength="8">
        </div>

        <button type="submit" class="w-full cursor-pointer rounded-full bg-ruby-red py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
            {{ __('auth.reset.submit') }}
        </button>
    </form>

    <a href="{{ route('login') }}" class="mt-8 flex items-center justify-center gap-1.5 text-sm font-semibold text-prussian-blue/60 transition hover:text-prussian-blue">
        <i class="fa-solid fa-arrow-left fa-xs"></i> {{ __('auth.forgot.back') }}
    </a>
</x-auth-layout>
