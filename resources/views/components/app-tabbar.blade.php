{{-- De onderbalk van de app, ook op een websitepagina die in de iOS/Android-app openstaat: dezelfde tabs
     (AppMenu), dezelfde opmaak als in mobile/app/index.html. Een tab opent dat scherm in de app; de tab waar
     deze pagina bij hoort (AppMenu::tabFor) licht op. Alleen ingelogd: een gast heeft in de app geen tabs. --}}
@inapp
    @auth
        @php
            $user = auth()->user();
            $menu = \App\Support\AppMenu::for($user);
            $counts = \App\Support\AppMenu::counts($user);
            $active = \App\Support\AppMenu::tabFor($user, request()->route()?->getName());
            $origin = \App\Support\InApp::origin() ?? \App\Support\InApp::guessOrigin();
        @endphp
        <nav data-app-tabbar class="fixed inset-x-0 bottom-0 z-[1100] border-t border-gray-200 bg-white" style="padding-bottom: env(safe-area-inset-bottom)">
            <div class="grid" style="grid-template-columns: repeat({{ count($menu['tabs']) }}, minmax(0, 1fr))">
                @foreach ($menu['tabs'] as $tab)
                    @php($count = isset($tab['count']) ? ($counts[$tab['count']] ?? 0) : 0)
                    <a href="{{ $origin }}/#/{{ $tab['screen'] }}"
                       @class([
                           'relative flex flex-col items-center gap-1 py-2.5 text-[11px] font-semibold',
                           'text-ruby-red' => $active === $tab['screen'],
                           'text-charcoal-blue' => $active !== $tab['screen'],
                       ])>
                        <i class="fa-solid {{ $tab['icon'] }} flex h-5 w-5 items-center justify-center text-[1.15rem]"></i>
                        <span>{{ $tab['label'] }}</span>
                        @if ($count > 0)
                            <span class="absolute top-1.5 left-1/2 ml-2 min-w-5 rounded-full bg-ruby-red px-1.5 text-center text-[11px] leading-5 font-bold text-white">{{ $count }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </nav>
    @endauth
@endinapp
