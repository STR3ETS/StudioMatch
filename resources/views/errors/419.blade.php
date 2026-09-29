<x-error-page code="419" :heading="__('errors.419.heading')" :text="__('errors.419.text')">
    <button type="button" data-history-back class="inline-flex cursor-pointer items-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-semibold text-ruby-red shadow-sm transition hover:bg-white/90">
        <i class="fa-solid fa-arrow-left fa-sm"></i> {{ __('errors.back') }}
    </button>
    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-full border border-white/25 px-6 py-3 text-sm font-semibold text-white/85 transition hover:bg-white/10 hover:text-white">
        {{ __('errors.home') }}
    </a>
</x-error-page>
