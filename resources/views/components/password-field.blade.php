@props(['id', 'name', 'autocomplete' => 'current-password', 'minlength' => null])

<div class="relative">
    <input id="{{ $id }}" type="password" name="{{ $name }}"
           placeholder="{{ __('auth.fields.password_placeholder') }}"
           autocomplete="{{ $autocomplete }}"
           @if ($minlength) minlength="{{ $minlength }}" @endif
           {{ $attributes->merge(['class' => 'mt-2 w-full rounded-xl border border-prussian-blue/15 px-4 py-2.5 pr-11 text-sm text-prussian-blue placeholder:text-prussian-blue/40 focus:border-prussian-blue/40 focus:outline-none']) }}
           required>
    <button type="button" data-password-toggle aria-label="{{ __('auth.fields.password_show') }}"
            class="absolute right-2 top-1/2 mt-1 flex h-8 w-8 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full text-prussian-blue/40 transition hover:bg-prussian-blue/5 hover:text-prussian-blue">
        <i class="fa-solid fa-eye fa-sm"></i>
    </button>
</div>
