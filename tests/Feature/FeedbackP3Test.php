<?php

namespace Tests\Feature;

use App\Http\Controllers\PublicStudioController;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Notifications\ContactMessage;
use App\Notifications\DamageResponded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FeedbackP3Test extends TestCase
{
    use RefreshDatabase;

    private function fakeAddressLookup(bool $found = true): void
    {
        Http::fake([
            'api.pdok.nl/*' => Http::response([
                'response' => ['docs' => $found ? [[
                    'postcode' => '1016GV',
                    'centroide_ll' => 'POINT(4.8836 52.3752)',
                ]] : []],
            ]),
        ]);
    }

    private function liveRoom(User $host): Room
    {
        $studio = $host->studios()->create([
            'name' => 'Redlight Recordings',
            'street' => 'Prinsengracht 263',
            'postal_code' => '1016 GV',
            'city' => 'Amsterdam',
            'lat' => 52.3752,
            'lng' => 4.8836,
        ]);

        return $studio->rooms()->create([
            'title' => 'Live room A',
            'description' => 'Fijne ruimte.',
            'type' => 'opname',
            'hourly_rate_cents' => 5000,
            'min_hours' => 2,
            'capacity' => 6,
            'status' => 'live',
        ]);
    }

    /* Account: e-mailverificatie en adres verplicht */

    public function test_unverified_user_cannot_reach_the_dashboard(): void
    {
        $user = User::factory()->unverified()->create(['role' => 'artiest']);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get('/dashboard/account')->assertRedirect(route('verification.notice'));
    }

    public function test_verification_notice_page_renders_for_an_unverified_user(): void
    {
        $user = User::factory()->unverified()->create(['role' => 'artiest']);

        $this->actingAs($user)->get(route('verification.notice'))
            ->assertOk()
            ->assertSee(__('auth.verify.title'))
            ->assertSee(__('auth.verify.blocked'));
    }

    public function test_artist_without_address_is_pushed_to_the_account_page(): void
    {
        $artist = User::factory()->withoutAddress()->create(['role' => 'artiest']);
        $host = User::factory()->create(['role' => 'verhuurder']);
        $room = $this->liveRoom($host);

        $this->actingAs($artist)->get('/dashboard')->assertRedirect(route('account.edit'));
        $this->actingAs($artist)->get('/dashboard/artiest')->assertRedirect(route('account.edit'));
        $this->actingAs($artist)->get('/studios/' . $room->slug . '/boeken')->assertRedirect(route('account.edit'));

        $this->actingAs($artist)->get(route('account.edit'))
            ->assertOk()
            ->assertSee(__('account.profile.address_required_title'));
    }

    public function test_host_without_address_keeps_access_to_the_dashboard(): void
    {
        $host = User::factory()->withoutAddress()->create(['role' => 'verhuurder']);

        $this->actingAs($host)->get('/dashboard')->assertRedirect(route('dashboard.host'));
    }

    public function test_artist_address_is_checked_against_the_address_register(): void
    {
        $this->fakeAddressLookup(found: false);
        $artist = User::factory()->withoutAddress()->create(['role' => 'artiest']);

        $this->actingAs($artist)->put('/dashboard/account/profiel', [
            'name' => $artist->name,
            'email' => $artist->email,
            'street' => 'Verzonnenstraat 999',
            'postal_code' => '1234 AB',
            'city' => 'Nergenshuizen',
        ])->assertSessionHasErrors('street');

        $this->assertNull($artist->fresh()->street);
    }

    /* Account: naam en begroeting */

    public function test_registration_requires_a_separate_first_and_last_name(): void
    {
        $base = [
            'role' => 'artiest',
            'email' => 'test@example.com',
            'password' => 'Wachtwoord123', 'password_confirmation' => 'Wachtwoord123',
            'terms' => '1',
        ];

        $this->post('/registreren', $base + ['first_name' => 'Piet'])
            ->assertSessionHasErrors('last_name');

        $this->post('/registreren', $base + ['first_name' => 'P', 'last_name' => 'Klaassen'])
            ->assertSessionHasErrors('first_name');

        $this->post('/registreren', $base + ['first_name' => 'Piet', 'last_name' => 'K2'])
            ->assertSessionHasErrors('last_name');

        $this->assertSame(0, User::count());
    }

    public function test_first_and_last_name_are_stored_as_one_full_name(): void
    {
        $this->post('/registreren', [
            'role' => 'artiest',
            'first_name' => 'Jan',
            'last_name' => "van 't Hof",
            'email' => 'jan@example.com',
            'password' => 'Wachtwoord123', 'password_confirmation' => 'Wachtwoord123',
            'terms' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame("Jan van 't Hof", User::first()->name);
    }

    public function test_registration_marks_the_session_as_brand_new(): void
    {
        $this->post('/registreren', [
            'role' => 'verhuurder',
            'first_name' => 'Nieuwe',
            'last_name' => 'Verhuurder',
            'email' => 'nieuw@example.com',
            'password' => 'Wachtwoord123', 'password_confirmation' => 'Wachtwoord123',
            'terms' => '1',
        ])->assertSessionHas('sm.just_registered', true);
    }

    public function test_a_new_account_is_not_welcomed_back(): void
    {
        $host = User::factory()->create(['role' => 'verhuurder', 'name' => 'Nieuwe Verhuurder']);

        $this->actingAs($host)
            ->withSession(['sm.just_registered' => true])
            ->get(route('dashboard.host'))
            ->assertOk()
            ->assertSee(__('dashboard.greeting_new', ['name' => 'Nieuwe']))
            ->assertDontSee(__('dashboard.greeting', ['name' => 'Nieuwe']));
    }

    public function test_a_returning_user_is_welcomed_back(): void
    {
        $host = User::factory()->create(['role' => 'verhuurder', 'name' => 'Terug Komer']);

        $this->actingAs($host)->get(route('dashboard.host'))
            ->assertOk()
            ->assertSee(__('dashboard.greeting', ['name' => 'Terug']));
    }

    /* Kaart: locatie niet te herleiden */

    public function test_public_map_data_never_exposes_the_exact_studio_coordinates(): void
    {
        $host = User::factory()->create(['role' => 'verhuurder']);
        $room = $this->liveRoom($host);

        $data = app(PublicStudioController::class)->mapData(collect([$room->load('studio', 'photos')]));

        $this->assertNotSame(52.3752, $data[0]['lat']);
        $this->assertNotSame(4.8836, $data[0]['lng']);

        // Same studio always lands on the same spot, within a few hundred metres.
        $again = app(PublicStudioController::class)->mapData(collect([$room->fresh()->load('studio', 'photos')]));
        $this->assertSame($data[0]['lat'], $again[0]['lat']);
        $this->assertLessThan(0.005, abs($data[0]['lat'] - 52.3752));
        $this->assertLessThan(0.005, abs($data[0]['lng'] - 4.8836));
    }

    /* Tickets */

    public function test_admin_can_reply_to_a_problem_report_and_the_host_sees_it(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $host = User::factory()->create(['role' => 'verhuurder']);
        $artist = User::factory()->create(['role' => 'artiest']);
        $room = $this->liveRoom($host);

        $booking = $room->bookings()->create([
            'user_id' => $artist->id,
            'date' => today()->subDays(2),
            'start_hour' => 10,
            'end_hour' => 13,
            'hourly_rate_cents' => 5000,
            'rent_cents' => 15000,
            'service_fee_cents' => 1350,
            'vat_cents' => 284,
            'total_cents' => 16634,
            'status' => 'completed',
            'terms_accepted_at' => now()->subDays(3),
            'damage_reported_at' => now()->subDay(),
            'damage_reason' => 'De microfoonstandaard is gebroken tijdens de sessie.',
        ]);

        $this->actingAs($admin)->patch(route('admin.tickets.respond', $booking), [
            'damage_response' => 'We hebben contact opgenomen met de artiest en koppelen morgen terug.',
        ])->assertRedirect(route('admin.tickets.index'));

        $booking->refresh();
        $this->assertNotNull($booking->damage_resolved_at);
        Notification::assertSentTo($host, DamageResponded::class);

        $this->actingAs($host)->get(route('host.bookings.index'))
            ->assertOk()
            ->assertSee('We hebben contact opgenomen met de artiest');
    }

    public function test_open_problem_reports_show_up_on_the_admin_home_screen(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $host = User::factory()->create(['role' => 'verhuurder']);
        $artist = User::factory()->create(['role' => 'artiest']);
        $room = $this->liveRoom($host);

        $room->bookings()->create([
            'user_id' => $artist->id,
            'date' => today()->subDays(2),
            'start_hour' => 10,
            'end_hour' => 13,
            'hourly_rate_cents' => 5000,
            'rent_cents' => 15000,
            'service_fee_cents' => 1350,
            'vat_cents' => 284,
            'total_cents' => 16634,
            'status' => 'completed',
            'terms_accepted_at' => now()->subDays(3),
            'damage_reported_at' => now(),
            'damage_reason' => 'Er is iets kapot.',
        ]);

        $this->actingAs($admin)->get(route('dashboard.admin'))
            ->assertOk()
            ->assertSee(trans_choice('admin.overview.tickets_damage', 1, ['count' => 1]));
    }

    public function test_a_replied_report_no_longer_counts_as_open(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $host = User::factory()->create(['role' => 'verhuurder']);
        $artist = User::factory()->create(['role' => 'artiest']);
        $room = $this->liveRoom($host);

        $room->bookings()->create([
            'user_id' => $artist->id,
            'date' => today()->subDays(2),
            'start_hour' => 10,
            'end_hour' => 13,
            'hourly_rate_cents' => 5000,
            'rent_cents' => 15000,
            'service_fee_cents' => 1350,
            'vat_cents' => 284,
            'total_cents' => 16634,
            'status' => 'completed',
            'terms_accepted_at' => now()->subDays(3),
            'damage_reported_at' => now()->subDay(),
            'damage_reason' => 'Er is iets kapot.',
            'damage_response' => 'Afgehandeld met beide partijen.',
            'damage_resolved_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('dashboard.admin'))
            ->assertOk()
            ->assertDontSee(trans_choice('admin.overview.tickets_damage', 1, ['count' => 1]));
    }

    public function test_the_damage_form_no_longer_promises_handling_outside_the_platform(): void
    {
        $host = User::factory()->create(['role' => 'verhuurder']);
        $artist = User::factory()->create(['role' => 'artiest']);
        $room = $this->liveRoom($host);

        $room->bookings()->create([
            'user_id' => $artist->id,
            'date' => today()->subDay(),
            'start_hour' => 10,
            'end_hour' => 13,
            'hourly_rate_cents' => 5000,
            'rent_cents' => 15000,
            'service_fee_cents' => 1350,
            'vat_cents' => 284,
            'total_cents' => 16634,
            'status' => 'completed',
            'terms_accepted_at' => now()->subDays(3),
        ]);

        $this->actingAs($host)->get(route('host.bookings.index'))
            ->assertOk()
            ->assertSee(__('host.damage.placeholder'), escape: false)
            ->assertDontSee('Beschrijf de schade')
            ->assertDontSee('buiten het platform');
    }

    /* Contactformulier */

    public function test_contact_messages_go_to_the_info_mailbox_by_default(): void
    {
        Notification::fake();

        $this->post('/contact', [
            'name' => 'Sam de Wit',
            'email' => 'sam@voorbeeld.nl',
            'subject' => 'general',
            'message' => 'Ik heb een vraag over het boeken van een studio.',
        ])->assertRedirect(route('contact'));

        Notification::assertSentOnDemand(
            ContactMessage::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'info@studiomatch.nl'
        );
    }

    /* Inlogproces visueel duidelijker */

    public function test_signup_pages_allow_showing_the_password(): void
    {
        $this->get('/registreren')
            ->assertOk()
            ->assertSee(__('auth.fields.first_name'))
            ->assertSee(__('auth.fields.last_name'))
            ->assertSee('data-password-toggle', escape: false);

        $this->get('/inloggen')
            ->assertOk()
            ->assertSee('data-password-toggle', escape: false);
    }

    /* Zoekpagina */

    public function test_search_page_keeps_the_show_results_button_in_view(): void
    {
        $this->get('/studios')
            ->assertOk()
            ->assertSee('data-sticky-count', escape: false)
            ->assertSee(__('studios.filters.apply'));
    }

    /* Foto's slepen */

    public function test_room_form_offers_drag_and_drop_instead_of_arrows(): void
    {
        $host = User::factory()->create(['role' => 'verhuurder']);
        $room = $this->liveRoom($host);
        $room->photos()->create(['path' => 'rooms/1/a.jpg', 'sort_order' => 0]);
        $room->photos()->create(['path' => 'rooms/1/b.jpg', 'sort_order' => 1]);

        $this->actingAs($host)->get(route('host.rooms.edit', $room))
            ->assertOk()
            ->assertSee('data-sortable-handle', escape: false)
            ->assertSee('data-reorder-url', escape: false)
            ->assertDontSee('move-photo-up-', escape: false);
    }

    /* Datumvelden in de huisstijl */

    public function test_availability_page_uses_the_house_style_datepicker(): void
    {
        $host = User::factory()->create(['role' => 'verhuurder']);
        $room = $this->liveRoom($host);

        $this->actingAs($host)->get(route('host.availability.edit', $room))
            ->assertOk()
            ->assertSee('data-datepicker', escape: false)
            ->assertDontSee('type="date"', escape: false);
    }
}
