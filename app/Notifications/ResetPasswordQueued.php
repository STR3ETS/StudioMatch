<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * De wachtwoordherstelmail van Laravel, maar dan via de wachtrij. Zie VerifyEmailQueued
 * voor waarom: een storing bij de mailprovider hoort geen foutpagina op te leveren.
 */
class ResetPasswordQueued extends ResetPassword implements ShouldQueue
{
    use Queueable;
}
