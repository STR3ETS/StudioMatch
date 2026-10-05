{{--
    Google reCAPTCHA v2. Zonder sleutels in de omgeving rendert er niets en slaat ook de
    controle op de server zichzelf over, zodat het formulier lokaal blijft werken.
--}}
@if (\App\Rules\Recaptcha::enabled())
    <div {{ $attributes->merge(['class' => 'mt-6']) }}>
        <div class="g-recaptcha"
             data-sitekey="{{ config('services.recaptcha.site_key') }}"
             data-theme="light"></div>

        <x-input-error field="g-recaptcha-response" class="mt-2" />

        <noscript>
            <p class="mt-2 text-xs leading-relaxed text-ruby-red">{{ __('contact.form.recaptcha_noscript') }}</p>
        </noscript>
    </div>

    {{-- Alleen op deze pagina geladen, niet op de rest van de site. --}}
    <script src="https://www.google.com/recaptcha/api.js?hl={{ app()->getLocale() }}" async defer></script>
@endif
