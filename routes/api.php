<?php

use App\Http\Controllers\Api\App\AdminController;
use App\Http\Controllers\Api\App\ArtistBookingController;
use App\Http\Controllers\Api\App\DeviceController;
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
        Route::post('apparaten', [DeviceController::class, 'store'])->middleware('throttle:30,1')->name('devices');

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

        Route::middleware('app.role:admin')->prefix('beheer')->name('admin.')->group(function () {
            Route::get('overzicht', [AdminController::class, 'overview'])->name('overview');
            Route::get('tickets', [AdminController::class, 'tickets'])->name('tickets');
            Route::post('tickets/{booking}/afhandelen', [AdminController::class, 'resolveTicket'])->name('tickets.resolve');
            Route::post('tickets/{booking}/reageren', [AdminController::class, 'respondToTicket'])->name('tickets.respond');
            Route::get('wachtrij', [AdminController::class, 'queue'])->name('queue');
            Route::get('wachtrij/{room}', [AdminController::class, 'room'])->name('queue.show');
            Route::post('wachtrij/{room}/goedkeuren', [AdminController::class, 'approveRoom'])->name('queue.approve');
            Route::post('wachtrij/{room}/afwijzen', [AdminController::class, 'rejectRoom'])->name('queue.reject');
            Route::get('boekingen', [AdminController::class, 'bookings'])->name('bookings');
            Route::post('boekingen/{booking}/status', [AdminController::class, 'changeBookingStatus'])->name('bookings.status');
            Route::get('gebruikers', [AdminController::class, 'users'])->name('users');
            Route::get('omzet', [AdminController::class, 'revenue'])->name('revenue');
            Route::get('blog', [AdminController::class, 'posts'])->name('posts');
            Route::delete('blog/{post}', [AdminController::class, 'deletePost'])->name('posts.destroy');
        });
    });
});
