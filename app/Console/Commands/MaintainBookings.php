<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\BookingCancelled;
use App\Notifications\SessionReminder;
use App\Support\StripeService;
use Illuminate\Console\Command;

class MaintainBookings extends Command
{
    protected $signature = 'bookings:maintain';

    protected $description = 'Laat verlopen betaalblokkades vrijvallen, annuleer onbeantwoorde aanvragen (BESLISSING 7) en rond voltooide sessies af';

    public function handle(): int
    {

        $expired = Booking::where('status', BookingStatus::PendingPayment)
            ->where('expires_at', '<', now())
            ->update(['status' => BookingStatus::Expired]);

        $stale = Booking::where('status', BookingStatus::PendingConfirmation)
            ->with(['room.studio.user', 'user'])
            ->get()
            ->filter(fn (Booking $booking) => ($booking->requested_at ?? $booking->created_at)->addDay()->isPast()
                || $booking->startsAt()->subHours(2)->isPast());

        foreach ($stale as $booking) {
            $booking->update(['status' => BookingStatus::Cancelled, 'cancelled_by' => 'auto']);
            StripeService::refund($booking, $booking->refundAmountCents(100));
            $booking->user->notify(new BookingCancelled($booking, 100));
            $booking->room->studio->user->notify(new BookingCancelled($booking, 100));
        }

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

        $completed = Booking::where('status', BookingStatus::Confirmed)
            ->get()
            ->filter(fn (Booking $booking) => $booking->endsAt()->isPast())
            ->each(fn (Booking $booking) => $booking->update(['status' => BookingStatus::Completed]))
            ->count();

        $transfers = 0;

        if (StripeService::enabled()) {
            $due = Booking::whereIn('status', [BookingStatus::Completed, BookingStatus::Cancelled])
                ->whereNull('transferred_at')
                ->whereNotNull('stripe_payment_intent_id')
                ->with('room.studio.user.hostProfile')
                ->get()
                ->filter(fn (Booking $booking) => $booking->startsAt()->addHours(24)->isPast()
                    && $booking->hostPayoutCents() > 0);

            foreach ($due as $booking) {
                if (StripeService::transfer($booking)) {
                    $transfers++;
                }
            }
        }

        $this->info("Verlopen: {$expired}, automatisch geannuleerd: {$stale->count()}, herinneringen: {$reminders->count()}, voltooid: {$completed}, uitbetalingen: {$transfers}.");

        return self::SUCCESS;
    }
}
