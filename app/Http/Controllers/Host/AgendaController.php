<?php

namespace App\Http\Controllers\Host;

use App\Enums\BookingStatus;
use App\Enums\ExceptionType;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\RoomException;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgendaController extends Controller
{

    public function __invoke(Request $request): View
    {
        $roomIds = $request->user()->rooms()->select('rooms.id');
        $from = today();
        $until = today()->addDays(28);

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
        $days = $bookings->flatMap(function (Booking $booking) use ($from, $until) {
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

        return view('host.agenda', ['days' => $days]);
    }
}
