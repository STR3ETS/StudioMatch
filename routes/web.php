<?php

use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\OverviewController as AdminOverviewController;
use App\Http\Controllers\Admin\QueueController;
use App\Http\Controllers\Admin\RevenueController as AdminRevenueController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Artist\OverviewController as ArtistOverviewController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Host\BookingController as HostBookingController;
use App\Http\Controllers\Host\DamageController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Host\AgendaController;
use App\Http\Controllers\Host\AvailabilityController;
use App\Http\Controllers\Host\OverviewController;
use App\Http\Controllers\Host\RevenueController;
use App\Http\Controllers\IcalController;
use App\Http\Controllers\Host\ProfileController;
use App\Http\Controllers\Host\RoomController;
use App\Http\Controllers\Host\RoomPhotoController;
use App\Http\Controllers\Host\StripeController as HostStripeController;
use App\Http\Controllers\Host\StudioController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PublicStudioController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/studios', [PublicStudioController::class, 'index'])->name('studios');

Route::get('/studios/{room:slug}', [PublicStudioController::class, 'show'])->name('studios.show');

Route::get('/voor-studios', function () {
    return view('hosts');
})->name('hosts');

Route::get('/hoe-werkt-het', function () {
    return view('how');
})->name('how');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');
Route::post('/contact', \App\Http\Controllers\ContactController::class)
    ->middleware('throttle:5,1')->name('contact.store');

Route::get('/faq', function () {
    return view('faq');
})->name('faq');

