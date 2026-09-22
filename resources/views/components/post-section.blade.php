@props(['index', 'row' => [], 'tools' => []])

@php
    $name = 'sections[' . $index . ']';
    $number = is_numeric($index) ? (int) $index + 1 : 1;

    // Bij een validatiefout komt de tekst rechtstreeks uit de oude invoer en is hij nog
    // niet langs de sanitizer geweest. Schoonmaken voordat we hem terug in de editor zetten.
    $body = \App\Support\RichText::clean($row['body'] ?? null);
@endphp

<div data-section data-sortable-item class="rounded-2xl border border-prussian-blue/10 bg-prussian-blue/[0.02] p-4 sm:p-5">
    <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <button type="button" data-sortable-handle title="{{ __('admin.blog.section_drag') }}" aria-label="{{ __('admin.blog.section_drag') }}"
                    class="sm-drag-handle inline-flex h-8 w-8 items-center justify-center rounded-lg text-prussian-blue/30 transition hover:bg-prussian-blue/5 hover:text-prussian-blue/60">
                <i class="fa-solid fa-grip-vertical fa-sm"></i>
            </button>
            <span data-section-number class="text-xs font-bold uppercase tracking-wide text-prussian-blue/40">
                {{ __('admin.blog.section_number', ['number' => $number]) }}
            </span>
        </div>

        <button type="button" data-section-remove title="{{ __('admin.blog.section_remove') }}"
                class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg text-prussian-blue/40 transition hover:bg-ruby-red/10 hover:text-ruby-red">
            <i class="fa-solid fa-xmark fa-sm"></i>
        </button>
    </div>

    @isset($row['id'])
        <input type="hidden" name="{{ $name }}[id]" value="{{ $row['id'] }}">
    @endisset

    <div class="mt-3">
        <label class="text-sm font-semibold text-prussian-blue">{{ __('admin.blog.section_title') }}</label>
        <input type="text" name="{{ $name }}[title]" value="{{ $row['title'] ?? '' }}"
               placeholder="{{ __('admin.blog.section_title_placeholder') }}"
               class="mt-2 w-full rounded-xl border border-prussian-blue/15 bg-white px-4 py-2.5 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none">
    </div>

    <div class="mt-4">
        <label class="text-sm font-semibold text-prussian-blue">{{ __('admin.blog.section_body') }}</label>

        <div data-editor
             data-link-prompt="{{ __('admin.blog.editor_link_prompt') }}"
             data-link-select="{{ __('admin.blog.editor_link_select') }}"
             class="mt-2 overflow-hidden rounded-xl border border-prussian-blue/15 bg-white focus-within:border-prussian-blue/40">
            <div class="flex flex-wrap items-center gap-0.5 border-b border-prussian-blue/10 bg-prussian-blue/[0.02] px-2 py-1.5">
                @foreach ($tools as $tool)
                    @if ($tool['divider'] ?? false)
                        <span class="mx-1 h-5 w-px bg-prussian-blue/10"></span>
                    @else
                        <button type="button" data-editor-command="{{ $tool['command'] }}"
                                title="{{ $tool['label'] }}" aria-label="{{ $tool['label'] }}"
                                class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg text-prussian-blue/60 transition hover:bg-prussian-blue/10 hover:text-prussian-blue">
                            <i class="fa-solid {{ $tool['icon'] }} fa-sm"></i>
                        </button>
                    @endif
                @endforeach
            </div>

            <div data-editor-area contenteditable="true" role="textbox" aria-multiline="true"
                 data-placeholder="{{ __('admin.blog.section_body_placeholder') }}"
                 class="sm-editor min-h-36 px-4 py-3 text-sm leading-relaxed text-prussian-blue focus:outline-none">{!! $body !!}</div>

            <input type="hidden" name="{{ $name }}[body]" value="{{ $body }}" data-editor-input>
        </div>
    </div>
</div>
