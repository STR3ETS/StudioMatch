@php
    $field = 'mt-2 w-full rounded-xl border border-prussian-blue/15 bg-white px-4 py-2.5 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none';
    $help = 'mt-1.5 text-xs leading-relaxed text-prussian-blue/50';

    // Na een validatiefout komen de alinea's uit de oude invoer, anders uit de database.
    $rows = old('sections');

    if ($rows === null) {
        $rows = $post->exists
            ? $post->sections->map(fn ($section) => ['id' => $section->id, 'title' => $section->title, 'body' => $section->body])->all()
            : [];
    }

    $tools = [
        ['command' => 'bold', 'icon' => 'fa-bold', 'label' => __('admin.blog.editor_bold')],
        ['command' => 'italic', 'icon' => 'fa-italic', 'label' => __('admin.blog.editor_italic')],
        ['command' => 'underline', 'icon' => 'fa-underline', 'label' => __('admin.blog.editor_underline')],
        ['command' => 'strikeThrough', 'icon' => 'fa-strikethrough', 'label' => __('admin.blog.editor_strike')],
        ['divider' => true],
        ['command' => 'heading', 'icon' => 'fa-heading', 'label' => __('admin.blog.editor_heading')],
        ['command' => 'insertUnorderedList', 'icon' => 'fa-list-ul', 'label' => __('admin.blog.editor_ul')],
        ['command' => 'insertOrderedList', 'icon' => 'fa-list-ol', 'label' => __('admin.blog.editor_ol')],
        ['command' => 'quote', 'icon' => 'fa-quote-right', 'label' => __('admin.blog.editor_quote')],
        ['divider' => true],
        ['command' => 'link', 'icon' => 'fa-link', 'label' => __('admin.blog.editor_link')],
        ['command' => 'unlink', 'icon' => 'fa-link-slash', 'label' => __('admin.blog.editor_unlink')],
        ['command' => 'removeFormat', 'icon' => 'fa-eraser', 'label' => __('admin.blog.editor_clear')],
    ];
@endphp

