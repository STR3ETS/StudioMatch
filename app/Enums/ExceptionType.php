<?php

namespace App\Enums;

enum ExceptionType: string
{
    // Uitzonderingen op het weekschema en blokkades (scope §2.4).
    case Open = 'open';     // extra open op een datum
    case Closed = 'closed'; // hele dag dicht
    case Block = 'block';   // blokkade: eigen sessie of onderhoud
}
