@props(['name', 'value' => null, 'min' => null, 'placeholder' => null, 'id' => null, 'clearable' => false])

@php
    $id = $id ?: 'date-' . \Illuminate\Support\Str::slug($name);
    $min = $min ?: today()->toDateString();
    $placeholder = $placeholder ?: __('studios.filters.date_placeholder');
    $field = 'mt-2 w-full rounded-xl border border-prussian-blue/15 bg-white px-4 py-2.5 text-sm text-prussian-blue focus:border-prussian-blue/40 focus:outline-none';
@endphp

<div {{ $attributes->merge(['class' => 'relative']) }} data-datepicker data-min="{{ $min }}">
    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    <button type="button" id="{{ $id }}" data-datepicker-toggle class="{{ $field }} cursor-pointer text-left {{ $clearable ? 'pr-10' : '' }}">
        <span data-datepicker-label class="{{ $value ? '' : 'text-prussian-blue/40' }}">
            {{ $value ? \Illuminate\Support\Carbon::parse($value)->translatedFormat('j M Y') : $placeholder }}
        </span>
    </button>
    @if ($clearable)
        <button type="button" data-datepicker-clear data-placeholder="{{ $placeholder }}"
                class="absolute right-3 top-1/2 mt-1 flex h-6 w-6 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full text-prussian-blue/40 transition hover:bg-prussian-blue/5 hover:text-prussian-blue {{ $value ? '' : 'hidden' }}">
            <i class="fa-solid fa-xmark fa-sm"></i>
        </button>
    @endif
    <div data-datepicker-panel class="absolute left-0 top-full z-30 mt-2 hidden w-72 rounded-2xl border border-prussian-blue/10 bg-white p-4 shadow-xl"></div>
</div>
