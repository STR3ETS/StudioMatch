<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\ExceptionType;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomException;
use Illuminate\Support\Facades\URL;

/**
 * Gegevens voor de schermen die in de iOS/Android-app zelf zitten (mobile/app). De app krijgt alleen
 * data, in de taal van de gebruiker; hoe het eruitziet staat in de app. Bedragen in centen, paden
 * relatief (die opent de app als serverpagina).
 */
class AppPayload
{
    /**
     * Een boeking zoals de artiest hem ziet.
     *
     * @return array<string, mixed>
     */
    public static function artistBooking(Booking $booking): array
    {
        $status = $booking->effectiveStatus();
        $room = $booking->room;

        return [
            'id' => $booking->id,
            'status' => $status->value,
            'status_label' => __('booking.status.'.$status->value),
            'badge' => $status->badgeClasses(),
            'room' => $room->title,
            'studio' => $room->studio->name,
            'city' => $room->studio->city,
            'photo' => self::url($room->photos->first()?->thumbUrl()),
            'date' => $booking->date->toDateString(),
            'starts_at' => $booking->startsAt()->toIso8601String(),
            'date_label' => $booking->dateRange(),
            'time_label' => $booking->timeRange(),
            'total_cents' => (int) $booking->total_cents,
        ];
    }

    /**
     * De detailweergave voor de artiest: prijsopbouw, adres (pas na bevestiging, net als op de website)
     * en de acties die op de website bestaan, als serverpagina.
     *
     * @return array<string, mixed>
     */
    public static function artistBookingDetail(Booking $booking): array
    {
        $status = $booking->effectiveStatus();
        $studio = $booking->room->studio;
        $confirmed = $status === BookingStatus::Confirmed;
        $dashboard = route('dashboard.artist', [], false).'#boeking-'.$booking->id;

        $actions = [];
        if ($status === BookingStatus::PendingPayment) {
            $actions[] = ['label' => __('booking.finish_payment'), 'path' => route('bookings.payment', $booking, false), 'primary' => true];
        }
        if ($booking->canReschedule()) {
            $actions[] = ['label' => __('booking.reschedule_button'), 'path' => route('bookings.reschedule', $booking, false)];
        }
        if ($booking->canReportProblem()) {
            $actions[] = ['label' => __('booking.problem.title'), 'path' => $dashboard];
        }
        if (in_array($status, [BookingStatus::PendingConfirmation, BookingStatus::Confirmed], true)) {
            $actions[] = ['label' => __('booking.cancel_button'), 'path' => $dashboard, 'danger' => true];
        }
        if ($booking->wasPaid()) {
            $actions[] = ['label' => __('dashboard.artist_nav.invoices'), 'path' => route('artist.invoices.index', [], false)];
        }

        $prices = array_values(array_filter([
            ['label' => __('app.price.rent'), 'cents' => (int) $booking->rent_cents],
            $booking->engineer_cents ? ['label' => __('app.price.engineer'), 'cents' => (int) $booking->engineer_cents] : null,
            ['label' => __('app.price.service_fee'), 'cents' => (int) $booking->service_fee_cents],
            ['label' => __('app.price.vat'), 'cents' => (int) $booking->vat_cents],
        ]));

        return [
            ...self::artistBooking($booking),
            'note' => match ($status) {
                BookingStatus::PendingConfirmation => __('booking.waiting_note'),
                default => null,
            },
            'address' => $confirmed ? $studio->fullAddress() : null,
            'phone' => $confirmed ? $studio->phone : null,
            'prices' => $prices,
            'refunded_cents' => (int) ($booking->refunded_cents ?? 0),
            'studio_path' => $booking->room->isPubliclyVisible() ? route('studios.show', $booking->room, false) : null,
            'actions' => $actions,
        ];
    }

    /**
     * Een boeking zoals de verhuurder hem ziet: wie, welke ruimte, wanneer en wat hij ontvangt.
     *
     * @return array<string, mixed>
     */
    public static function hostBooking(Booking $booking): array
    {
        $status = $booking->effectiveStatus();

        return [
            'id' => $booking->id,
            'status' => $status->value,
            'status_label' => __('booking.status.'.$status->value),
            'badge' => $status->badgeClasses(),
            'artist' => $booking->user?->name ?? $booking->buyer_name,
            'room' => $booking->room->title,
            'studio' => $booking->room->studio->name,
            'date' => $booking->date->toDateString(),
            'starts_at' => $booking->startsAt()->toIso8601String(),
            'date_label' => $booking->dateRange(),
            'time_label' => $booking->timeRange(),
            'rent_cents' => (int) $booking->rent_cents,
            'with_engineer' => (bool) $booking->with_engineer,
            // Zonder reactie annuleert bookings:maintain de aanvraag 24 uur na het betalen.
            'respond_before' => $status === BookingStatus::PendingConfirmation && $booking->requested_at
                ? $booking->requested_at->copy()->addHours(24)->toIso8601String()
                : null,
        ];
    }

    /** @return array<string, mixed> */
    public static function agendaException(RoomException $exception): array
    {
        $closed = $exception->type === ExceptionType::Closed;

        return [
            'kind' => $closed ? 'closed' : 'block',
            'title' => $exception->room->title,
            'studio' => $exception->room->studio->name,
            'label' => $exception->label ?: __('host.availability.types.'.$exception->type->value),
            'time_label' => $closed || $exception->start_hour === null
                ? __('host.agenda.closed_all_day')
                : Hours::range((int) $exception->start_hour, (int) $exception->end_hour),
        ];
    }

    /** @return array<string, mixed> */
    public static function studio(Room $room): array
    {
        return [
            'id' => $room->id,
            'name' => $room->studio->name.' - '.$room->title,
            'city' => $room->studio->city,
            'type' => $room->typeLabel(),
            'price' => $room->displayHourlyEuros(),
            'day_price' => $room->displayDayEuros(),
            'photo' => self::url($room->photos->first()?->thumbUrl()),
            'path' => route('studios.show', $room, false),
        ];
    }

    /**
     * Foto's komen van de server, de app draait op een ander adres: altijd een volledig adres, en wel op
     * de server die de app aanspreekt. Storage::url plakt APP_URL ervoor; die kan anders zijn (een
     * testserver, of een telefoon die via het wifi-adres verbindt), dus alleen het pad houden.
     */
    private static function url(?string $url): ?string
    {
        return $url === null ? null : URL::to(parse_url($url, PHP_URL_PATH) ?? $url);
    }
}
