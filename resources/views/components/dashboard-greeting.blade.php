{{-- A freshly registered account should not be welcomed "back". --}}
<h1 {{ $attributes->merge(['class' => 'mt-3 text-3xl font-bold text-prussian-blue']) }}>
    {{ __(session('sm.just_registered') ? 'dashboard.greeting_new' : 'dashboard.greeting', ['name' => auth()->user()->firstName()]) }}
</h1>
