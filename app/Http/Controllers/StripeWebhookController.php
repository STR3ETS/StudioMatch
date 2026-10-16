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
        // Stripe kent per bestemming een eigen geheim. Wie zowel het platform als de
        // gekoppelde accounts laat afleveren, zet ze hier gescheiden door een komma.
        $secrets = array_filter(array_map('trim', explode(',', (string) config('services.stripe.webhook_secret'))));

        abort_if($secrets === [], 400);

        $event = null;

        foreach ($secrets as $secret) {
            try {
                $event = Webhook::constructEvent(
                    $request->getContent(),
                    $request->header('Stripe-Signature', ''),
                    $secret,
                );

                break;
            } catch (Throwable) {
                continue;
            }
        }

        abort_if($event === null, 400);

        if ($event->type === 'checkout.session.completed') {
            $this->handleCheckoutCompleted($event->data->object);
        }

        if ($event->type === 'charge.refunded') {
            $this->handleChargeRefunded($event->data->object);
        }

        if ($event->type === 'account.updated') {
            $this->handleAccountUpdated($event->data->object);
        }

        return response()->noContent();
    }

    private function handleChargeRefunded(object $charge): void
    {
        Booking::where('stripe_payment_intent_id', $charge->payment_intent)->first()?->update([
            'refunded_cents' => $charge->amount_refunded,
        ]);
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