Route::middleware('guest')->group(function () {
    Route::get('/inloggen', function () {
        return view('auth.login');
    })->name('login');
    Route::post('/inloggen', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');

    Route::get('/registreren', function () {
        return view('auth.register');
    })->name('register');
    Route::post('/registreren', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('register.store');

    Route::get('/wachtwoord-vergeten', function () {
        return view('auth.forgot-password');
    })->name('password.request');
    Route::post('/wachtwoord-vergeten', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');

    Route::get('/wachtwoord-herstellen/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('/wachtwoord-herstellen', [NewPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/email/verifieren/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('dashboard')->with('status', __('auth.verify.done'));
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/verifieren', function (\Illuminate\Http\Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', __('auth.verify.sent'));
    })->middleware('throttle:3,1')->name('verification.send');

    Route::get('/dashboard/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('/dashboard/account/profiel', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::put('/dashboard/account/wachtwoord', [AccountController::class, 'updatePassword'])->name('account.password.update');
    Route::delete('/dashboard/account', [AccountController::class, 'destroy'])->name('account.destroy');

    Route::get('/dashboard/artiest', ArtistOverviewController::class)
        ->middleware('role:artiest')->name('dashboard.artist');

    Route::get('/dashboard/facturen', [\App\Http\Controllers\InvoiceController::class, 'index'])
        ->middleware('role:artiest')->name('artist.invoices.index');
    Route::get('/facturen/{booking}/{type}', [\App\Http\Controllers\InvoiceController::class, 'download'])
        ->name('invoices.download');

    Route::middleware('role:artiest')->group(function () {
        Route::get('/studios/{room:slug}/boeken', [BookingController::class, 'create'])->name('studios.book');
        Route::post('/studios/{room:slug}/boeken', [BookingController::class, 'store'])
            ->middleware('throttle:10,1')->name('bookings.store');
        Route::get('/boekingen/{booking}/betalen', [BookingController::class, 'payment'])->name('bookings.payment');
        Route::post('/boekingen/{booking}/betalen', [BookingController::class, 'pay'])->name('bookings.pay');
        Route::get('/boekingen/{booking}/betaald', [BookingController::class, 'paid'])->name('bookings.paid');
        Route::post('/boekingen/{booking}/annuleren', [BookingController::class, 'cancel'])->name('bookings.cancel');
        Route::get('/boekingen/{booking}/verzetten', [BookingController::class, 'reschedule'])->name('bookings.reschedule');
        Route::post('/boekingen/{booking}/verzetten', [BookingController::class, 'rescheduleStore'])->name('bookings.reschedule.store');
        Route::post('/boekingen/{booking}/probleem', [BookingController::class, 'reportProblem'])->name('bookings.problem');
        Route::get('/boekingen/{booking}/agenda.ics', [BookingController::class, 'ics'])->name('bookings.ics');
    });

    Route::middleware('role:verhuurder')->prefix('dashboard/verhuurder')->group(function () {
        Route::get('/', OverviewController::class)->name('dashboard.host');

        Route::get('/bedrijfsgegevens', [ProfileController::class, 'edit'])->name('host.profile.edit');
        Route::put('/bedrijfsgegevens', [ProfileController::class, 'update'])->name('host.profile.update');

        Route::get('/studios', [StudioController::class, 'index'])->name('host.studios.index');
        Route::get('/studios/nieuw', [StudioController::class, 'create'])->name('host.studios.create');
        Route::post('/studios', [StudioController::class, 'store'])->name('host.studios.store');
        Route::get('/studios/{studio}', [StudioController::class, 'show'])->name('host.studios.show');
        Route::get('/studios/{studio}/bewerken', [StudioController::class, 'edit'])->name('host.studios.edit');
        Route::put('/studios/{studio}', [StudioController::class, 'update'])->name('host.studios.update');
        Route::delete('/studios/{studio}', [StudioController::class, 'destroy'])->name('host.studios.destroy');

        Route::get('/studios/{studio}/ruimtes/nieuw', [RoomController::class, 'create'])->name('host.studios.rooms.create');
        Route::post('/studios/{studio}/ruimtes', [RoomController::class, 'store'])->name('host.studios.rooms.store');

        Route::get('/ruimtes/{room}/bewerken', [RoomController::class, 'edit'])->name('host.rooms.edit');
        Route::put('/ruimtes/{room}', [RoomController::class, 'update'])->name('host.rooms.update');
        Route::delete('/ruimtes/{room}', [RoomController::class, 'destroy'])->name('host.rooms.destroy');
        Route::delete('/ruimtes/{room}/fotos/{photo}', [RoomPhotoController::class, 'destroy'])->name('host.rooms.photos.destroy');

        Route::get('/boekingen', [HostBookingController::class, 'index'])->name('host.bookings.index');
        Route::patch('/boekingen/{booking}/accepteren', [HostBookingController::class, 'accept'])->name('host.bookings.accept');
        Route::patch('/boekingen/{booking}/weigeren', [HostBookingController::class, 'decline'])->name('host.bookings.decline');

        Route::get('/agenda', AgendaController::class)->name('host.agenda');
        Route::get('/omzet', RevenueController::class)->name('host.revenue');

        Route::post('/schade/{booking}', [DamageController::class, 'store'])->name('host.damage.store');

        Route::get('/facturen', [\App\Http\Controllers\Host\InvoiceController::class, 'index'])->name('host.invoices.index');

        Route::get('/uitbetalingen', [HostStripeController::class, 'show'])->name('host.stripe.show');
        Route::post('/uitbetalingen/onboarding', [HostStripeController::class, 'onboard'])->name('host.stripe.onboard');
        Route::get('/uitbetalingen/terug', [HostStripeController::class, 'returned'])->name('host.stripe.return');

        Route::get('/beschikbaarheid', [AvailabilityController::class, 'index'])->name('host.availability.index');
        Route::get('/beschikbaarheid/{room}', [AvailabilityController::class, 'edit'])->name('host.availability.edit');
        Route::put('/beschikbaarheid/{room}/schema', [AvailabilityController::class, 'updateSchedule'])->name('host.availability.schedule');
        Route::put('/beschikbaarheid/{room}/vakantie', [AvailabilityController::class, 'updateVacation'])->name('host.availability.vacation');
        Route::post('/beschikbaarheid/{room}/uitzonderingen', [AvailabilityController::class, 'storeException'])->name('host.availability.exceptions.store');
        Route::delete('/beschikbaarheid/{room}/uitzonderingen/{exception}', [AvailabilityController::class, 'destroyException'])->name('host.availability.exceptions.destroy');
    });

    Route::middleware('role:admin')->prefix('dashboard/admin')->group(function () {
        Route::get('/', AdminOverviewController::class)->name('dashboard.admin');
        Route::get('/wachtrij', [QueueController::class, 'index'])->name('admin.queue.index');
        Route::get('/wachtrij/{room}', [QueueController::class, 'show'])->name('admin.queue.show');
        Route::patch('/wachtrij/{room}/goedkeuren', [QueueController::class, 'approve'])->name('admin.queue.approve');
        Route::patch('/wachtrij/{room}/afwijzen', [QueueController::class, 'reject'])->name('admin.queue.reject');

        Route::get('/tickets', [TicketController::class, 'index'])->name('admin.tickets.index');
        Route::patch('/tickets/{booking}/afhandelen', [TicketController::class, 'resolve'])->name('admin.tickets.resolve');

        Route::get('/boekingen', [AdminBookingController::class, 'index'])->name('admin.bookings.index');
        Route::get('/boekingen/export', [AdminBookingController::class, 'export'])->name('admin.bookings.export');
        Route::patch('/boekingen/{booking}/status', [AdminBookingController::class, 'updateStatus'])->name('admin.bookings.status');

        Route::get('/gebruikers', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::get('/gebruikers/export', [AdminUserController::class, 'export'])->name('admin.users.export');

        Route::get('/omzet', AdminRevenueController::class)->name('admin.revenue');
    });

    Route::post('/uitloggen', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

Route::get('/ical/ruimte/{room}', IcalController::class)->middleware('signed')->name('ical.room');

Route::get('/sitemap.xml', \App\Http\Controllers\SitemapController::class)->name('sitemap');

foreach (['voorwaarden' => 'terms', 'privacy' => 'privacy', 'disclaimer' => 'disclaimer', 'cookiebeleid' => 'cookies'] as $uri => $key) {
    Route::get('/' . $uri, fn () => view('legal', ['title' => __('legal.' . $key)]))->name('legal.' . $key);
}

Route::get('/language/{locale}', function (string $locale) {
    if (array_key_exists($locale, config('localization.supported', []))) {
        session()->put('locale', $locale);
        auth()->user()?->update(['locale' => $locale]);
    }

    return redirect()->back();
})->name('language.switch');
