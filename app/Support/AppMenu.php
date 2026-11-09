<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Onderbalk en menu van de iOS/Android-app per rol, op één plek. De app (mobile/app) krijgt dit via
 * api/app/ik. Een tab is een scherm in de app zelf; een link opent een serverpagina in dezelfde app
 * (ingelogd via een eenmalige webcode). Een nieuwe serverpagina in het menu vraagt dus geen nieuwe
 * app-build, een nieuw scherm in de app wel.
 */
class AppMenu
{
    /** Serverpaden die in de app een eigen scherm hebben: pad => scherm. */
    public const SCREENS = [
        '/dashboard/artiest' => 'boekingen',
        '/studios' => 'studios',
        '/dashboard/verhuurder/boekingen' => 'inbox',
        '/dashboard/verhuurder/agenda' => 'agenda',
    ];

    /**
     * @return array{tabs: list<array<string, mixed>>, links: list<array<string, string>>}
     */
    public static function for(User $user): array
    {
        return match ($user->role) {
            UserRole::Artiest => [
                'tabs' => [
                    self::tab('boekingen', 'app.menu.bookings', 'fa-calendar-check'),
                    self::tab('studios', 'app.menu.search', 'fa-magnifying-glass'),
                    self::tab('account', 'app.menu.account', 'fa-user'),
                ],
                'links' => [
                    self::link('dashboard.artist_nav.invoices', 'fa-file-invoice', 'artist.invoices.index'),
                    self::link('app.menu.account_details', 'fa-user-pen', 'account.edit'),
                    self::link('app.menu.how', 'fa-circle-question', 'how'),
                    self::link('app.menu.contact', 'fa-envelope', 'contact'),
                ],
            ],
            UserRole::Verhuurder => [
                'tabs' => [
                    self::tab('inbox', 'app.menu.inbox', 'fa-inbox', 'requests'),
                    self::tab('agenda', 'app.menu.agenda', 'fa-calendar-days'),
                    self::tab('account', 'app.menu.account', 'fa-user'),
                ],
                'links' => [
                    self::link('host.nav.studios', 'fa-building', 'host.studios.index'),
                    self::link('host.nav.availability', 'fa-clock', 'host.availability.index'),
                    self::link('host.nav.revenue', 'fa-chart-line', 'host.revenue'),
                    self::link('host.nav.invoices', 'fa-file-invoice', 'host.invoices.index'),
                    self::link('host.nav.stripe', 'fa-building-columns', 'host.stripe.show'),
                    self::link('host.nav.profile', 'fa-briefcase', 'host.profile.edit'),
                    self::link('app.menu.account_details', 'fa-user-pen', 'account.edit'),
                    self::link('app.menu.contact', 'fa-envelope', 'contact'),
                ],
            ],
            UserRole::Admin => [
                'tabs' => [
                    self::tab('account', 'app.menu.account', 'fa-user'),
                ],
                'links' => [
                    self::link('admin.nav.overview', 'fa-gauge', 'dashboard.admin'),
                    self::link('admin.nav.queue', 'fa-list-check', 'admin.queue.index'),
                    self::link('admin.nav.tickets', 'fa-triangle-exclamation', 'admin.tickets.index'),
                    self::link('admin.nav.bookings', 'fa-calendar-check', 'admin.bookings.index'),
                    self::link('admin.nav.users', 'fa-users', 'admin.users.index'),
                    self::link('admin.nav.revenue', 'fa-chart-line', 'admin.revenue'),
                ],
            ],
        };
    }

    /** @return array<string, mixed> */
    private static function tab(string $screen, string $label, string $icon, ?string $count = null): array
    {
        return array_filter([
            'screen' => $screen,
            'label' => __($label),
            'icon' => $icon,
            'count' => $count,
        ], fn ($value) => $value !== null);
    }

    /** @return array<string, string> */
    private static function link(string $label, string $icon, string $route): array
    {
        return [
            'label' => __($label),
            'icon' => $icon,
            'path' => route($route, [], false),
        ];
    }
}
