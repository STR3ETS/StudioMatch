<?php

namespace App\Enums;

enum OwnerType: string
{
    // Type verhuurder (scope §2.2); bepaalt o.a. welke Stripe-onboarding straks geldt.
    case Particulier = 'particulier';
    case Ondernemer = 'ondernemer';
}
