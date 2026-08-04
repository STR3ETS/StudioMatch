<x-auth-layout :title="__('auth.login.title')">
    @php
        $label = 'block text-xs font-bold uppercase tracking-wide text-prussian-blue/50';
        $field = 'mt-2 w-full rounded-xl border border-prussian-blue/15 px-4 py-2.5 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none';
    @endphp

    <h1 class="text-3xl font-bold text-prussian-blue">{{ __('auth.login.title') }}</h1>
    <p class="mt-2 text-prussian-blue/60">{{ __('auth.login.subtitle') }}</p>

    @if (session('status'))
        <p class="mt-6 rounded-xl bg-emerald-500/10 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="email" class="{{ $label }}">{{ __('auth.fields.email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('auth.fields.email_placeholder') }}" class="{{ $field }}" required autofocus>
            <x-input-error field="email" />
        </div>

        <div>
            <label for="password" class="{{ $label }}">{{ __('auth.fields.password') }}</label>
            <input id="password" type="password" name="password" placeholder="{{ __('auth.fields.password_placeholder') }}" class="{{ $field }}" required>
            <x-input-error field="password" />
        </div>

        <div class="flex items-center justify-between gap-4">
            <label class="flex cursor-pointer items-center gap-2.5 text-sm text-prussian-blue">
                <input type="checkbox" name="remember" class="h-4 w-4 rounded border-prussian-blue/30 accent-ruby-red">
                {{ __('auth.login.remember') }}
            </label>
            <a href="{{ route('password.request') }}" class="text-sm font-semibold text-ruby-red hover:underline">{{ __('auth.login.forgot') }}</a>
        </div>

        <button type="submit" class="w-full cursor-pointer rounded-full bg-ruby-red py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
            {{ __('auth.login.submit') }}
        </button>
    </form>

    <p class="mt-8 text-center text-sm text-prussian-blue/60">
        {{ __('auth.login.no_account') }}
        <a href="{{ route('register') }}" class="font-semibold text-ruby-red hover:underline">{{ __('auth.login.register') }}</a>
    </p>
</x-auth-layout>
