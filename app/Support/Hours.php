<?php

namespace App\Support;

/**
 * Opening hours and bookings are stored as an hour offset from midnight of the starting
 * date. Anything above 24 therefore falls on the next calendar day: 25 is 01:00 the next
 * morning. This keeps a night session in one record instead of splitting it over two days.
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
     * Clock time with a marker when it lands on the next day, for dropdowns.
     */
    public static function label(int $hour): string
    {
        return $hour > 24
            ? self::clock($hour) . ' ' . __('booking.next_day_label')
            : self::clock($hour);
    }

    /**
     * "22:00 – 02:00 (+1)" for a session that runs past midnight.
     */
    public static function range(int $startHour, int $endHour): string
    {
        $range = self::clock($startHour) . ' – ' . self::clock($endHour);

        return $endHour > 24 ? $range . ' ' . __('booking.next_day_short') : $range;
    }
}
