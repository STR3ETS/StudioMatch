<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * De verificatiemail van Laravel, maar dan via de wachtrij.
 *
 * Standaard verstuurt Laravel deze midden in het registratieverzoek. Weigert de
 * mailprovider op dat moment, dan krijgt de bezoeker een 500 terwijl zijn account al is
 * aangemaakt. Vanuit de wachtrij kan dat niet meer gebeuren.
 */
class VerifyEmailQueued extends VerifyEmail implements ShouldQueue
{
    use Queueable;
}
