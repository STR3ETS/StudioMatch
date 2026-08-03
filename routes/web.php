<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/studios', function () {
    return view('studios');
})->name('studios');

Route::get('/studios/{studio}', function (string $studio) {
    return view('studio-show');
})->name('studios.show');

Route::get('/voor-studios', function () {
    return view('hosts');
})->name('hosts');

Route::get('/hoe-werkt-het', function () {
    return view('how');
})->name('how');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

Route::get('/faq', function () {
    return view('faq');
})->name('faq');

Route::get('/inloggen', function () {
    return view('auth.login');
})->name('login');

Route::get('/registreren', function () {
    return view('auth.register');
})->name('register');

Route::get('/wachtwoord-vergeten', function () {
    return view('auth.forgot-password');
})->name('password.request');

Route::get('/language/{locale}', function (string $locale) {
    if (array_key_exists($locale, config('localization.supported', []))) {
        session()->put('locale', $locale);
    }

    return redirect()->back();
})->name('language.switch');
