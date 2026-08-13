<?php

namespace App\Enums;

enum ExceptionType: string
{

    case Open = 'open';
    case Closed = 'closed';
    case Block = 'block';
}
