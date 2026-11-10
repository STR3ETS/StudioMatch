<?php

namespace App\Support;

use App\Actions\ChangeArtistBooking;
use App\Enums\BookingStatus;
use App\Enums\ExceptionType;
use App\Enums\RoomStatus;
use App\Models\Booking;
use App\Models\Post;
use App\Models\Room;
use App\Models\RoomException;
use App\Models\User;
use Illuminate\Support\Carbon;
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
        // Met 'screen' heeft de app er een eigen scherm voor, met 'path' opent hij de serverpagina.
        $actions = [];
        if ($status === BookingStatus::PendingPayment) {
            $actions[] = ['label' => __('booking.finish_payment'), 'screen' => 'betalen/'.$booking->id, 'path' => route('bookings.payment', $booking, false), 'primary' => true];
        }
        if ($booking->canReschedule()) {
            $actions[] = ['label' => __('booking.reschedule_button'), 'screen' => 'verzetten/'.$booking->id, 'path' => route('bookings.reschedule', $booking, false)];
        }
        if ($booking->wasPaid()) {
            $actions[] = ['label' => __('dashboard.artist_nav.invoices'), 'path' => route('artist.invoices.index', [], false)];
        }
        if ($booking->canReportProblem()) {
            $actions[] = ['label' => __('booking.problem.title'), 'path' => $dashboard];
        }
        if (ChangeArtistBooking::canCancel($booking)) {
            $actions[] = ['label' => __('booking.cancel_button'), 'cancel' => self::cancelQuestion($booking), 'path' => $dashboard, 'danger' => true];
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
            'studio_id' => $booking->room->isPubliclyVisible() ? $booking->room_id : null,
            'actions' => $actions,
            'payment' => $status === BookingStatus::PendingPayment ? [
                'expires_at' => $booking->expires_at->toIso8601String(),
                'hold_note' => __('booking.payment.hold_note', ['time' => $booking->expires_at->format('H:i')]),
                // Met Stripe betaal je op de beveiligde pagina van Stripe (via de serverpagina), anders is het
                // een demo en rondt de app de betaling zelf af.
                'stripe' => StripeService::enabled(),
                'note' => StripeService::enabled() ? __('booking.payment.stripe_note') : __('booking.payment.demo_note'),
                'submit' => __('booking.payment.submit', ['amount' => '€ '.number_format($booking->total_cents / 100, 2, ',', '.')]),
            ] : null,
        ];
    }

    /**
     * Wat de artiest terugkrijgt als hij nu annuleert, voor de vraag vooraf.
     */
    private static function cancelQuestion(Booking $booking): string
    {
        $percent = $booking->refundPercentForArtistCancellation();
        $amount = '€ '.number_format($booking->refundAmountCents($percent) / 100, 2, ',', '.');

        return match (true) {
            $percent >= 100 => __('app.cancel.full', ['amount' => $amount]),
            $percent > 0 => __('app.cancel.partial', ['amount' => $amount, 'percent' => $percent]),
            default => __('app.cancel.none'),
        };
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
     * De studiopagina in de app: wat er op de website staat, plus alles om te kiezen (vrije uren per dag,
     * vrije hele dagen) en de prijs vooraf te berekenen zoals de server dat doet (BookRoom::prices).
     *
     * @return array<string, mixed>
     */
    public static function studioDetail(Room $room): array
    {
        $room->loadMissing(['studio.user.hostProfile', 'photos', 'hours', 'exceptions']);
        $studio = $room->studio;
        $money = fn (?int $cents) => '€ '.number_format(($cents ?? 0) / 100, 2, ',', '.');
        $translate = fn (?array $values, string $prefix) => collect($values ?? [])->map(fn ($value) => __($prefix.$value))->values()->all();
        $multiDay = $room->allowsMultiDay();
        $engineerOptional = $room->hasOptionalEngineer();

        $engineer = match (true) {
            (bool) $room->engineer_included => __('studio.facts.engineer_yes'),
            $engineerOptional => __('studio.facts.engineer_optional', ['amount' => $money($room->engineer_rate_cents)]),
            default => __('studio.facts.engineer_no'),
        };

        return [
            ...self::studio($room),
            'studio' => $studio->name,
            'room' => $room->title,
            'photos' => $room->photos->map(fn ($photo) => self::url($photo->url()))->values(),
            'facts' => [
                ['icon' => 'fa-users', 'text' => __('studio.facts.capacity', ['count' => $room->capacity])],
                ['icon' => 'fa-clock', 'text' => __('studio.facts.min_duration', ['count' => $room->min_hours])],
                ['icon' => 'fa-headphones', 'text' => $engineer],
            ],
            'description' => $room->description,
            'equipment' => array_values(array_filter([...$translate($room->equipment, 'studios.equipment.'), $room->equipment_extra])),
            'daws' => array_values($room->daws ?? []),
            'facilities' => $translate($room->facilities, 'studios.facilities.'),
            'rules' => self::houseRules($room),
            'opening_hours' => $room->hours->sortBy('weekday')->map(fn ($day) => [
                'day' => __('host.availability.days.'.$day->weekday),
                'today' => (int) $day->weekday === now()->isoWeekday(),
                'hours' => $day->is_open ? Hours::openingRange((int) $day->weekday, (int) $day->open_hour, (int) $day->close_hour) : null,
            ])->values(),
            'night_note' => $room->hours->contains(fn ($day) => $day->is_open && (int) $day->close_hour > 24) ? __('studio.opening_hours_night') : null,
            'owner' => $studio->user?->hostProfile ? __('studio.owner_'.$studio->user->hostProfile->owner_type->value) : null,
            'booking' => [
                'hourly_cents' => (int) $room->hourly_rate_cents,
                'min_hours' => (int) $room->min_hours,
                'max_hours' => (int) config('studio.booking_max_hours'),
                'free_hours' => (object) $room->freeHoursByDate(),
                'multi_day' => $multiDay,
                'free_days' => $multiDay ? $room->freeWholeDays() : [],
                'day_cents' => (int) $room->day_rate_cents,
                'day_rates' => (object) ($multiDay ? $room->dayRatesByWeekday() : []),
                'min_days' => max(1, (int) ($room->min_days ?? 2)),
                'max_days' => (int) config('studio.booking_max_days'),
                'engineer_optional' => $engineerOptional,
                'engineer_hour_cents' => $engineerOptional ? (int) $room->engineer_rate_cents : 0,
                'engineer_day_cents' => $engineerOptional ? $room->engineerDayRateCents() : 0,
                'engineer_hour_text' => $engineerOptional ? __('studio.booking.engineer_toggle', ['amount' => $money($room->engineer_rate_cents)]) : null,
                'engineer_day_text' => $engineerOptional ? __('studio.booking.engineer_toggle_day', ['amount' => $money($room->engineerDayRateCents())]) : null,
                'fee_percent' => (float) config('studio.service_fee_percent'),
                'vat_percent' => (float) config('studio.vat_percent'),
            ],
        ];
    }

    /**
     * Afrekenen in de app: het gekozen moment zoals de server het heeft gecontroleerd, met de prijs.
     *
     * @param  array{date: Carbon, end_date: ?Carbon, start: ?int, end: ?int, days: ?int, engineer: bool}  $period
     * @param  array<string, int>  $prices
     * @return array<string, mixed>
     */
    public static function checkout(Room $room, User $user, array $period, array $prices): array
    {
        $room->loadMissing(['studio', 'photos']);
        $days = $period['days'];

        $rent = $days !== null
            ? trans_choice('booking.day_count', $days, ['count' => $days])
            : __('studio.booking.hours', ['count' => $period['end'] - $period['start']]);

        return [
            'room_id' => $room->id,
            'name' => $room->studio->name.' - '.$room->title,
            'city' => $room->studio->city,
            'photo' => self::url($room->photos->first()?->thumbUrl()),
            'date_label' => $days !== null
                ? $period['date']->translatedFormat('j F Y').' - '.$period['end_date']->translatedFormat('j F Y')
                : $period['date']->translatedFormat('l j F Y'),
            'time_label' => $days !== null ? null : Hours::bookingRange($period['date'], $period['start'], $period['end']),
            'prices' => [
                // "Huur (2 uur)": niet "Totaal", dat staat er al onder.
                ['label' => __('app.price.rent').' ('.$rent.($period['engineer'] ? ', '.__('booking.checkout.with_engineer') : '').')', 'cents' => $prices['rent_cents']],
                ['label' => __('studio.booking.service_fee'), 'cents' => $prices['service_fee_cents']],
                ['label' => __('studio.booking.vat'), 'cents' => $prices['vat_cents']],
            ],
            'total_cents' => $prices['total_cents'],
            'rules' => self::houseRules($room),
            'needs_address' => ! $user->hasCompleteAddress(),
            'address' => ['street' => $user->street, 'postal_code' => $user->postal_code, 'city' => $user->city],
            'hold_minutes' => (int) config('studio.checkout_hold_minutes'),
        ];
    }

    /** @return array<int, string> */
    private static function houseRules(Room $room): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $room->house_rules))->map(fn ($rule) => trim($rule))->filter()->values()->all();
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
