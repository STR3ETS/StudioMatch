<x-host-layout :title="__('host.stripe.title')" active="stripe">
    <h1 class="text-2xl font-bold text-prussian-blue">{{ __('host.stripe.title') }}</h1>
    <p class="mt-2 text-prussian-blue/60">{{ __('host.stripe.subtitle') }}</p>

    <div class="mt-8 max-w-2xl">
        @if (! $stripeEnabled)
            <div class="flex items-start gap-3 rounded-2xl border border-dashed border-amber-500/40 bg-amber-500/5 p-6">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600"><i class="fa-solid fa-flask"></i></span>
                <div>
                    <h2 class="font-bold text-prussian-blue">{{ __('host.stripe.not_configured_title') }}</h2>
                    <p class="mt-1 text-sm leading-relaxed text-prussian-blue/60">{{ __('host.stripe.demo_note') }}</p>
                </div>
            </div>
        @elseif ($profile->stripe_payouts_enabled)
            <div class="flex items-start gap-3 rounded-2xl border border-emerald-500/30 bg-emerald-500/5 p-6">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600"><i class="fa-solid fa-circle-check"></i></span>
                <div>
                    <h2 class="font-bold text-prussian-blue">{{ __('host.stripe.active_title') }}</h2>
                    <p class="mt-1 text-sm leading-relaxed text-prussian-blue/60">{{ __('host.stripe.active_text') }}</p>
                </div>
            </div>
        @elseif ($profile->stripe_account_id !== null)
            <div class="rounded-2xl border border-amber-500/40 bg-amber-500/5 p-6">
                <div class="flex items-start gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600"><i class="fa-solid fa-hourglass-half"></i></span>
                    <div>
                        <h2 class="font-bold text-prussian-blue">{{ __('host.stripe.pending_title') }}</h2>
                        <p class="mt-1 text-sm leading-relaxed text-prussian-blue/60">{{ __('host.stripe.pending_text') }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('host.stripe.onboard') }}" class="mt-5">
                    @csrf
                    <button type="submit" class="cursor-pointer rounded-full bg-prussian-blue px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-prussian-blue/90">
                        <i class="fa-solid fa-arrow-right fa-sm mr-1.5"></i>{{ __('host.stripe.continue_button') }}
                    </button>
                </form>
            </div>
        @else
            <div class="rounded-2xl border border-prussian-blue/10 bg-white p-6">
                <div class="flex items-start gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-ruby-red/10 text-ruby-red"><i class="fa-solid fa-shield-halved"></i></span>
                    <div>
                        <h2 class="font-bold text-prussian-blue">{{ __('host.stripe.not_connected_title') }}</h2>
                        <p class="mt-1 text-sm leading-relaxed text-prussian-blue/60">{{ __('host.stripe.not_connected_text') }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('host.stripe.onboard') }}" class="mt-5">
                    @csrf
                    <button type="submit" class="cursor-pointer rounded-full bg-ruby-red px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-ruby-red/90">
                        <i class="fa-solid fa-arrow-right fa-sm mr-1.5"></i>{{ __('host.stripe.connect_button') }}
                    </button>
                </form>
            </div>
        @endif

        <x-info-note class="mt-6">{{ __('host.stripe.payout_note') }}</x-info-note>
    </div>
</x-host-layout>
