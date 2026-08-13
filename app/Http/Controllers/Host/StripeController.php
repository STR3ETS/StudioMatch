<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Support\StripeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StripeController extends Controller
{

    public function show(Request $request): View|RedirectResponse
    {
        $profile = $request->user()->hostProfile;

        if ($profile === null) {
            return redirect()->route('host.profile.edit')->with('status', __('host.stripe.profile_first'));
        }

        if (StripeService::enabled() && $profile->stripe_account_id !== null && ! $profile->stripe_payouts_enabled) {
            StripeService::refreshAccountStatus($profile);
            $profile->refresh();
        }

        return view('host.stripe', [
            'profile' => $profile,
            'stripeEnabled' => StripeService::enabled(),
        ]);
    }

    public function onboard(Request $request): RedirectResponse
    {
        $profile = $request->user()->hostProfile;

        if ($profile === null) {
            return redirect()->route('host.profile.edit')->with('status', __('host.stripe.profile_first'));
        }

        if (! StripeService::enabled()) {
            return back()->withErrors(['stripe' => __('host.stripe.not_configured')]);
        }

        $url = StripeService::onboardingUrl($profile);

        if ($url === null) {
            return back()->withErrors(['stripe' => __('host.stripe.failed')]);
        }

        return redirect()->away($url);
    }

    public function returned(Request $request): RedirectResponse
    {
        $profile = $request->user()->hostProfile;

        if ($profile !== null) {
            StripeService::refreshAccountStatus($profile);
        }

        return redirect()->route('host.stripe.show')->with('status', __('host.stripe.returned'));
    }
}
