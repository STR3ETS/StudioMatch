<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
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
        '/dashboard/admin' => 'overzicht',
        '/dashboard/admin/tickets' => 'tickets',
        '/dashboard/admin/wachtrij' => 'wachtrij',
        '/dashboard/admin/boekingen' => 'admin-boekingen',
        '/dashboard/admin/gebruikers' => 'gebruikers',
        '/dashboard/admin/omzet' => 'omzet',
        '/dashboard/admin/blog' => 'blog',
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
                    self::tab('overzicht', 'admin.nav.overview', 'fa-gauge'),
                    self::tab('tickets', 'admin.nav.tickets', 'fa-triangle-exclamation', 'tickets'),
                    self::tab('wachtrij', 'app.menu.queue', 'fa-list-check', 'queue'),
                    self::tab('admin-boekingen', 'admin.nav.bookings', 'fa-calendar-check'),
                    self::tab('account', 'app.menu.account', 'fa-user'),
                ],
                // Ook schermen in de app (SCREENS): de app opent ze daar, niet als websitepagina.
                'links' => [
                    self::link('admin.nav.users', 'fa-users', 'admin.users.index'),
                    self::link('admin.nav.revenue', 'fa-chart-line', 'admin.revenue'),
                    self::link('admin.nav.blog', 'fa-newspaper', 'admin.posts.index'),
                    self::link('app.menu.account_details', 'fa-user-pen', 'account.edit'),
                ],
            ],
        };
    }

    /**
     * Tellers voor de stippen op de tabs (de app via api/app/ik, de websitepagina's via x-app-tabbar).
     *
     * @return array<string, int>
     */
    public static function counts(User $user): array
    {
        return [
            'requests' => $user->isHost()
                ? Booking::whereIn('room_id', $user->rooms()->select('rooms.id'))
                    ->where('status', BookingStatus::PendingConfirmation)->count()
                : 0,
            // Open tickets: wat op de beheerder wacht.
            'tickets' => $user->isAdmin() ? AdminDesk::openTicketCount() : 0,
            // Ruimtes die op beoordeling wachten.
            'queue' => $user->isAdmin() ? AdminDesk::queueCount() : 0,
        ];
    }

    /**
     * Bij welke tab een websitepagina hoort, zodat de onderbalk op die pagina de juiste tab oplicht:
     * een studio bij Zoeken, betalen of verzetten bij Boekingen, de boekingen van de verhuurder bij
     * Aanvragen, de rest (facturen, gegevens, ...) bij Account, waar die links ook staan.
     */
    public static function tabFor(User $user, ?string $routeName): string
    {
        $routeName ??= '';

        return match ($user->role) {
            UserRole::Artiest => match (true) {
                str_starts_with($routeName, 'studios') => 'studios',
                str_starts_with($routeName, 'bookings.') || $routeName === 'dashboard.artist' => 'boekingen',
                default => 'account',
            },
            UserRole::Verhuurder => match (true) {
                str_starts_with($routeName, 'host.bookings.') => 'inbox',
                $routeName === 'host.agenda' => 'agenda',
                default => 'account',
            },
            UserRole::Admin => match (true) {
                $routeName === 'dashboard.admin' => 'overzicht',
                str_starts_with($routeName, 'admin.tickets.') => 'tickets',
                str_starts_with($routeName, 'admin.queue.') => 'wachtrij',
                str_starts_with($routeName, 'admin.bookings.') => 'admin-boekingen',
                default => 'account',
            },
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
