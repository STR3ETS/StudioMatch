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
        $profile = $booking->room->studio->user->hostProfile;
        $btwPlichtig = (bool) $profile?->btw_plichtig;
        $vatRate = (int) config('studio.vat_percent');

        $base = 'SM-' . $booking->created_at->year . '-' . str_pad((string) $booking->id, 4, '0', STR_PAD_LEFT);
        $numbers = ['huur' => $base . '-H', 'commissie' => $base . '-C', 'credit-huur' => $base . '-CH', 'credit-commissie' => $base . '-CC'];

        $hostSeller = array_filter([
            $profile?->name ?? $booking->room->studio->user->name,
            $booking->room->studio->fullAddress(),
            $profile?->kvk_number ? 'KvK ' . $profile->kvk_number : null,
            $btwPlichtig && $profile?->vat_number ? 'Btw ' . $profile->vat_number : null,
        ]);

        $platformSeller = [
            __('contact.info.company'),
            'studiomatch.nl',
            __('contact.info.kvk'),
            __('contact.info.btw'),
        ];

        $sessionLabel = __('invoice.line_session', [
            'room' => $booking->room->studio->name . ' - ' . $booking->room->title,
            'date' => $booking->date->translatedFormat('j F Y'),
            'time' => $booking->timeRange(),
            'hours' => $booking->hours(),
        ]);

        $data = match ($type) {
            'huur' => [
                'title' => $btwPlichtig ? __('invoice.types.rent_invoice') : __('invoice.types.rent_receipt'),
                'seller' => $hostSeller,
                'lines' => [['label' => $sessionLabel, 'amount' => $booking->rent_cents]],
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
            'buyer' => [$booking->user->name, $booking->user->email],
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
