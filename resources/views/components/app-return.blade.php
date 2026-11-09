{{-- Alleen als deze pagina vanuit de iOS/Android-app is geopend (AppWebSessionController onthoudt waar de
     app draait): een balk terug naar de app. Een iPhone heeft geen terugknop, zonder deze balk zit je vast. --}}
@if (session('app_bundle'))
    <a href="{{ session('app_bundle') }}/" class="sticky top-0 z-50 flex items-center gap-2 bg-prussian-blue px-4 pb-3 text-sm font-semibold text-white" style="padding-top: max(0.75rem, env(safe-area-inset-top))">
        <i class="fa-solid fa-chevron-left fa-xs"></i> {{ __('app.back_to_app') }}
    </a>
@endif
