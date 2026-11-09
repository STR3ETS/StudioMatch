<?php

use App\Http\Controllers\Api\App\ArtistBookingController;
use App\Http\Controllers\Api\App\HostAgendaController;
use App\Http\Controllers\Api\App\HostInboxController;
use App\Http\Controllers\Api\App\SessionController;
use App\Http\Controllers\Api\App\StudioSearchController;
use Illuminate\Support\Facades\Route;

// De iOS/Android-app (mobile/app): de schermen zitten in de app, deze routes leveren de gegevens.
// Taal: het account, anders de taal van de telefoon (SetAppLocale).
Route::prefix('app')->name('api.app.')->middleware('app.locale')->group(function () {
    Route::get('teksten', [SessionController::class, 'texts'])->middleware('throttle:60,1')->name('texts');
    Route::post('inloggen', [SessionController::class, 'store'])->middleware('throttle:10,1')->name('login');

    // 120 per minuut per gebruiker: ruim voor vooraf laden en verversen, te weinig om de server te bestoken.
    Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
        Route::get('ik', [SessionController::class, 'show'])->name('me');
        Route::post('uitloggen', [SessionController::class, 'destroy'])->name('logout');
        Route::patch('taal', [SessionController::class, 'locale'])->name('locale');
        Route::post('webcode', [SessionController::class, 'webCode'])->middleware('throttle:60,1')->name('web-code');

        Route::get('studios', StudioSearchController::class)->name('studios');

        Route::middleware('app.role:artiest')->group(function () {
            Route::get('boekingen', [ArtistBookingController::class, 'index'])->name('bookings');
            Route::get('boekingen/{booking}', [ArtistBookingController::class, 'show'])->name('bookings.show');
        });

        Route::middleware('app.role:verhuurder')->group(function () {
            Route::get('inbox', [HostInboxController::class, 'index'])->name('inbox');
            Route::post('inbox/{booking}/accepteren', [HostInboxController::class, 'accept'])->name('inbox.accept');
            Route::post('inbox/{booking}/weigeren', [HostInboxController::class, 'decline'])->name('inbox.decline');
            Route::get('agenda', HostAgendaController::class)->name('agenda');
        });
    });
});
