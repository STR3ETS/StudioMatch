<div data-cookie-banner class="fixed inset-x-4 bottom-4 z-[1250] mx-auto hidden max-w-xl rounded-2xl border border-prussian-blue/10 bg-white p-5 shadow-2xl shadow-prussian-blue/20">
    <div class="flex items-start gap-3">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-ruby-red/10 text-ruby-red"><i class="fa-solid fa-cookie-bite fa-sm"></i></span>
        <p class="text-sm leading-relaxed text-prussian-blue/70">
            {{ __('cookies.text') }}
            <a href="{{ route('legal.cookies') }}" class="font-semibold text-ruby-red hover:underline">{{ __('cookies.link') }}</a>
        </p>
    </div>
    <div class="mt-4 flex flex-wrap gap-2">
        <button type="button" data-cookie-accept class="cursor-pointer rounded-full bg-ruby-red px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">{{ __('cookies.accept') }}</button>
        <button type="button" data-cookie-decline class="cursor-pointer rounded-full border border-prussian-blue/20 px-5 py-2.5 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">{{ __('cookies.decline') }}</button>
    </div>
</div>
<script>
    (() => {
        const banner = document.querySelector('[data-cookie-banner]');
        if (! banner || localStorage.getItem('sm-cookie-consent')) return;
        banner.classList.remove('hidden');
        const close = (value) => {
            localStorage.setItem('sm-cookie-consent', value);
            banner.remove();
        };
        banner.querySelector('[data-cookie-accept]').addEventListener('click', () => close('all'));
        banner.querySelector('[data-cookie-decline]').addEventListener('click', () => close('essential'));
    })();
</script>
