<?php

namespace App\Http\Controllers;

use App\Actions\BookRoom;
use App\Actions\ChangeArtistBooking;
use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Notifications\NewTicket;
use App\Notifications\ProblemReported;
use App\Support\InApp;
use App\Support\MailDelivery;
use App\Support\StripeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function create(Request $request, Room $room, BookRoom $book): View|RedirectResponse|Response
    {
        abort_unless($room->isPubliclyVisible(), 404);

        // In de app boek je in het scherm van de studio zelf.
        if (InApp::origin($request) !== null && $request->user()->isArtist()) {
            return InApp::toApp('#/studio/'.$room->id, $request);
        }

        $period = $book->period($room, $request->all());

        return view('book.checkout', [
            'room' => $room->load(['studio', 'photos']),
            'date' => $period['date'],
            'endDate' => $period['end_date'],
            'days' => $period['days'],
            'startHour' => $period['start'],
            'endHour' => $period['end'],
            'withEngineer' => $period['engineer'],
            'prices' => $book->prices($room, $period),
        ]);
    }

    public function store(Request $request, Room $room, BookRoom $book): RedirectResponse
    {
        abort_unless($room->isPubliclyVisible(), 404);

        $booking = $book->reserve($request->user(), $room, $request->all());

        return redirect()->route('bookings.payment', $booking);
    }

    public function payment(Request $request, Booking $booking): View|RedirectResponse|Response
    {
        $this->authorizeBooking($request, $booking);

        if ($booking->status !== BookingStatus::PendingPayment) {
            return $this->finished($booking);
        }

        if ($booking->expires_at->isPast()) {
            $booking->update(['status' => BookingStatus::Expired]);

            return $this->backToRoom($booking, __('booking.errors.expired'));
        }

        return view('book.payment', [
            'booking' => $booking->load('room.studio'),
            'stripeEnabled' => StripeService::enabled(),
        ]);
    }

    public function pay(Request $request, Booking $booking): RedirectResponse|Response
    {
        $this->authorizeBooking($request, $booking);

        abort_unless($booking->status === BookingStatus::PendingPayment, 404);

        if ($booking->expires_at->isPast()) {
            $booking->update(['status' => BookingStatus::Expired]);

            return $this->backToRoom($booking, __('booking.errors.expired'));
        }

        if (StripeService::enabled()) {
            $url = StripeService::createCheckoutSession($booking);

            if ($url === null) {
                return back()->withErrors(['payment' => __('booking.errors.payment_failed')]);
            }

            return redirect()->away($url);
        }

        $booking->markAsPaid();

        return $this->finished($booking, __('booking.paid'));
    }

    public function paid(Request $request, Booking $booking): RedirectResponse|Response
    {
        $this->authorizeBooking($request, $booking);

        $sessionId = (string) $request->query('session_id');

        if ($sessionId === '' || ! StripeService::verifyCheckoutPaid($booking, $sessionId)) {
            return redirect()->route('bookings.payment', $booking);
        }

        if (! $booking->markAsPaid()) {
            $booking->refresh();

            if ($booking->status === BookingStatus::Expired) {
                StripeService::refund($booking, $booking->total_cents);

                return $this->backToRoom($booking, __('booking.errors.expired_refunded'));
            }
        }

        return $this->finished($booking, __('booking.paid'));
    }

    public function cancel(Request $request, Booking $booking, ChangeArtistBooking $change): RedirectResponse|Response
    {
        $this->authorizeBooking($request, $booking);

        $change->cancel($booking);

        return $this->finished($booking, __('booking.cancelled'));
    }

    public function reschedule(Request $request, Booking $booking): View|Response
    {
        $this->authorizeBooking($request, $booking);

        abort_unless($booking->canReschedule(), 404);

        // In de app verzet je in een eigen scherm.
        if (InApp::origin($request) !== null) {
            return InApp::toApp('#/verzetten/'.$booking->id, $request);
        }

        return view('book.reschedule', [
            'booking' => $booking->load('room.studio'),
            'freeHours' => $booking->room->freeHoursByDate(),
        ]);
    }

    public function rescheduleStore(Request $request, Booking $booking, ChangeArtistBooking $change): RedirectResponse|Response
    {
        $this->authorizeBooking($request, $booking);

        $change->reschedule($booking, $request->all());

        return $this->finished($booking, __('booking.rescheduled'));
    }

    public function reportProblem(Request $request, Booking $booking): RedirectResponse|Response
    {
        $this->authorizeBooking($request, $booking);

        abort_unless($booking->canReportProblem(), 404);

        $validated = $request->validate([
            'dispute_reason' => ['required', 'string', 'min:10', 'max:2000'],
            'dispute_studio_response' => ['nullable', 'string', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $photos = collect($validated['photos'] ?? [])
            ->map(fn ($photo) => $photo->store('disputes/'.$booking->id, 'public'))
            ->all();

        $booking->update([
            'status' => BookingStatus::Disputed,
            'disputed_at' => now(),
            'dispute_reason' => $validated['dispute_reason'],
            'dispute_studio_response' => $validated['dispute_studio_response'] ?? null,
            'dispute_photos' => $photos !== [] ? $photos : null,
        ]);

        $booking->room->studio->user->notify(new ProblemReported($booking));
        $admins = User::where('role', UserRole::Admin)->get();
        Notification::send($admins, new ProblemReported($booking));
        // En een pushmelding naar de telefoon van de beheerders (Tickets in de app).
        MailDelivery::send($admins, new NewTicket($booking, 'dispute'));

        return $this->finished($booking, __('booking.problem_reported'));
    }

    public function ics(Request $request, Booking $booking): Response
    {
        $this->authorizeBooking($request, $booking);

        abort_unless($booking->status === BookingStatus::Confirmed, 404);

        $escape = fn (string $value) => str_replace([',', ';', "\n"], ['\,', '\;', '\n'], $value);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//StudioMatch//NL',
            'BEGIN:VEVENT',
            'UID:artist-booking-'.$booking->id.'@studiomatch',
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$booking->startsAt()->copy()->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$booking->endsAt()->copy()->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.$escape(__('booking.ics_summary', ['room' => $booking->room->studio->name.' - '.$booking->room->title])),
            'LOCATION:'.$escape($booking->room->studio->fullAddress()),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return response(implode("\r\n", $lines), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="studiomatch-boeking-'.$booking->id.'.ics"',
        ]);
    }

    /**
     * De reservering is verlopen: opnieuw kiezen. Op de website op de studiopagina met de melding, in de app
     * in het scherm van de studio (een redirect naar de app werkt daar niet, zie InApp::toApp).
     */
    private function backToRoom(Booking $booking, string $message): RedirectResponse|Response
    {
        if (InApp::active()) {
            return InApp::toApp('#/studio/'.$booking->room_id);
        }

        return redirect()->route('studios.show', $booking->room)->withErrors(['slot' => $message]);
    }

    private function authorizeBooking(Request $request, Booking $booking): void
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
    }

    /**
     * Klaar met betalen, annuleren, verzetten of een probleem melden: op de website naar het overzicht met de
     * melding, in de iOS/Android-app terug naar deze boeking in de app zelf (daar staat de nieuwe status),
     * in plaats van de websitepagina met het overzicht.
     */
    private function finished(Booking $booking, ?string $message = null): RedirectResponse|Response
    {
        if (InApp::active()) {
            return InApp::toApp('#/boeking/'.$booking->id);
        }

        return redirect()->route('dashboard.artist')->with(array_filter(['status' => $message]));
    }
}
