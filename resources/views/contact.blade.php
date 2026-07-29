<x-layout :title="__('contact.meta_title')" :description="__('contact.meta_description')">
    @php
        $field = 'mt-2 w-full rounded-xl border border-prussian-blue/15 px-4 py-2.5 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none';
        $label = 'block text-xs font-bold uppercase tracking-wide text-prussian-blue/50';

        $quickLinks = [
            'faq' => route('how'),
            'hosts' => route('hosts'),
            'problem' => route('how'),
        ];

        $socials = [
            ['icon' => 'fa-instagram', 'label' => 'Instagram'],
            ['icon' => 'fa-tiktok', 'label' => 'TikTok'],
            ['icon' => 'fa-facebook-f', 'label' => 'Facebook'],
            ['icon' => 'fa-linkedin-in', 'label' => 'LinkedIn'],
        ];
    @endphp

    {{-- ===== Hero ===== --}}
    <x-hero compact>
        <h1 class="text-4xl font-bold text-white sm:text-5xl">{{ __('contact.hero.heading') }}</h1>
        <p class="mx-auto mt-4 max-w-2xl text-white/60">{{ __('contact.hero.subtitle') }}</p>
    </x-hero>

    {{-- ===== Snelle hulp ===== --}}
    <section class="py-16">
        <div class="mx-auto max-w-7xl px-6">
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach (['faq', 'hosts', 'problem'] as $key)
                    <a href="{{ $quickLinks[$key] }}" class="group rounded-2xl border border-prussian-blue/10 p-6 transition hover:-translate-y-1 hover:shadow-lg hover:shadow-prussian-blue/5">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-ruby-red/10 text-ruby-red"><i class="fa-solid {{ __('contact.quick.' . $key . '.icon') }}"></i></span>
                        <h2 class="mt-4 font-bold text-prussian-blue">{{ __('contact.quick.' . $key . '.title') }}</h2>
                        <p class="mt-2 text-sm leading-relaxed text-prussian-blue/60">{{ __('contact.quick.' . $key . '.text') }}</p>
                        <span class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-ruby-red">
                            {{ __('contact.quick.' . $key . '.link') }}
                            <i class="fa-solid fa-arrow-right fa-xs transition-transform group-hover:translate-x-1"></i>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===== Formulier + contactinfo ===== --}}
    <section class="bg-dots pb-20">
        <div class="mx-auto max-w-7xl px-6">
            <div class="grid gap-8 lg:grid-cols-[1fr_380px]">
                {{-- Formulier --}}
                <form action="#" class="rounded-3xl border border-prussian-blue/10 bg-white p-8 shadow-sm">
                    <h2 class="text-2xl font-bold text-prussian-blue">{{ __('contact.form.title') }}</h2>

                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="contact-name" class="{{ $label }}">{{ __('contact.form.name') }}</label>
                            <input id="contact-name" type="text" name="name" placeholder="{{ __('contact.form.name_placeholder') }}" class="{{ $field }}">
                        </div>
                        <div>
                            <label for="contact-email" class="{{ $label }}">{{ __('contact.form.email') }}</label>
                            <input id="contact-email" type="email" name="email" placeholder="{{ __('contact.form.email_placeholder') }}" class="{{ $field }}">
                        </div>
                    </div>

                    <div class="mt-5">
                        <label for="contact-subject" class="{{ $label }}">{{ __('contact.form.subject') }}</label>
                        <select id="contact-subject" name="subject" class="{{ $field }} cursor-pointer">
                            @foreach (__('contact.form.subjects') as $value => $subject)
                                <option value="{{ $value }}">{{ $subject }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-5">
                        <label for="contact-message" class="{{ $label }}">{{ __('contact.form.message') }}</label>
                        <textarea id="contact-message" name="message" rows="6" placeholder="{{ __('contact.form.message_placeholder') }}" class="{{ $field }} resize-y"></textarea>
                    </div>

                    <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-prussian-blue/50"><i class="fa-solid fa-lock mr-1 text-prussian-blue/30"></i> {{ __('contact.form.privacy') }}</p>
                        <button type="submit" class="cursor-pointer rounded-full bg-ruby-red px-8 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">{{ __('contact.form.submit') }}</button>
                    </div>
                </form>

                {{-- Contactinfo --}}
                <div class="relative overflow-hidden rounded-3xl bg-prussian-blue p-8 text-white">
                    <x-floating-icons />
                    <div class="relative z-10 flex h-full flex-col">
                        <h2 class="text-xl font-bold">{{ __('contact.info.title') }}</h2>

                        <div class="mt-6 flex items-center gap-4">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/10"><i class="fa-solid fa-envelope"></i></span>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-white/50">{{ __('contact.info.email_label') }}</p>
                                <a href="mailto:{{ __('contact.info.email') }}" class="font-semibold transition hover:text-ruby-red">{{ __('contact.info.email') }}</a>
                            </div>
                        </div>

                        <p class="mt-5 flex items-center gap-2 text-sm text-white/60"><i class="fa-solid fa-clock text-white/40"></i> {{ __('contact.info.response') }}</p>

                        <div class="mt-8 border-t border-white/10 pt-6">
                            <p class="text-xs font-bold uppercase tracking-wide text-white/50">{{ __('contact.info.socials_title') }}</p>
                            <div class="mt-3 flex items-center gap-3">
                                @foreach ($socials as $social)
                                    <a href="#" aria-label="{{ $social['label'] }}" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white/80 transition hover:-translate-y-0.5 hover:bg-white/20 hover:text-white">
                                        <i class="fa-brands {{ $social['icon'] }} fa-sm"></i>
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-auto border-t border-white/10 pt-6 text-xs text-white/50 max-lg:mt-8">
                            <p class="font-semibold text-white/70">{{ __('contact.info.company') }}</p>
                            <p class="mt-1">{{ __('contact.info.kvk') }}</p>
                            <p>{{ __('contact.info.btw') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layout>
