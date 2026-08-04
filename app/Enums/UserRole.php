<?php

namespace App\Enums;

enum UserRole: string
{
    case Artiest = 'artiest';
    case Verhuurder = 'verhuurder';
    case Admin = 'admin';

    /**
     * De rollen die bij registratie gekozen mogen worden.
     *
     * @return array<int, string>
     */
    public static function registerable(): array
    {
        return [self::Artiest->value, self::Verhuurder->value];
    }

    /**
     * De dashboardroute waar deze rol na inloggen landt.
     */
    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Verhuurder => 'dashboard.host',
            self::Admin => 'dashboard.admin',
            default => 'dashboard.artist',
        };
    }
}
