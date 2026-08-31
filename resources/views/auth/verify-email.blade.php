<x-auth-layout :title="__('auth.verify.title')">
    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-ruby-red/10 text-xl text-ruby-red">
        <i class="fa-solid fa-envelope-open-text"></i>
    </span>

    <h1 class="mt-6 text-center text-3xl font-bold text-prussian-blue">{{ __('auth.verify.title') }}</h1>
    <p class="mt-3 text-center text-prussian-blue/60">{{ __('auth.verify.subtitle', ['email' => auth()->user()->email]) }}</p>

    <x-auth-steps :current="2" class="mt-8" />

    @if (session('status'))
        <p class="mt-6 rounded-xl bg-emerald-500/10 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</p>
    @endif

    <p class="mt-6 flex items-start gap-2.5 rounded-xl border border-ruby-red/30 bg-ruby-red/5 px-4 py-3 text-sm leading-relaxed text-prussian-blue/80">
        <i class="fa-solid fa-lock mt-0.5 text-ruby-red"></i>
        <span>{{ __('auth.verify.blocked') }}</span>
    </p>

    <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
        @csrf
        <button type="submit" class="w-full cursor-pointer rounded-full bg-ruby-red py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
            <i class="fa-solid fa-paper-plane fa-sm mr-1.5"></i>{{ __('auth.verify.resend') }}
        </button>
    </form>

    <p class="mt-4 text-center text-xs text-prussian-blue/50">{{ __('auth.verify.spam_hint') }}</p>

    <p class="mt-8 text-center text-sm text-prussian-blue/60">
        {{ __('auth.verify.wrong_email') }}
        <button type="submit" form="verify-logout" class="cursor-pointer font-semibold text-ruby-red hover:underline">{{ __('auth.verify.logout') }}</button>
    </p>
    <form id="verify-logout" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>
</x-auth-layout>
