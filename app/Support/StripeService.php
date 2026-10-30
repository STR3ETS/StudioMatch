<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\HostProfile;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Throwable;

class StripeService
{
    /** De laatste fout van Stripe, zodat php artisan stripe:check hem kan tonen. */
    private static ?string $lastError = null;

    public static function lastError(): ?string
    {
        return self::$lastError;
    }

    /** Stripe zegt in de uitzondering welk veld hij afkeurt; dat is het halve antwoord. */
    private static function describe(Throwable $exception): string
    {
        if (! $exception instanceof ApiErrorException) {
            return $exception->getMessage();
        }

        $error = $exception->getError();

        $parts = array_filter([
            $exception->getMessage(),
            $error?->type ? 'type: ' . $error->type : null,
            $error?->code ? 'code: ' . $error->code : null,
            $error?->param ? 'veld: ' . $error->param : null,
            $exception->getHttpStatus() ? 'status: ' . $exception->getHttpStatus() : null,
        ]);

        return implode(' | ', $parts);
    }

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
                'line_items' => self::lineItems($booking),
                'customer_email' => $booking->user->email,
                'metadata' => ['booking_id' => $booking->id],
                'payment_intent_data' => [
                    'description' => self::splitDescription($booking),
                    'metadata' => self::splitMetadata($booking),
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

    /*
     * Eén regel met het totaal liet in Stripe niet zien welk deel naar de studio gaat en
     * welk deel bij StudioMatch blijft. Los uitgesplitst ziet de huurder het op de
     * betaalpagina en ziet StudioMatch het in het dashboard. Samen altijd total_cents.
     */
    public static function lineItems(Booking $booking): array
    {
        $line = fn (int $cents, string $name, ?string $description = null) => [
            'quantity' => 1,
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => $cents,
                'product_data' => array_filter(['name' => $name, 'description' => $description]),
            ],
        ];

        $items = [$line(
            $booking->rent_cents,
            $booking->room->studio->name . ' - ' . $booking->room->title,
            $booking->date->translatedFormat('l j F Y') . ' ' . $booking->timeRange(),
        )];

        if ($booking->service_fee_cents > 0) {
            $items[] = $line($booking->service_fee_cents, __('booking.checkout.stripe_service_fee'));
        }

        if ($booking->vat_cents > 0) {
            $items[] = $line($booking->vat_cents, __('booking.checkout.stripe_vat', ['percent' => config('studio.vat_percent')]));
        }

        return $items;
    }

    public static function splitDescription(Booking $booking): string
    {
        return __('booking.checkout.stripe_split', [
            'id' => $booking->id,
            'studio' => $booking->room->studio->name,
            'rent' => self::euro($booking->rent_cents),
            'platform' => self::euro($booking->service_fee_cents + $booking->vat_cents),
        ]);
    }

    /** Metadata is in het Stripe-dashboard doorzoekbaar en staat bij elke betaling. */
    public static function splitMetadata(Booking $booking): array
    {
        return [
            'booking_id' => (string) $booking->id,
            'studio' => $booking->room->studio->name,
            'naar_studio' => self::euro($booking->rent_cents),
            'servicekosten' => self::euro($booking->service_fee_cents),
            'btw_servicekosten' => self::euro($booking->vat_cents),
            'uitbetaling' => __('booking.checkout.stripe_payout_moment'),
        ];
    }

    private static function euro(int $cents): string
    {
        return '€ ' . number_format($cents / 100, 2, ',', '.');
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
            /*
             * Zonder bron haalt Stripe de overboeking uit het vrij beschikbare saldo van
             * StudioMatch. Bij een nieuw account staat dat de eerste dagen op nul en mislukt
             * de overboeking dan. Gekoppeld aan de betaling zelf maakt Stripe hem meteen aan
             * en voert hem uit zodra dat geld vrijkomt; de studio ziet hem direct staan.
             */
            $charge = self::client()->paymentIntents->retrieve($booking->stripe_payment_intent_id)->latest_charge;

            $transfer = self::client()->transfers->create(array_filter([
                'amount' => $amount,
                'currency' => 'eur',
                'destination' => $profile->stripe_account_id,
                'source_transaction' => is_string($charge) ? $charge : $charge?->id,
                'transfer_group' => 'booking-' . $booking->id,
                'description' => __('booking.checkout.stripe_transfer', [
                    'id' => $booking->id,
                    'date' => $booking->date->translatedFormat('j F Y'),
                ]),
                'metadata' => ['booking_id' => $booking->id],
            ]));

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
                $account = self::client()->v2->core->accounts->create([
                    'contact_email' => $profile->user->email,
                    'display_name' => $profile->name,
                    'dashboard' => 'express',
                    'identity' => [
                        'country' => 'NL',
                        'entity_type' => $profile->owner_type->value === 'ondernemer' ? 'company' : 'individual',
                    ],
                    'defaults' => [
                        'currency' => 'eur',
                        'locales' => ['nl-NL'],
                        'profile' => [
                            'business_url' => config('app.url'),
                            'doing_business_as' => $profile->name,
                        ],
                        'responsibilities' => [
                            'fees_collector' => 'application',
                            'losses_collector' => 'application',
                        ],
                    ],
                    'configuration' => [
                        'recipient' => [
                            'capabilities' => [
                                'stripe_balance' => ['stripe_transfers' => ['requested' => true]],
                            ],
                        ],
                    ],
                ]);

                $profile->update(['stripe_account_id' => $account->id]);
            }

            $link = self::client()->v2->core->accountLinks->create([
                'account' => $profile->stripe_account_id,
                'use_case' => [
                    'type' => 'account_onboarding',
                    'account_onboarding' => [
                        'configurations' => ['recipient'],
                        'refresh_url' => route('host.stripe.show'),
                        'return_url' => route('host.stripe.return'),
                    ],
                ],
            ]);

            return $link->url;
        } catch (Throwable $e) {
            self::$lastError = self::describe($e);

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
            $account = self::client()->v2->core->accounts->retrieve($profile->stripe_account_id, [
                'include' => ['configuration.recipient', 'requirements'],
            ]);

            $status = $account->configuration->recipient->capabilities->stripe_balance->stripe_transfers->status ?? null;
            $openRequirements = count($account->requirements->entries ?? []);

            $profile->update([
                'stripe_details_submitted' => $status === 'active' || $openRequirements === 0,
                'stripe_payouts_enabled' => $status === 'active',
            ]);
        } catch (Throwable $e) {
            Log::error('Stripe accountstatus ophalen mislukt', ['profile' => $profile->id, 'error' => $e->getMessage()]);
        }
    }
}
