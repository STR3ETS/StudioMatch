<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('bookings:maintain')->everyFiveMinutes();

/*
 * De mail staat in de wachtrij, dus die moet wel verwerkt worden. Dit leunt op de
 * scheduler die er toch al is, zodat er geen aparte worker ingericht hoeft te worden.
 * De opdracht stopt zodra de wachtrij leeg is en sowieso na 55 seconden, zodat hij
 * nooit over de volgende minuut heen loopt.
 */
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=55')
    ->everyMinute()
    ->withoutOverlapping();
