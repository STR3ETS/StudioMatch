<x-admin-layout :title="__('admin.blog.title')" active="blog">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-prussian-blue">{{ __('admin.blog.title') }}</h1>
            <p class="mt-2 text-prussian-blue/60">{{ __('admin.blog.subtitle') }}</p>
        </div>
        <a href="{{ route('admin.posts.create') }}" class="inline-flex items-center gap-2 rounded-full bg-ruby-red px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
            <i class="fa-solid fa-plus fa-sm"></i> {{ __('admin.blog.new') }}
        </a>
    </div>

    <form method="GET" action="{{ route('admin.posts.index') }}" class="mt-6">
        <select name="status" onchange="this.form.requestSubmit()" class="cursor-pointer rounded-xl border border-prussian-blue/15 bg-white px-3 py-2 text-sm font-medium text-prussian-blue focus:border-prussian-blue/40 focus:outline-none">
            <option value="">{{ __('admin.blog.all_statuses') }}</option>
            @foreach (\App\Enums\PostStatus::cases() as $case)
                <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}</option>
            @endforeach
        </select>
    </form>

    @if ($posts->isEmpty())
        <p class="mt-6 rounded-xl border border-dashed border-prussian-blue/20 bg-white px-4 py-8 text-center text-sm text-prussian-blue/50">{{ __('admin.blog.empty') }}</p>
    @else
        <div class="mt-6 overflow-hidden rounded-2xl border border-prussian-blue/10 bg-white">
            <div class="overflow-x-auto">
                {{-- Op een telefoon een lijst (titel en status, de rest als regel eronder, knoppen op een eigen rij), vanaf md de tabel. --}}
                <table class="block w-full text-sm md:table">
                    <thead class="hidden md:table-header-group">
                        <tr class="border-b border-prussian-blue/10 text-left text-xs font-bold uppercase tracking-wide text-prussian-blue/50">
                            <th class="px-5 py-3.5">{{ __('admin.blog.col_title') }}</th>
                            <th class="px-5 py-3.5">{{ __('admin.blog.col_status') }}</th>
                            <th class="px-5 py-3.5">{{ __('admin.blog.col_sections') }}</th>
                            <th class="px-5 py-3.5">{{ __('admin.blog.col_author') }}</th>
                            <th class="px-5 py-3.5">{{ __('admin.blog.col_updated') }}</th>
                            <th class="px-5 py-3.5"></th>
                        </tr>
                    </thead>
                    <tbody class="block md:table-row-group">
                        @foreach ($posts as $post)
                            <tr class="flex flex-wrap items-start gap-3 border-b border-prussian-blue/5 px-4 py-3.5 last:border-0 md:table-row md:p-0">
                                <td class="block min-w-0 flex-1 md:table-cell md:px-5 md:py-3.5">
                                    <a href="{{ route('admin.posts.edit', $post) }}" class="font-semibold text-prussian-blue transition hover:text-ruby-red">{{ $post->title }}</a>
                                    <p class="break-all text-xs text-prussian-blue/50 md:break-normal">/blog/{{ $post->slug }}</p>
                                    <p class="mt-1 text-xs text-prussian-blue/70 md:hidden">
                                        {{ collect([
                                            trans_choice('admin.blog.section_count', $post->sections_count),
                                            $post->author?->name,
                                            $post->updated_at->translatedFormat('j M Y'),
                                        ])->filter()->implode(', ') }}
                                    </p>
                                </td>
                                <td class="block shrink-0 text-right md:table-cell md:px-5 md:py-3.5 md:text-left">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $post->status->badgeClasses() }}">
                                        {{ $post->status->label() }}
                                    </span>
                                    @if ($post->isScheduled())
                                        <p class="mt-1 text-xs text-prussian-blue/50">
                                            <i class="fa-regular fa-clock fa-xs"></i>
                                            {{ __('admin.blog.scheduled') }} {{ $post->published_at->translatedFormat('j M Y H:i') }}
                                        </p>
                                    @endif
                                </td>
                                <td class="hidden px-5 py-3.5 text-prussian-blue/70 md:table-cell">{{ trans_choice('admin.blog.section_count', $post->sections_count) }}</td>
                                <td class="hidden px-5 py-3.5 text-prussian-blue/70 md:table-cell">{{ $post->author?->name ?? '-' }}</td>
                                <td class="hidden px-5 py-3.5 text-prussian-blue/70 md:table-cell">{{ $post->updated_at->translatedFormat('j M Y') }}</td>
                                <td class="block w-full md:table-cell md:w-auto md:px-5 md:py-3.5">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('blog.show', $post->slug) }}" target="_blank" rel="noopener" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-prussian-blue/15 text-prussian-blue transition hover:bg-prussian-blue/5" title="{{ __('admin.blog.view') }}">
                                            <i class="fa-solid fa-arrow-up-right-from-square fa-sm"></i>
                                        </a>
                                        <a href="{{ route('admin.posts.edit', $post) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-prussian-blue/15 text-prussian-blue transition hover:bg-prussian-blue/5" title="{{ __('admin.blog.edit_title') }}">
                                            <i class="fa-solid fa-pen fa-sm"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.posts.destroy', $post) }}" data-confirm="{{ __('admin.blog.delete_confirm') }}" data-confirm-accept="{{ __('admin.blog.delete_accept') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-ruby-red/20 text-ruby-red transition hover:bg-ruby-red/5" title="{{ __('admin.blog.delete') }}">
                                                <i class="fa-solid fa-trash fa-sm"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-admin-layout>
