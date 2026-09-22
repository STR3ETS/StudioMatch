<x-layout :title="__('blog.meta_title')" :description="__('blog.meta_description')">
    <x-hero compact>
        <h1 class="text-4xl font-bold text-white sm:text-5xl">{{ __('blog.hero.heading') }}</h1>
        <p class="mx-auto mt-4 max-w-2xl text-white/60">{{ __('blog.hero.subtitle') }}</p>
    </x-hero>

    <section class="bg-dots py-20">
        <div class="mx-auto max-w-7xl px-6">
            @if (! $featured)
                <p class="rounded-2xl border border-dashed border-prussian-blue/20 bg-white px-6 py-16 text-center text-prussian-blue/50">
                    {{ __('blog.empty') }}
                </p>
            @else
                <a href="{{ route('blog.show', $featured->slug) }}"
                   class="group grid overflow-hidden rounded-3xl border border-prussian-blue/10 bg-white transition hover:shadow-xl hover:shadow-prussian-blue/10 lg:grid-cols-2">
                    <span class="block aspect-[16/10] overflow-hidden bg-prussian-blue/5 lg:aspect-auto lg:h-full">
                        @if ($featured->coverUrl())
                            <img src="{{ $featured->coverUrl() }}" alt="{{ $featured->title }}"
                                 class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
                        @else
                            <span class="flex h-full min-h-56 w-full items-center justify-center text-prussian-blue/15">
                                <i class="fa-solid fa-newspaper text-6xl"></i>
                            </span>
                        @endif
                    </span>

                    <span class="flex flex-col justify-center p-7 sm:p-10">
                        <span class="text-xs font-bold uppercase tracking-wide text-ruby-red">{{ __('blog.featured') }}</span>

                        <span class="mt-3 text-2xl font-bold leading-tight text-prussian-blue transition group-hover:text-ruby-red sm:text-3xl">{{ $featured->title }}</span>

                        @if ($featured->excerpt)
                            <span class="mt-3 text-prussian-blue/60">{{ $featured->excerpt }}</span>
                        @endif

                        <span class="mt-5 text-xs font-semibold uppercase tracking-wide text-prussian-blue/40">
                            @if ($featured->published_at)
                                {{ $featured->published_at->translatedFormat('j F Y') }} ·
                            @endif
                            {{ trans_choice('blog.reading_time', $featured->readingMinutes()) }}
                        </span>

                        <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-ruby-red">
                            {{ __('blog.read_more') }} <i class="fa-solid fa-arrow-right fa-xs transition group-hover:translate-x-1"></i>
                        </span>
                    </span>
                </a>

                @if ($posts->isNotEmpty())
                    <div data-loadmore data-loadmore-initial="9" data-loadmore-step="9" class="mt-12">
                        <div data-loadmore-grid class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($posts as $post)
                                <x-blog-card :post="$post" />
                            @endforeach
                        </div>

                        <div class="mt-10 flex justify-center">
                            <button type="button" data-loadmore-btn class="cursor-pointer rounded-full border border-prussian-blue/20 px-6 py-2.5 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">
                                {{ __('blog.load_more') }}
                            </button>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </section>
</x-layout>
