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
    /**
     * Agendaweergave (scope §2.9): bevestigde sessies, aanvragen en blokkades
     * van de komende vier weken, gegroepeerd per dag.
     */
    public function __invoke(Request $request): View
    {
        $roomIds = $request->user()->rooms()->select('rooms.id');
        $from = today();
        $until = today()->addDays(28);

        $bookings = Booking::whereIn('room_id', $roomIds)
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::PendingConfirmation])
            ->whereBetween('date', [$from->toDateString(), $until->toDateString()])
            ->with(['room.studio', 'user'])
            ->get();

        $exceptions = RoomException::whereIn('room_id', $roomIds)
            ->whereBetween('date', [$from->toDateString(), $until->toDateString()])
            ->whereIn('type', [ExceptionType::Block, ExceptionType::Closed])
            ->with('room.studio')
            ->get();

        // Eén lijst per dag: boekingen en blokkades door elkaar, op tijd gesorteerd.
        $days = $bookings->map(fn (Booking $booking) => [
            'date' => $booking->date->toDateString(),
            'sort' => (int) $booking->start_hour,
            'kind' => 'booking',
            'item' => $booking,
        ])->concat($exceptions->map(fn (RoomException $exception) => [
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
