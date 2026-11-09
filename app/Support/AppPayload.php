<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\ExceptionType;
use App\Enums\RoomStatus;
use App\Models\Booking;
use App\Models\Post;
use App\Models\Room;
use App\Models\RoomException;
use App\Models\User;
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

        // Volgorde: wat je meestal wilt eerst, annuleren altijd als laatste (de app zet het apart onderaan).
        $actions = [];
        if ($status === BookingStatus::PendingPayment) {
            $actions[] = ['label' => __('booking.finish_payment'), 'path' => route('bookings.payment', $booking, false), 'primary' => true];
        }
        if ($booking->canReschedule()) {
            $actions[] = ['label' => __('booking.reschedule_button'), 'path' => route('bookings.reschedule', $booking, false)];
        }
        if ($booking->wasPaid()) {
            $actions[] = ['label' => __('dashboard.artist_nav.invoices'), 'path' => route('artist.invoices.index', [], false)];
        }
        if ($booking->canReportProblem()) {
            $actions[] = ['label' => __('booking.problem.title'), 'path' => $dashboard];
        }
        if (in_array($status, [BookingStatus::PendingConfirmation, BookingStatus::Confirmed], true)) {
            $actions[] = ['label' => __('booking.cancel_button'), 'path' => $dashboard, 'danger' => true];
        }

        $prices = array_values(array_filter([
            ['label' => __('app.price.rent'), 'cents' => (int) $booking->rent_cents],
            $booking->engineer_cents ? ['label' => __('app.price.engineer'), 'cents' => (int) $booking->engineer_cents] : null,
            ['label' => __('app.price.service_fee'), 'cents' => (int) $booking->service_fee_cents],
            ['label' => __('app.price.vat'), 'cents' => (int) $booking->vat_cents],
        ]));

        return [
            ...self::artistBooking($booking),
            // Wat er nu gebeurt, bij de statussen waar dat niet vanzelf spreekt.
            'note' => match ($status) {
                BookingStatus::PendingConfirmation => __('booking.waiting_note'),
                BookingStatus::Expired => __('app.notes.expired'),
                BookingStatus::Disputed => __('app.notes.disputed'),
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
            // Tikken op een sessie: een aanvraag beantwoord je in de app (Aanvragen), de rest staat bij de
            // boekingen op de website (contactgegevens, schade melden).
            'path' => $status === BookingStatus::PendingConfirmation ? null : route('host.bookings.index', [], false),
        ];
    }

    /**
     * Een ruimte in de goedkeuringswachtrij, voor de lijst.
     *
     * @return array<string, mixed>
     */
    public static function adminRoom(Room $room): array
    {
        return [
            'id' => $room->id,
            'title' => $room->title,
            'studio' => $room->studio->name,
            'city' => $room->studio->city,
            'host' => $room->studio->user->name,
            'photo' => self::url($room->photos->first()?->thumbUrl()),
            'since' => $room->updated_at->toIso8601String(),
        ];
    }

    /**
     * Alles wat de beheerder nodig heeft om een ruimte te beoordelen, zoals de beoordelingspagina op de
     * website (admin.queue.show).
     *
     * @return array<string, mixed>
     */
    public static function adminRoomDetail(Room $room, bool $stripeBlocked): array
    {
        $euro = fn (?int $cents) => $cents === null ? null : (int) $cents;

        return [
            ...self::adminRoom($room),
            'status' => $room->status->value,
            'status_label' => __('host.status.'.$room->status->value),
            'in_review' => $room->status === RoomStatus::InReview,
            'address' => $room->studio->fullAddress(),
            'host_email' => $room->studio->user->email,
            'photos' => $room->photos->map(fn ($photo) => self::url($photo->url()))->values(),
            'type' => __('host.types.'.$room->type->value),
            'hourly_cents' => $euro($room->hourly_rate_cents),
            'day_cents' => $room->allowsMultiDay() ? $euro($room->day_rate_cents) : null,
            'min_hours' => (int) $room->min_hours,
            'capacity' => (int) $room->capacity,
            // "Technicus: altijd inbegrepen" (los staat er anders alleen "Altijd inbegrepen").
            'engineer' => $room->engineer_included || $room->hasOptionalEngineer()
                ? __('app.price.engineer').': '.mb_strtolower(__($room->engineer_included ? 'host.rooms.fields.engineer_included' : 'host.rooms.fields.engineer_optional'))
                : null,
            'description' => $room->description,
            'equipment' => collect($room->equipment ?? [])->map(fn ($item) => __('studios.equipment.'.$item))
                ->concat($room->daws ?? [])->values(),
            'equipment_extra' => $room->equipment_extra,
            'facilities' => collect($room->facilities ?? [])->map(fn ($item) => __('studios.facilities.'.$item))->values(),
            'house_rules' => $room->house_rules,
            'hours' => $room->hours->sortBy('weekday')->map(fn ($day) => [
                'day' => __('host.availability.days.'.$day->weekday),
                'label' => $day->is_open ? Hours::openingRange((int) $day->weekday, (int) $day->open_hour, (int) $day->close_hour) : __('studio.closed'),
            ])->values(),
            'stripe_blocked' => $stripeBlocked,
            'stripe_message' => $stripeBlocked ? __('admin.queue.stripe_required', ['host' => $room->studio->user->name]) : null,
        ];
    }

    /**
     * Een boeking in het overzicht van de beheerder, met de statussen waarin hij hem kan zetten.
     *
     * @return array<string, mixed>
     */
    public static function adminBooking(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'status' => $booking->status->value,
            'status_label' => __('booking.status.'.$booking->status->value),
            'room' => $booking->room->studio->name.' - '.$booking->room->title,
            'artist' => $booking->user?->name ?? $booking->buyer_name,
            'date_label' => $booking->dateRange(),
            'time_label' => $booking->timeRange(),
            'total_cents' => (int) $booking->total_cents,
        ];
    }

    /** @return array<string, mixed> */
    public static function adminUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'role_label' => $user->isHost() ? __('admin.users.role_host') : __('admin.users.role_artist'),
            'bookings' => (int) $user->bookings_count,
            'studios' => (int) $user->studios_count,
            'since' => $user->created_at->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    public static function adminPost(Post $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'status' => $post->status->value,
            'status_label' => $post->status->label(),
            'scheduled' => $post->isScheduled() ? $post->published_at?->toIso8601String() : null,
            'sections' => (int) $post->sections_count,
            'author' => $post->author?->name,
            'updated_at' => $post->updated_at->toIso8601String(),
            'edit_path' => route('admin.posts.edit', $post, false),
            'view_path' => route('blog.show', $post->slug, false),
        ];
    }

    /**
     * Een ticket voor de beheerder: een probleem dat een artiest meldde ('dispute') of een probleemmelding
     * van een verhuurder ('damage'). Het pad opent de ticketpagina op de website op dit ticket.
     *
     * @return array<string, mixed>
     */
    public static function adminTicket(Booking $booking, string $kind): array
    {
        $dispute = $kind === 'dispute';
        $room = $booking->room;
        $host = $room->studio->user;

        return [
            'id' => $booking->id,
            'kind' => $kind,
            'title' => $room->studio->name.' - '.$room->title,
            'date_label' => $booking->dateRange(),
            'time_label' => $booking->timeRange(),
            'artist' => $booking->user?->name ?? $booking->buyer_name,
            'host' => $host?->name,
            'total_cents' => (int) $booking->total_cents,
            'reported_at' => ($dispute ? $booking->disputed_at : $booking->damage_reported_at)?->toIso8601String(),
            'reason' => $dispute ? $booking->dispute_reason : $booking->damage_reason,
            'studio_response' => $dispute ? $booking->dispute_studio_response : null,
            'photos' => count(($dispute ? $booking->dispute_photos : $booking->damage_photos) ?? []),
            'open' => $dispute || $booking->damage_resolved_at === null,
            'response' => $dispute ? null : $booking->damage_response,
            'path' => route('admin.tickets.index', [], false).'#'.($dispute ? 'ticket-' : 'melding-').$booking->id,
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
