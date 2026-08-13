<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\ExceptionType;
use App\Models\Room;
use Illuminate\Http\Response;

class IcalController extends Controller
{

    public function __invoke(Room $room): Response
    {
        $room->load('studio');

        $events = [];

        $bookings = $room->bookings()
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed, BookingStatus::Disputed])
            ->whereDate('date', '>=', today()->subDays(30))
            ->get();

        foreach ($bookings as $booking) {
            $events[] = [
                'uid' => 'booking-' . $booking->id . '@studiomatch',
                'start' => $booking->startsAt(),
                'end' => $booking->endsAt(),
                'summary' => __('host.ical.booking_summary', ['room' => $room->title]),
            ];
        }

        $exceptions = $room->exceptions()
            ->whereDate('date', '>=', today()->subDays(30))
            ->whereIn('type', [ExceptionType::Block, ExceptionType::Closed])
            ->get();

        foreach ($exceptions as $exception) {
            $start = $exception->date->copy()->startOfDay()->addHours((int) ($exception->start_hour ?? 0));
            $end = $exception->type === ExceptionType::Closed
                ? $exception->date->copy()->startOfDay()->addHours(24)
                : $exception->date->copy()->startOfDay()->addHours((int) $exception->end_hour);

            $events[] = [
                'uid' => 'exception-' . $exception->id . '@studiomatch',
                'start' => $start,
                'end' => $end,
                'summary' => $exception->label ?: __('host.ical.block_summary', ['room' => $room->title]),
            ];
        }

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//StudioMatch//NL',
            'X-WR-CALNAME:' . $this->escape('StudioMatch - ' . $room->studio->name . ' - ' . $room->title),
        ];

        foreach ($events as $event) {
            $lines = [
                ...$lines,
                'BEGIN:VEVENT',
                'UID:' . $event['uid'],
                'DTSTAMP:' . now()->utc()->format('Ymd\THis\Z'),
                'DTSTART:' . $event['start']->copy()->utc()->format('Ymd\THis\Z'),
                'DTEND:' . $event['end']->copy()->utc()->format('Ymd\THis\Z'),
                'SUMMARY:' . $this->escape($event['summary']),
                'END:VEVENT',
            ];
        }

        $lines[] = 'END:VCALENDAR';

        return response(implode("\r\n", $lines), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="studiomatch.ics"',
        ]);
    }

    private function escape(string $value): string
    {
        return str_replace([',', ';', "\n"], ['\,', '\;', '\n'], $value);
    }
}
