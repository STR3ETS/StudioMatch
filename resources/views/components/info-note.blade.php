<p {{ $attributes->merge(['class' => 'flex items-start gap-2 rounded-xl bg-prussian-blue/5 px-4 py-3 text-xs leading-relaxed text-prussian-blue/60']) }}>
    <i class="fa-solid fa-circle-info mt-0.5 text-prussian-blue/40"></i>
    <span>{{ $slot }}</span>
</p>