<x-admin-layout :title="$post->exists ? __('admin.blog.edit_title') : __('admin.blog.new_title')" active="blog">
    <a href="{{ route('admin.posts.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-prussian-blue/60 transition hover:text-ruby-red">
        <i class="fa-solid fa-arrow-left fa-sm"></i> {{ __('admin.blog.back') }}
    </a>

    <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-prussian-blue">{{ $post->exists ? __('admin.blog.edit_title') : __('admin.blog.new_title') }}</h1>

        @if ($post->exists)
            <a href="{{ route('blog.show', $post->slug) }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-2 rounded-full border border-prussian-blue/20 px-5 py-2.5 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">
                <i class="fa-solid fa-arrow-up-right-from-square fa-sm"></i> {{ __('admin.blog.view') }}
            </a>
        @endif
    </div>

    <form method="POST"
          action="{{ $post->exists ? route('admin.posts.update', $post) : route('admin.posts.store') }}"
          enctype="multipart/form-data"
          data-post-form
          class="mt-6 space-y-6">
        @csrf
        @if ($post->exists)
            @method('PUT')
        @endif

        <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6 sm:p-8">
            <div>
                <label for="title" class="text-sm font-semibold text-prussian-blue">{{ __('admin.blog.field_title') }}</label>
                <input id="title" type="text" name="title" value="{{ old('title', $post->title) }}" class="{{ $field }}" required>
                <x-input-error field="title" />
            </div>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="slug" class="text-sm font-semibold text-prussian-blue">{{ __('admin.blog.field_slug') }}</label>
                    <div class="mt-2 flex items-center rounded-xl border border-prussian-blue/15 bg-white pl-4 focus-within:border-prussian-blue/40">
                        <span class="text-sm text-prussian-blue/40">/blog/</span>
                        <input id="slug" type="text" name="slug" value="{{ old('slug', $post->slug) }}" class="w-full rounded-r-xl border-0 bg-transparent px-1 py-2.5 text-sm text-prussian-blue focus:outline-none">
                    </div>
                    <p class="{{ $help }}">{{ __('admin.blog.help_slug') }}</p>
                    <x-input-error field="slug" />
                </div>

                <div>
                    <label for="published_at" class="text-sm font-semibold text-prussian-blue">{{ __('admin.blog.field_published_at') }}</label>
                    <input id="published_at" type="datetime-local" name="published_at"
                           value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}" class="{{ $field }}">
                    <p class="{{ $help }}">{{ __('admin.blog.help_published_at') }}</p>
                    <x-input-error field="published_at" />
                </div>
            </div>

            <div class="mt-5">
                <label for="excerpt" class="text-sm font-semibold text-prussian-blue">{{ __('admin.blog.field_excerpt') }}</label>
                <textarea id="excerpt" name="excerpt" rows="3" class="{{ $field }}">{{ old('excerpt', $post->excerpt) }}</textarea>
                <p class="{{ $help }}">{{ __('admin.blog.help_excerpt') }}</p>
                <x-input-error field="excerpt" />
            </div>

            <div class="mt-5">
                <span class="text-sm font-semibold text-prussian-blue">{{ __('admin.blog.field_status') }}</span>
                <div class="mt-2 grid gap-3 sm:grid-cols-2">
                    @foreach (\App\Enums\PostStatus::cases() as $case)
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-prussian-blue/15 px-4 py-3 text-sm font-medium text-prussian-blue transition has-checked:border-ruby-red has-checked:bg-ruby-red/5">
                            <input type="radio" name="status" value="{{ $case->value }}" class="accent-ruby-red"
                                   @checked(old('status', $post->status?->value ?? \App\Enums\PostStatus::Concept->value) === $case->value)>
                            {{ $case->label() }}
                        </label>
                    @endforeach
                </div>
                <x-input-error field="status" />
            </div>

            <div class="mt-5" data-cover>
                <span class="text-sm font-semibold text-prussian-blue">{{ __('admin.blog.field_cover') }}</span>

                <div class="mt-2 flex flex-wrap items-center gap-4">
                    <img data-cover-preview src="{{ $post->coverUrl() }}" alt=""
                         class="h-24 w-40 rounded-xl border border-prussian-blue/10 object-cover {{ $post->coverUrl() ? '' : 'hidden' }}">

                    <div class="flex flex-wrap items-center gap-2">
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-prussian-blue/20 px-4 py-2.5 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">
                            <i class="fa-solid fa-image fa-sm"></i>
                            <span data-cover-label data-replace="{{ __('admin.blog.cover_replace') }}" data-choose="{{ __('admin.blog.cover_choose') }}">{{ $post->coverUrl() ? __('admin.blog.cover_replace') : __('admin.blog.cover_choose') }}</span>
                            <input type="file" name="cover" accept="image/jpeg,image/png,image/webp" class="hidden" data-cover-input>
                        </label>

                        <button type="button" data-cover-remove
                                class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-ruby-red/20 px-4 py-2.5 text-sm font-semibold text-ruby-red transition hover:bg-ruby-red/5 {{ $post->coverUrl() ? '' : 'hidden' }}">
                            <i class="fa-solid fa-trash fa-sm"></i> {{ __('admin.blog.cover_remove') }}
                        </button>
                    </div>
                </div>

                <input type="hidden" name="remove_cover" value="0" data-cover-flag>
                <p data-cover-marked class="{{ $help }} hidden font-semibold text-ruby-red">{{ __('admin.blog.cover_marked') }}</p>
                <p class="{{ $help }}">{{ __('admin.blog.help_cover') }}</p>
                <x-input-error field="cover" />
            </div>
        </div>

        <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6 sm:p-8"
             data-sections-wrap
             data-section-label="{{ __('admin.blog.section_number', ['number' => ':number']) }}">
            <h2 class="text-lg font-bold text-prussian-blue">{{ __('admin.blog.sections_title') }}</h2>
            <p class="mt-1.5 text-sm leading-relaxed text-prussian-blue/60">{{ __('admin.blog.sections_note') }}</p>

            <div data-sections class="mt-6 space-y-4">
                @foreach ($rows as $index => $row)
                    <x-post-section :index="$index" :row="$row" :tools="$tools" />
                @endforeach
            </div>

            <p data-sections-empty class="mt-6 rounded-xl border border-dashed border-prussian-blue/20 px-4 py-8 text-center text-sm text-prussian-blue/50 {{ count($rows) ? 'hidden' : '' }}">
                {{ __('admin.blog.sections_empty') }}
            </p>

            <button type="button" data-section-add class="mt-5 inline-flex cursor-pointer items-center gap-2 rounded-full border border-prussian-blue/20 px-5 py-2.5 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">
                <i class="fa-solid fa-plus fa-sm"></i> {{ __('admin.blog.section_add') }}
            </button>

            <template data-section-template>
                <x-post-section :index="'__INDEX__'" :row="[]" :tools="$tools" />
            </template>
        </div>

        <button type="submit" class="cursor-pointer rounded-full bg-ruby-red px-8 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
            {{ __('admin.blog.save') }}
        </button>
    </form>

    @if ($post->exists)
        <form id="delete-post" method="POST" action="{{ route('admin.posts.destroy', $post) }}"
              data-confirm="{{ __('admin.blog.delete_confirm') }}" data-confirm-accept="{{ __('admin.blog.delete_accept') }}"
              class="mt-6 rounded-2xl border border-ruby-red/30 bg-ruby-red/5 p-6">
            @csrf
            @method('DELETE')
            <button type="submit" class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-ruby-red/30 px-5 py-2.5 text-sm font-semibold text-ruby-red transition hover:bg-ruby-red/10">
                <i class="fa-solid fa-trash fa-sm"></i> {{ __('admin.blog.delete') }}
            </button>
        </form>
    @endif
</x-admin-layout>
