<x-error-page code="401" :heading="__('errors.401.heading')" :text="__('errors.401.text')">
    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-semibold text-ruby-red shadow-sm transition hover:bg-white/90">
        <i class="fa-solid fa-right-to-bracket fa-sm"></i> {{ __('errors.login') }}
    </a>
    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-full border border-white/25 px-6 py-3 text-sm font-semibold text-white/85 transition hover:bg-white/10 hover:text-white">
        {{ __('errors.home') }}
    </a>
</x-error-page>
