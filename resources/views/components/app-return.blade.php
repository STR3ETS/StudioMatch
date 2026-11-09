@props(['title' => null])

{{-- Alleen als deze pagina in de iOS/Android-app openstaat (InApp): dezelfde navy kop als de schermen van de
     app, met een knop terug naar de app, de naam van de pagina en het merk. De layouts laten dan hun eigen
     navigatie weg en zetten .in-app op <html> (resources/css/in-app.css geeft de pagina de stijl van de app).
     Een iPhone heeft geen terugknop, zonder deze kop zit je vast. Zonder sessie (een foutpagina) weet de
     route app.back waar de app draait. Op het dashboard neemt de kop de echte paginatitel over (de h1). --}}
@inapp
    @php($origin = \App\Support\InApp::origin())
    {{-- Ook vóór de stylesheet er is: de webview van de app is navy (capacitor.config.ts). --}}
    <style>html { background: #f4f4f7; }</style>
    <header data-app-return class="sticky top-0 z-[1100] bg-prussian-blue text-white" style="padding-top: env(safe-area-inset-top)">
        <div class="flex h-14 items-center gap-1 pl-1 pr-4">
            <a href="{{ $origin ? $origin.'/' : route('app.back') }}" aria-label="{{ __('app.back_to_app') }}" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full active:bg-white/10">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            {{-- Alleen het eerste deel van een titel als "Studio - Ruimte · Plaats": de kop is smal. --}}
            <p data-app-title class="min-w-0 flex-1 truncate text-lg font-bold">{{ filled($title) ? \Illuminate\Support\Str::before($title, ' · ') : config('app.name', 'StudioMatch') }}</p>
            <img src="/logos/sm-mark-wit.png" alt="StudioMatch" class="h-7 w-7 shrink-0">
        </div>
    </header>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const heading = document.querySelector('[data-dash-main] h1')?.textContent.trim();
            if (heading) document.querySelector('[data-app-title]').textContent = heading;
        });
    </script>
@endinapp
