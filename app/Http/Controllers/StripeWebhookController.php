<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\HostProfile;
use App\Support\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Webhook;
use Throwable;

class StripeWebhookController extends Controller
{

    public function __invoke(Request $request): Response
    {
        $secret = (string) config('services.stripe.webhook_secret');

        abort_if($secret === '', 400);

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature', ''),
                $secret,
            );
        } catch (Throwable) {
            abort(400);
        }

        if ($event->type === 'checkout.session.completed') {
            $this->handleCheckoutCompleted($event->data->object);
        }

        if ($event->type === 'account.updated') {
            $this->handleAccountUpdated($event->data->object);
        }

        return response()->noContent();
    }

    private function handleCheckoutCompleted(object $session): void
    {
        $booking = Booking::find($session->metadata->booking_id ?? null);

        if ($booking === null || $session->payment_status !== 'paid') {
            return;
        }

        if ($booking->stripe_payment_intent_id === null) {
            $booking->update(['stripe_payment_intent_id' => $session->payment_intent]);
        }

        if (! $booking->markAsPaid()) {
            $booking->refresh();

            if ($booking->status === BookingStatus::Expired) {
                StripeService::refund($booking, $booking->total_cents);
            }
        }
    }

    private function handleAccountUpdated(object $account): void
    {
        HostProfile::where('stripe_account_id', $account->id)->first()?->update([
            'stripe_details_submitted' => (bool) $account->details_submitted,
            'stripe_payouts_enabled' => (bool) $account->payouts_enabled,
        ]);
    }
}
