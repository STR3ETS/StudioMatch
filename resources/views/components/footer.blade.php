@php
    $cities = ['Amsterdam', 'Rotterdam', 'Utrecht', 'Den Haag', 'Groningen', 'Tilburg'];

    $socials = [
        ['icon' => 'fa-instagram', 'label' => 'Instagram'],
        ['icon' => 'fa-tiktok', 'label' => 'TikTok'],
        ['icon' => 'fa-facebook-f', 'label' => 'Facebook'],
        ['icon' => 'fa-linkedin-in', 'label' => 'LinkedIn'],
    ];
@endphp

<footer class="bg-ruby-red text-white">
    <div class="mx-auto max-w-7xl px-6 py-16">
        <div class="grid grid-cols-2 gap-8 sm:grid-cols-3 lg:grid-cols-6">
            {{-- Brand --}}
            <div class="col-span-2 sm:col-span-3 lg:col-span-2">
                <img src="/logos/sm-primary-logo-wit.png" alt="StudioMatch" class="h-10 w-auto">
                <p class="mt-4 text-sm font-semibold italic text-white">{{ __('footer.tagline') }}</p>
                <p class="mt-2 max-w-xs text-sm leading-relaxed text-white/70">{{ __('footer.description') }}</p>
                <div class="mt-6 flex items-center gap-3">
                    @foreach ($socials as $social)
                        <a href="#" aria-label="{{ $social['label'] }}"
                           class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white/80 transition hover:-translate-y-0.5 hover:bg-white/20 hover:text-white">
                            <i class="fa-brands {{ $social['icon'] }} fa-sm"></i>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Ontdekken --}}
            <div>
                <h3 class="text-sm font-bold">{{ __('footer.discover.title') }}</h3>
                <ul class="mt-4 space-y-3 text-sm text-white/60">
                    <li><a href="#" class="transition hover:text-white">{{ __('footer.discover.search') }}</a></li>
                    <li><a href="{{ route('how') }}" class="transition hover:text-white">{{ __('footer.discover.how_it_works') }}</a></li>
                    <li><a href="{{ route('faq') }}" class="transition hover:text-white">{{ __('footer.discover.faq') }}</a></li>
                </ul>
            </div>

            {{-- Voor studio's --}}
            <div>
                <h3 class="text-sm font-bold">{{ __('footer.hosts.title') }}</h3>
                <ul class="mt-4 space-y-3 text-sm text-white/60">
                    <li><a href="{{ route('hosts') }}" class="transition hover:text-white">{{ __('footer.hosts.become_host') }}</a></li>
                    <li><a href="#" class="transition hover:text-white">{{ __('footer.hosts.how_hosting_works') }}</a></li>
                    <li><a href="#" class="transition hover:text-white">{{ __('footer.hosts.pricing') }}</a></li>
                </ul>
            </div>

            {{-- Bedrijf --}}
            <div>
                <h3 class="text-sm font-bold">{{ __('footer.company.title') }}</h3>
                <ul class="mt-4 space-y-3 text-sm text-white/60">
                    <li><a href="#" class="transition hover:text-white">{{ __('footer.company.about') }}</a></li>
                    <li><a href="{{ route('contact') }}" class="transition hover:text-white">{{ __('footer.company.contact') }}</a></li>
                </ul>
            </div>

            {{-- Populaire steden --}}
            <div>
                <h3 class="text-sm font-bold">{{ __('footer.cities.title') }}</h3>
                <ul class="mt-4 space-y-3 text-sm text-white/60">
                    @foreach ($cities as $city)
                        <li><a href="#" class="transition hover:text-white">{{ $city }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    {{-- Subfooter --}}
    <div class="border-t border-white/10">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-6 py-6 lg:items-center lg:justify-between">
            <div class="w-full flex flex-wrap items-center justify-between gap-y-3 text-xs text-white/50">
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                    <span>&copy; {{ date('Y') }} StudioMatch. {{ __('footer.rights') }}</span>
                    <a href="#" class="transition hover:text-white">{{ __('footer.legal.terms') }}</a>
                    <a href="#" class="transition hover:text-white">{{ __('footer.legal.privacy') }}</a>
                    <a href="#" class="transition hover:text-white">{{ __('footer.legal.disclaimer') }}</a>
                    <a href="#" class="transition hover:text-white">{{ __('footer.legal.cookies') }}</a>
                </div>
                <div class="flex items-center gap-5">
                    <span class="hidden sm:inline">KvK 94893527</span>
                    <span class="hidden sm:inline">BTW NL866927827B01</span>
                </div>
            </div>

            <p class="shrink-0 text-xs text-white/50 w-full">
                {{ __('footer.made_with') }}
                <a href="https://eazyonline.nl" target="_blank" rel="noopener"
                   class="font-semibold text-white transition hover:underline ml-0.25">Eazyonline</a>
            </p>
        </div>
    </div>
</footer>
