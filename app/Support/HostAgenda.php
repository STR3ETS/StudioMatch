<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\ExceptionType;
use App\Models\Booking;
use App\Models\RoomException;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * De agenda van een verhuurder: sessies, aanvragen en blokkades per dag. Gedeeld door de agenda op de
 * website (Host\AgendaController, vier weken) en de app (Api\App\HostAgendaController, een week).
 */
class HostAgenda
{
    /**
     * @return Collection<string, Collection<int, array{date: string, sort: int, kind: string, item: Booking|RoomException}>>
     */
    public static function days(User $host, CarbonInterface $from, CarbonInterface $until): Collection
    {
        $roomIds = $host->rooms()->select('rooms.id');

        $bookings = Booking::whereIn('room_id', $roomIds)
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::PendingConfirmation])
            ->where(function ($query) use ($from, $until) {
                $query->whereBetween('date', [$from->toDateString(), $until->toDateString()])
                    ->orWhere(function ($query) use ($from, $until) {
                        // Meerdaagse boeking die al eerder begon maar nog loopt.
                        $query->whereNotNull('end_date')
                            ->whereDate('date', '<=', $until)
                            ->whereDate('end_date', '>=', $from);
                    });
            })
            ->with(['room.studio', 'user'])
            ->get();

        $exceptions = RoomException::whereIn('room_id', $roomIds)
            ->whereBetween('date', [$from->toDateString(), $until->toDateString()])
            ->whereIn('type', [ExceptionType::Block, ExceptionType::Closed])
            ->with('room.studio')
            ->get();

        // Een meerdaagse boeking staat op elke dag die hij bezet, niet alleen op de startdag.
        return $bookings->flatMap(function (Booking $booking) use ($from, $until) {
            $entries = [];
            $last = $booking->end_date?->copy() ?? $booking->date->copy();

            for ($date = $booking->date->copy(); $date->lte($last); $date->addDay()) {
                if ($date->betweenIncluded($from, $until)) {
                    $entries[] = [
                        'date' => $date->toDateString(),
                        'sort' => (int) $booking->start_hour,
                        'kind' => 'booking',
                        'item' => $booking,
                    ];
                }
            }

            return $entries;
        })->concat($exceptions->map(fn (RoomException $exception) => [
            'date' => $exception->date->toDateString(),
            'sort' => (int) ($exception->start_hour ?? 0),
            'kind' => $exception->type === ExceptionType::Closed ? 'closed' : 'block',
            'item' => $exception,
        ]))
            ->sortBy([['date', 'asc'], ['sort', 'asc']])
            ->groupBy('date');
    }
}
