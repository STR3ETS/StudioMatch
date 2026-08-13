<?php

namespace App\Enums;

enum UserRole: string
{
    case Artiest = 'artiest';
    case Verhuurder = 'verhuurder';
    case Admin = 'admin';

    public static function registerable(): array
    {
        return [self::Artiest->value, self::Verhuurder->value];
    }

    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Verhuurder => 'dashboard.host',
            self::Admin => 'dashboard.admin',
            default => 'dashboard.artist',
        };
    }
}
