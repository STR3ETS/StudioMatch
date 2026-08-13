<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\HostProfile;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Throwable;

class StripeService
{
    public static function enabled(): bool
    {
        return (string) config('services.stripe.secret') !== '';
    }

    public static function client(): StripeClient
    {
        return new StripeClient(config('services.stripe.secret'));
    }

    public static function createCheckoutSession(Booking $booking): ?string
    {
        try {
            $session = self::client()->checkout->sessions->create([
                'mode' => 'payment',
                'payment_method_types' => ['ideal', 'card'],
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => 'eur',
                        'unit_amount' => $booking->total_cents,
                        'product_data' => [
                            'name' => $booking->room->studio->name . ' - ' . $booking->room->title,
                            'description' => $booking->date->translatedFormat('l j F Y') . ' ' . $booking->timeRange(),
                        ],
                    ],
                ]],
                'customer_email' => $booking->user->email,
                'metadata' => ['booking_id' => $booking->id],
                'payment_intent_data' => [
                    'metadata' => ['booking_id' => $booking->id],
                    'transfer_group' => 'booking-' . $booking->id,
                ],
                'expires_at' => now()->addMinutes(30)->timestamp,
                'success_url' => route('bookings.paid', $booking) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('bookings.payment', $booking),
            ]);

            $booking->update(['stripe_checkout_session_id' => $session->id]);

            return $session->url;
        } catch (Throwable $e) {
            Log::error('Stripe checkout session mislukt', ['booking' => $booking->id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    public static function verifyCheckoutPaid(Booking $booking, string $sessionId): bool
    {
        if ($booking->stripe_checkout_session_id !== $sessionId) {
            return false;
        }

        try {
            $session = self::client()->checkout->sessions->retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return false;
            }

            $booking->update(['stripe_payment_intent_id' => $session->payment_intent]);

            return true;
        } catch (Throwable $e) {
            Log::error('Stripe checkout verificatie mislukt', ['booking' => $booking->id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public static function refund(Booking $booking, int $amountCents): void
    {
        if ($amountCents <= 0 || $booking->refunded_cents !== null) {
            return;
        }

        if (! self::enabled() || $booking->stripe_payment_intent_id === null) {
            $booking->update(['refunded_cents' => $amountCents]);

            return;
        }

        try {
            $refund = self::client()->refunds->create([
                'payment_intent' => $booking->stripe_payment_intent_id,
                'amount' => $amountCents,
            ]);

            $booking->update(['stripe_refund_id' => $refund->id, 'refunded_cents' => $amountCents]);
        } catch (Throwable $e) {
            Log::error('Stripe refund mislukt', ['booking' => $booking->id, 'error' => $e->getMessage()]);
        }
    }

    public static function transfer(Booking $booking): bool
    {
        $profile = $booking->room->studio->user->hostProfile;
        $amount = $booking->hostPayoutCents();

        if ($amount <= 0 || ! self::enabled() || $profile?->stripe_account_id === null || ! $profile->stripe_payouts_enabled) {
            return false;
        }

        try {
            $transfer = self::client()->transfers->create([
                'amount' => $amount,
                'currency' => 'eur',
                'destination' => $profile->stripe_account_id,
                'transfer_group' => 'booking-' . $booking->id,
                'metadata' => ['booking_id' => $booking->id],
            ]);

            $booking->update(['stripe_transfer_id' => $transfer->id, 'transferred_at' => now()]);

            return true;
        } catch (Throwable $e) {
            Log::error('Stripe transfer mislukt', ['booking' => $booking->id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public static function onboardingUrl(HostProfile $profile): ?string
    {
        try {
            if ($profile->stripe_account_id === null) {
                $account = self::client()->accounts->create([
                    'type' => 'express',
                    'country' => 'NL',
                    'email' => $profile->user->email,
                    'business_type' => $profile->owner_type->value === 'ondernemer' ? 'company' : 'individual',
                    'capabilities' => ['transfers' => ['requested' => true]],
                ]);

                $profile->update(['stripe_account_id' => $account->id]);
            }

            $link = self::client()->accountLinks->create([
                'account' => $profile->stripe_account_id,
                'refresh_url' => route('host.stripe.show'),
                'return_url' => route('host.stripe.return'),
                'type' => 'account_onboarding',
            ]);

            return $link->url;
        } catch (Throwable $e) {
            Log::error('Stripe onboarding mislukt', ['profile' => $profile->id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    public static function refreshAccountStatus(HostProfile $profile): void
    {
        if (! self::enabled() || $profile->stripe_account_id === null) {
            return;
        }

        try {
            $account = self::client()->accounts->retrieve($profile->stripe_account_id);

            $profile->update([
                'stripe_details_submitted' => (bool) $account->details_submitted,
                'stripe_payouts_enabled' => (bool) $account->payouts_enabled,
            ]);
        } catch (Throwable $e) {
            Log::error('Stripe accountstatus ophalen mislukt', ['profile' => $profile->id, 'error' => $e->getMessage()]);
        }
    }
}
