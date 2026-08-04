<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\BookingCancelled;
use App\Notifications\SessionReminder;
use Illuminate\Console\Command;

class MaintainBookings extends Command
{
    protected $signature = 'bookings:maintain';

    protected $description = 'Laat verlopen betaalblokkades vrijvallen, annuleer onbeantwoorde aanvragen (BESLISSING 7) en rond voltooide sessies af';

    public function handle(): int
    {
        // 1. Checkout-blokkade van 15 minuten verlopen → slot komt vrij (scope §2.5).
        $expired = Booking::where('status', BookingStatus::PendingPayment)
            ->where('expires_at', '<', now())
            ->update(['status' => BookingStatus::Expired]);

        // 2. Geen reactie van de verhuurder binnen 24 uur, of de start is dichtbij:
        //    automatisch annuleren + 100% terug + mail naar beide partijen (BESLISSING 7).
        //    De termijn telt vanaf de (laatste) aanvraag, dus ook na verzetten.
        $stale = Booking::where('status', BookingStatus::PendingConfirmation)
            ->with(['room.studio.user', 'user'])
            ->get()
            ->filter(fn (Booking $booking) => ($booking->requested_at ?? $booking->created_at)->addDay()->isPast()
                || $booking->startsAt()->subHours(2)->isPast());

        foreach ($stale as $booking) {
            $booking->update(['status' => BookingStatus::Cancelled, 'cancelled_by' => 'auto']);
            $booking->user->notify(new BookingCancelled($booking, 100));
            $booking->room->studio->user->notify(new BookingCancelled($booking, 100));
        }

        // 3. Herinnering 24 uur vóór de sessie naar beide partijen (mailmatrix §2.10).
        $reminders = Booking::where('status', BookingStatus::Confirmed)
            ->whereNull('reminder_sent_at')
            ->with(['room.studio.user', 'user'])
            ->get()
            ->filter(fn (Booking $booking) => $booking->startsAt()->isFuture()
                && $booking->startsAt()->subHours(24)->isPast());

        foreach ($reminders as $booking) {
            $booking->update(['reminder_sent_at' => now()]);
            $booking->user->notify(new SessionReminder($booking));
            $booking->room->studio->user->notify(new SessionReminder($booking));
        }

        // 4. Bevestigde sessies waarvan de eindtijd voorbij is → voltooid.
        $completed = Booking::where('status', BookingStatus::Confirmed)
            ->get()
            ->filter(fn (Booking $booking) => $booking->endsAt()->isPast())
            ->each(fn (Booking $booking) => $booking->update(['status' => BookingStatus::Completed]))
            ->count();

        $this->info("Verlopen: {$expired}, automatisch geannuleerd: {$stale->count()}, herinneringen: {$reminders->count()}, voltooid: {$completed}.");

        return self::SUCCESS;
    }
}
