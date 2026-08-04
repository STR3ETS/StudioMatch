<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Boekingsonderhoud: verlopen blokkades, auto-annulering (BESLISSING 7) en afronden.
Schedule::command('bookings:maintain')->everyFiveMinutes();
