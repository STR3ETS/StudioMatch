@props(['field'])

@error($field)
    <p {{ $attributes->merge(['class' => 'mt-1.5 text-xs font-semibold text-ruby-red']) }}>{{ $message }}</p>
@enderror
