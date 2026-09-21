<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Opening hours and bookings are stored as an hour offset from midnight of the starting
 * date. Anything above 24 therefore falls on the next calendar day: 25 is 01:00 the next
 * morning. This keeps a night session in one record instead of splitting it over two days.
 *
 * Naar buiten toe noemen we die volgende dag altijd bij naam, want een markering als "+1"
 * bleek onduidelijk voor bezoekers.
 */
class Hours
{

    public static function max(): int
    {
        return (int) config('studio.latest_close_hour', 30);
    }

    /**
     * Clock time without any day marker: 25 becomes "01:00".
     */
    public static function clock(int $hour): string
    {
        return $hour === 24 ? '24:00' : sprintf('%02d:00', $hour % 24);
    }

    /**
     * Clock time for a dropdown, met de dag erbij zodra het na middernacht valt.
     */
    public static function label(int $hour): string
    {
        return $hour > 24
            ? self::clock($hour) . ' ' . __('booking.next_day_label')
            : self::clock($hour);
    }

    /**
     * "09:00 – 21:00", of "22:00 – 02:00" als het doorloopt. Zonder dagaanduiding, voor
     * plekken waar de datum er al naast staat.
     */
    public static function range(int $startHour, int $endHour): string
    {
        return self::clock($startHour) . ' – ' . self::clock($endHour);
    }

    /**
     * Openingstijden per weekdag: "06:00 – 02:00 (di)" als de studio na middernacht sluit.
     */
    public static function openingRange(int $weekday, int $openHour, int $closeHour): string
    {
        $range = self::range($openHour, $closeHour);

        if ($closeHour <= 24) {
            return $range;
        }

        return $range . ' (' . self::weekdayShort($weekday + 1) . ')';
    }

    /**
     * Boekingsperiode met dagnamen zodra hij over middernacht loopt:
     * "maandag 21:00 – dinsdag 02:00". Blijft het binnen de dag, dan alleen de tijden.
     */
    public static function bookingRange(CarbonInterface $date, int $startHour, int $endHour): string
    {
        if ($endHour <= 24) {
            return self::range($startHour, $endHour);
        }

        $start = $date->copy()->startOfDay()->addHours($startHour);
        $end = $date->copy()->startOfDay()->addHours($endHour);

        return $start->translatedFormat('l') . ' ' . self::clock($startHour)
            . ' – ' . $end->translatedFormat('l') . ' ' . self::clock($endHour);
    }

    /**
     * Afgekorte dagnaam voor weekdag 1 tot en met 7, waarbij 8 weer maandag is.
     */
    public static function weekdayShort(int $weekday): string
    {
        $weekday = (($weekday - 1) % 7) + 1;

        $name = Carbon::now()->startOfWeek()->addDays($weekday - 1)->translatedFormat('D');

        return ucfirst(rtrim($name, '.'));
    }
}
