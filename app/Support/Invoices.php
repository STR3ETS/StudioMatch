<?php

namespace App\Support;

use App\Models\Booking;

class Invoices
{
    public static function documentsFor(Booking $booking): array
    {
        if (! $booking->wasPaid()) {
            return [];
        }

        $types = ['huur', 'commissie'];

        if (self::creditRentCents($booking) > 0) {
            $types[] = 'credit-huur';
        }

        if (self::creditFeeCents($booking) > 0) {
            $types[] = 'credit-commissie';
        }

        return $types;
    }

    public static function hostDocumentsFor(Booking $booking): array
    {
        return array_values(array_intersect(self::documentsFor($booking), ['huur', 'credit-huur']));
    }

    public static function build(Booking $booking, string $type): array
    {
        // De gegevens komen van de boeking zelf. Die momentopname is bij het aanmaken
        // vastgelegd, zodat een factuur blijft kloppen nadat een account is verwijderd.
        // De live gegevens dienen alleen nog als terugval voor oude, ongevulde rijen.
        $profile = $booking->room->studio->user->hostProfile;
        $btwPlichtig = $booking->seller_vat_liable ?? (bool) $profile?->btw_plichtig;
        $vatRate = (int) config('studio.vat_percent');

        $base = 'SM-' . $booking->created_at->year . '-' . str_pad((string) $booking->id, 4, '0', STR_PAD_LEFT);
        $numbers = ['huur' => $base . '-H', 'commissie' => $base . '-C', 'credit-huur' => $base . '-CH', 'credit-commissie' => $base . '-CC'];

        $kvk = $booking->seller_kvk_number ?? $profile?->kvk_number;
        $sellerVat = $booking->seller_vat_number ?? $profile?->vat_number;

        $hostSeller = array_filter([
            $booking->seller_name ?? $profile?->name ?? $booking->room->studio->user->name,
            $booking->seller_address ?? $booking->room->studio->fullAddress(),
            $kvk ? 'KvK ' . $kvk : null,
            $btwPlichtig && $sellerVat ? 'Btw ' . $sellerVat : null,
        ]);

        $platformSeller = [
            __('contact.info.company'),
            'studiomatch.nl',
            __('contact.info.kvk'),
            __('contact.info.btw'),
        ];

        $sessionLabel = __('invoice.line_session', [
            'room' => $booking->room_label ?? $booking->room->studio->name . ' - ' . $booking->room->title,
            'date' => $booking->date->translatedFormat('j F Y'),
            'time' => $booking->timeRange(),
            'hours' => $booking->hours(),
        ]);

        // De toeslag voor de engineer staat als eigen regel op de huurfactuur.
        $engineerCents = (int) ($booking->engineer_cents ?? 0);
        $rentLines = $engineerCents > 0
            ? [
                ['label' => $sessionLabel, 'amount' => $booking->rent_cents - $engineerCents],
                ['label' => __('invoice.line_engineer'), 'amount' => $engineerCents],
            ]
            : [['label' => $sessionLabel, 'amount' => $booking->rent_cents]];

        $data = match ($type) {
            'huur' => [
                'title' => $btwPlichtig ? __('invoice.types.rent_invoice') : __('invoice.types.rent_receipt'),
                'seller' => $hostSeller,
                'lines' => $rentLines,
                'total' => $booking->rent_cents,
                'vat' => $btwPlichtig ? self::vatFromInclusive($booking->rent_cents, $vatRate) : null,
                'note' => $btwPlichtig ? __('invoice.notes.rent_invoice') : __('invoice.notes.rent_receipt'),
                'reference' => null,
            ],
            'commissie' => [
                'title' => __('invoice.types.fee_invoice'),
                'seller' => $platformSeller,
                'lines' => [['label' => __('invoice.line_fee', ['number' => $base]), 'amount' => $booking->service_fee_cents + $booking->vat_cents]],
                'total' => $booking->service_fee_cents + $booking->vat_cents,
                'vat' => ['excl' => $booking->service_fee_cents, 'rate' => $vatRate, 'vat' => $booking->vat_cents],
                'note' => __('invoice.notes.fee_invoice'),
                'reference' => null,
            ],
            'credit-huur' => [
                'title' => __('invoice.types.credit'),
                'seller' => $hostSeller,
                'lines' => [['label' => __('invoice.line_credit', ['number' => $numbers['huur']]), 'amount' => -self::creditRentCents($booking)]],
                'total' => -self::creditRentCents($booking),
                'vat' => $btwPlichtig ? self::vatFromInclusive(-self::creditRentCents($booking), $vatRate) : null,
                'note' => __('invoice.notes.credit'),
                'reference' => $numbers['huur'],
            ],
            'credit-commissie' => [
                'title' => __('invoice.types.credit'),
                'seller' => $platformSeller,
                'lines' => [['label' => __('invoice.line_credit', ['number' => $numbers['commissie']]), 'amount' => -self::creditFeeCents($booking)]],
                'total' => -self::creditFeeCents($booking),
                'vat' => self::vatFromInclusive(-self::creditFeeCents($booking), $vatRate),
                'note' => __('invoice.notes.credit'),
                'reference' => $numbers['commissie'],
            ],
        };

        return [
            ...$data,
            'number' => $numbers[$type],
            'date' => ($booking->requested_at ?? $booking->created_at)->format('d-m-Y'),
            'buyer' => array_filter([
                $booking->isBusinessBooking() ? $booking->buyer_company : null,
                $booking->buyer_name ?? $booking->user?->name,
                $booking->buyer_street ?? $booking->user?->street,
                trim(($booking->buyer_postal_code ?? $booking->user?->postal_code ?? '') . ' ' . ($booking->buyer_city ?? $booking->user?->city ?? '')) ?: null,
                $booking->buyer_email ?? $booking->user?->email,
                $booking->isBusinessBooking() && $booking->buyer_vat_number ? 'Btw ' . $booking->buyer_vat_number : null,
            ]),
        ];
    }

    public static function creditRentCents(Booking $booking): int
    {
        $refunded = $booking->refunded_cents ?? 0;
        $fees = $booking->service_fee_cents + $booking->vat_cents;

        return min($booking->rent_cents, max(0, $refunded - $fees));
    }

    public static function creditFeeCents(Booking $booking): int
    {
        return min($booking->refunded_cents ?? 0, $booking->service_fee_cents + $booking->vat_cents);
    }

    private static function vatFromInclusive(int $inclusiveCents, int $rate): array
    {
        $excl = (int) round($inclusiveCents / (1 + $rate / 100));

        return ['excl' => $excl, 'rate' => $rate, 'vat' => $inclusiveCents - $excl];
    }
}
