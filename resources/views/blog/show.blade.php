@php
    $cover = $post->coverUrl();
    $titled = $post->sections->filter(fn ($section) => filled($section->title));

    $schema = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $post->title,
        'description' => $post->metaDescription(),
        'mainEntityOfPage' => route('blog.show', $post->slug),
        'datePublished' => $post->published_at?->toAtomString(),
        'dateModified' => $post->updated_at->toAtomString(),
        'image' => $cover ? url($cover) : null,
        'inLanguage' => app()->getLocale(),
        'author' => ['@type' => 'Organization', 'name' => config('app.name', 'StudioMatch')],
        'publisher' => [
            '@type' => 'Organization',
            'name' => config('app.name', 'StudioMatch'),
            'logo' => ['@type' => 'ImageObject', 'url' => url('/logos/sm-primary-logo-blauw.png')],
        ],
    ]);
@endphp

<x-layout :title="$post->title"
          :description="$post->metaDescription()"
          :image="$cover ? url($cover) : null"
          type="article"
          :schema="$schema">
    <x-hero compact>
        <a href="{{ route('blog') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-white/70 transition hover:text-white">
            <i class="fa-solid fa-arrow-left fa-xs"></i> {{ __('blog.back') }}
        </a>

        <h1 class="mx-auto mt-5 max-w-3xl text-3xl font-bold leading-tight text-white sm:text-4xl">{{ $post->title }}</h1>

        <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-white/50">
            @if ($post->published_at)
                {{ $post->published_at->translatedFormat('j F Y') }} ·
            @endif
            {{ trans_choice('blog.reading_time', $post->readingMinutes()) }}
        </p>
    </x-hero>

    <section class="bg-dots py-16">
        <div class="mx-auto max-w-3xl px-6">
            @if ($preview)
                <p class="mb-8 flex items-start gap-3 rounded-2xl border border-amber-500/30 bg-amber-500/10 px-5 py-4 text-sm font-semibold text-amber-700">
                    <i class="fa-solid fa-eye-slash mt-0.5"></i>
                    <span>
                        @if ($post->isScheduled())
                            {{ __('blog.scheduled_notice', ['date' => $post->published_at->translatedFormat('j F Y H:i')]) }}
                        @else
                            {{ __('blog.draft_notice') }}
                        @endif
                    </span>
                </p>
            @endif

            @if ($cover)
                <img src="{{ $cover }}" alt="{{ $post->title }}" class="mb-10 w-full rounded-2xl border border-prussian-blue/10 object-cover">
            @endif

            @if ($post->excerpt)
                <p class="text-lg leading-relaxed text-prussian-blue/70">{{ $post->excerpt }}</p>
            @endif

            @if ($titled->count() >= 3)
                <nav class="mt-10 rounded-2xl border border-prussian-blue/10 bg-white p-6">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('blog.toc_title') }}</h2>
                    <ol class="mt-3 space-y-2 text-sm">
                        @foreach ($titled as $section)
                            <li class="flex gap-3">
                                <span class="font-bold text-ruby-red">{{ $loop->iteration }}</span>
                                <a href="#{{ $section->anchor() }}" class="text-prussian-blue/70 transition hover:text-ruby-red">{{ $section->title }}</a>
                            </li>
                        @endforeach
                    </ol>
                </nav>
            @endif

            <article class="mt-10 space-y-10">
                @foreach ($post->sections as $section)
                    <section>
                        @if (filled($section->title))
                            <h2 id="{{ $section->anchor() }}" class="scroll-mt-28 text-xl font-bold text-prussian-blue sm:text-2xl">{{ $section->title }}</h2>
                        @endif

                        @if (filled($section->body))
                            {{-- De opgeslagen HTML is bij het bewaren al teruggebracht tot een korte lijst veilige tags. --}}
                            <div class="sm-prose {{ filled($section->title) ? 'mt-3' : '' }}">{!! $section->body !!}</div>
                        @endif
                    </section>
                @endforeach
            </article>
        </div>
    </section>

    @if ($related->isNotEmpty())
        <section class="bg-white py-20">
            <div class="mx-auto max-w-7xl px-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h2 class="text-2xl font-bold text-prussian-blue">{{ __('blog.related_title') }}</h2>
                    <a href="{{ route('blog') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ruby-red transition hover:gap-3">
                        {{ __('blog.all_articles') }} <i class="fa-solid fa-arrow-right fa-xs"></i>
                    </a>
                </div>

                <div class="mt-8 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $item)
                        <x-blog-card :post="$item" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="bg-prussian-blue py-16">
        <div class="mx-auto max-w-3xl px-6 text-center">
            <h2 class="text-2xl font-bold text-white sm:text-3xl">{{ __('blog.cta.title') }}</h2>
            <p class="mt-3 text-white/60">{{ __('blog.cta.text') }}</p>
            <a href="{{ route('studios') }}" class="mt-7 inline-flex items-center gap-2 rounded-full bg-ruby-red px-7 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                <i class="fa-solid fa-magnifying-glass fa-sm"></i> {{ __('blog.cta.button') }}
            </a>
        </div>
    </section>
</x-layout>
