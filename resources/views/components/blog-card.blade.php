@props(['post'])

<a href="{{ route('blog.show', $post->slug) }}"
   class="group flex h-full flex-col overflow-hidden rounded-2xl border border-prussian-blue/10 bg-white transition hover:-translate-y-1 hover:shadow-lg hover:shadow-prussian-blue/10">
    <span class="block aspect-[16/10] overflow-hidden bg-prussian-blue/5">
        @if ($post->coverUrl())
            <img src="{{ $post->coverUrl() }}" alt="{{ $post->title }}" loading="lazy"
                 class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
        @else
            <span class="flex h-full w-full items-center justify-center text-prussian-blue/15">
                <i class="fa-solid fa-newspaper text-4xl"></i>
            </span>
        @endif
    </span>

    <span class="flex flex-1 flex-col p-5">
        <span class="text-xs font-semibold uppercase tracking-wide text-prussian-blue/40">
            @if ($post->published_at)
                {{ $post->published_at->translatedFormat('j F Y') }} ·
            @endif
            {{ trans_choice('blog.reading_time', $post->readingMinutes()) }}
        </span>

        <span class="mt-2 text-lg font-bold leading-snug text-prussian-blue transition group-hover:text-ruby-red">{{ $post->title }}</span>

        @if ($post->excerpt)
            <span class="mt-2 line-clamp-3 text-sm leading-relaxed text-prussian-blue/60">{{ $post->excerpt }}</span>
        @endif

        <span class="mt-auto inline-flex items-center gap-2 pt-4 text-sm font-semibold text-ruby-red">
            {{ __('blog.read_more') }} <i class="fa-solid fa-arrow-right fa-xs transition group-hover:translate-x-1"></i>
        </span>
    </span>
</a>
