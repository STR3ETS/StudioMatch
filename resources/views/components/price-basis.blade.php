@props(['default' => 'incl'])

@php
    // Alleen wie btw-plichtig is heeft een prijs exclusief btw; voor de rest is er maar één bedrag.
    $vatLiable = (bool) auth()->user()?->hostProfile?->btw_plichtig;
    $basis = $vatLiable ? old('price_basis', $default) : 'incl';
    $vat = (int) config('studio.vat_percent');
@endphp

<div data-price-basis data-vat="{{ $vat }}" data-basis="{{ $basis }}" data-vat-liable="{{ $vatLiable ? '1' : '0' }}"
     {{ $attributes->merge(['class' => 'mt-4 rounded-xl bg-prussian-blue/5 px-4 py-3.5']) }}>
    @if ($vatLiable)
        <p class="text-xs font-bold uppercase tracking-wide text-prussian-blue/50">{{ __('host.rooms.price_basis.label') }}</p>
        <div class="mt-2 inline-flex rounded-full bg-white p-1 shadow-sm">
            @foreach (['excl', 'incl'] as $option)
                <label class="cursor-pointer">
                    <input type="radio" name="price_basis" value="{{ $option }}" class="peer sr-only" @checked($basis === $option)>
                    <span class="block rounded-full px-4 py-1.5 text-sm font-semibold text-prussian-blue/60 transition peer-checked:bg-prussian-blue peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-ruby-red">
                        {{ __('host.rooms.price_basis.' . $option, ['percent' => $vat]) }}
                    </span>
                </label>
            @endforeach
        </div>
        <p class="mt-2 text-xs leading-relaxed text-prussian-blue/60">{{ __('host.rooms.price_basis.note', ['percent' => $vat]) }}</p>
    @else
        <p class="text-xs leading-relaxed text-prussian-blue/60">{{ __('host.rooms.price_basis.not_liable') }}</p>
    @endif
</div>

@once
    <script>
        (() => {
            const box = document.querySelector('[data-price-basis]');
            if (! box || box.dataset.vatLiable !== '1') return;

            const form = box.closest('form');
            const factor = 1 + Number(box.dataset.vat) / 100;
            const euro = new Intl.NumberFormat('nl-NL', { style: 'currency', currency: 'EUR' });
            const t = {
                incl: @json(__('host.rooms.price_basis.hint_incl', ['percent' => $vat])),
                excl: @json(__('host.rooms.price_basis.hint_excl', ['percent' => $vat])),
            };
            let basis = box.dataset.basis;

            const inputs = () => [...form.querySelectorAll('input[data-price]')];

            const hintFor = (input) => {
                let hint = input.parentElement.parentElement.querySelector(`[data-price-hint="${input.name}"]`);
                if (! hint) {
                    hint = document.createElement('p');
                    hint.dataset.priceHint = input.name;
                    hint.className = 'mt-1.5 text-xs font-medium text-emerald-700';
                    (input.closest('.relative') ?? input).after(hint);
                }
                return hint;
            };

            // Laat onder elk bedrag zien wat het andere bedrag is, en wat er op de pagina komt.
            const render = () => inputs().forEach((input) => {
                const value = parseFloat(input.value);
                const hint = hintFor(input);
                if (! value) { hint.textContent = ''; return; }

                hint.textContent = basis === 'excl'
                    ? t.excl.replace(':amount', euro.format(value * factor))
                    : t.incl.replace(':amount', euro.format(value / factor));
            });

            // Bij omschakelen blijft de werkelijke prijs gelijk; alleen de weergave wisselt.
            box.querySelectorAll('input[name="price_basis"]').forEach((radio) => radio.addEventListener('change', () => {
                const next = radio.value;
                inputs().forEach((input) => {
                    const value = parseFloat(input.value);
                    if (! value) return;
                    input.value = (next === 'excl' ? value / factor : value * factor).toFixed(2);
                });
                basis = next;
                render();
            }));

            form.addEventListener('input', (event) => {
                if (event.target.matches('input[data-price]')) render();
            });

            render();
        })();
    </script>
@endonce
