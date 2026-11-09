<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Post;
use App\Models\Room;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\DisputeResolved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * De app-API (api/app/*) voor de iOS/Android-app: inloggen met een token, rollen gescheiden, dezelfde
 * regels als de website bij accepteren en weigeren, en alleen de app zelf mag hem aanroepen.
 */
class AppApiTest extends TestCase
{
    use RefreshDatabase;

    private User $artist;

    private User $host;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->artist = User::factory()->create(['role' => 'artiest', 'email' => 'artiest@app.test', 'password' => 'Wachtwoord123']);
        $this->host = User::factory()->create(['role' => 'verhuurder']);
        $this->host->hostProfile()->create(['name' => 'Redlight Recordings', 'phone' => '0612345678']);
        $studio = $this->host->studios()->create(['name' => 'Redlight', 'street' => 'Teststraat 1', 'postal_code' => '1234 AB', 'city' => 'Arnhem']);
        $this->room = $studio->rooms()->create([
            'title' => 'Live room', 'description' => 'Een fijne ruimte.', 'type' => 'opname',
            'hourly_rate_cents' => 5000, 'min_hours' => 2, 'capacity' => 6, 'status' => 'live',
        ]);
    }

    private function booking(array $attributes = []): Booking
    {
        return $this->room->bookings()->create(array_merge([
            'user_id' => $this->artist->id,
            'date' => today()->addDays(7),
            'start_hour' => 10,
            'end_hour' => 13,
            'hourly_rate_cents' => 5000,
            'rent_cents' => 15000,
            'service_fee_cents' => 1350,
            'vat_cents' => 284,
            'total_cents' => 16634,
            'status' => 'confirmed',
            'terms_accepted_at' => now(),
            'confirmed_at' => now(),
        ], $attributes));
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    public function test_texts_are_public_and_follow_the_requested_language(): void
    {
        $this->getJson(route('api.app.texts', ['locale' => 'en']))
            ->assertOk()
            ->assertJsonPath('locale', 'en')
            ->assertJsonPath('ui.login.submit', 'Log in');

        $this->getJson(route('api.app.texts'), ['Accept-Language' => 'nl-NL,nl;q=0.9'])
            ->assertJsonPath('ui.login.submit', 'Inloggen');
    }

    public function test_logging_in_returns_a_token_profile_and_menu(): void
    {
        $response = $this->postJson(route('api.app.login'), ['email' => 'artiest@app.test', 'password' => 'Wachtwoord123', 'device' => 'Pixel'])
            ->assertOk()
            ->assertJsonPath('user.role', 'artiest');

        $this->assertNotEmpty($response->json('token'));
        $this->assertSame(['boekingen', 'studios', 'account'], array_column($response->json('menu.tabs'), 'screen'));
        $this->assertNotEmpty($response->json('ui.bookings.title'));
        $this->assertSame(1, $this->artist->tokens()->count());
    }

    public function test_a_wrong_password_gets_a_dutch_error(): void
    {
        $this->postJson(route('api.app.login'), ['email' => 'artiest@app.test', 'password' => 'fout'], ['Accept-Language' => 'nl'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'De combinatie van e-mailadres en wachtwoord klopt niet.');
    }

    public function test_data_needs_a_token_and_a_token_unused_for_sixty_days_stops_working(): void
    {
        $this->getJson(route('api.app.me'))->assertUnauthorized();

        $token = $this->tokenFor($this->artist);
        $this->withToken($token)->getJson(route('api.app.me'))->assertOk();

        $this->travel(61)->days();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson(route('api.app.me'))->assertUnauthorized();
    }

    public function test_logging_out_revokes_the_token(): void
    {
        $token = $this->tokenFor($this->artist);

        $this->withToken($token)->postJson(route('api.app.logout'))->assertNoContent();

        $this->assertSame(0, $this->artist->tokens()->count());
    }

    public function test_an_artist_sees_only_own_bookings_and_never_host_data(): void
    {
        $mine = $this->booking();
        $other = $this->booking(['user_id' => User::factory()->create(['role' => 'artiest'])->id]);
        $token = $this->tokenFor($this->artist);

        $upcoming = $this->withToken($token)->getJson(route('api.app.bookings'))->assertOk()->json('upcoming');
        $this->assertSame([$mine->id], array_column($upcoming, 'id'));

        $this->withToken($token)->getJson(route('api.app.bookings.show', $mine))
            ->assertOk()
            ->assertJsonPath('studio', 'Redlight')
            ->assertJsonPath('address', 'Teststraat 1, 1234 AB Arnhem');
        $this->withToken($token)->getJson(route('api.app.bookings.show', $other))->assertNotFound();

        $this->withToken($token)->getJson(route('api.app.inbox'))->assertForbidden();
        $this->withToken($token)->getJson(route('api.app.agenda'))->assertForbidden();
    }

    public function test_cancelling_comes_last_and_expired_and_disputed_bookings_explain_themselves(): void
    {
        $token = $this->tokenFor($this->artist);

        $actions = $this->withToken($token)->getJson(route('api.app.bookings.show', $this->booking()))->assertOk()->json('actions');
        $this->assertTrue(end($actions)['danger'] ?? false);
        $this->assertSame([], array_filter(array_slice($actions, 0, -1), fn (array $action) => $action['danger'] ?? false));

        $this->withToken($token)->getJson(route('api.app.bookings.show', $this->booking(['status' => 'expired', 'confirmed_at' => null])))
            ->assertOk()->assertJsonPath('note', __('app.notes.expired'));
        $this->withToken($token)->getJson(route('api.app.bookings.show', $this->booking(['status' => 'disputed'])))
            ->assertOk()->assertJsonPath('note', __('app.notes.disputed'));
    }

    public function test_agenda_sessions_say_where_they_open(): void
    {
        $this->booking(['date' => today()->addDay()]);
        $this->booking(['date' => today()->addDays(2), 'status' => 'pending_confirmation', 'confirmed_at' => null, 'requested_at' => now()]);

        $days = $this->withToken($this->tokenFor($this->host))->getJson(route('api.app.agenda'))->assertOk()->json('days');

        // Bevestigd: de boekingen op de website. Een aanvraag beantwoordt de app zelf (Aanvragen).
        $this->assertSame(route('host.bookings.index', [], false), $days[1]['entries'][0]['path']);
        $this->assertNull($days[2]['entries'][0]['path']);
    }

    public function test_the_admin_has_an_overview_and_tickets_in_the_app(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $dispute = $this->booking(['status' => 'disputed', 'disputed_at' => now(), 'dispute_reason' => 'De microfoon deed het niet.']);
        $this->booking(['damage_reported_at' => now(), 'damage_reason' => 'Kabel kapot.', 'start_hour' => 15, 'end_hour' => 18]);
        $token = $this->tokenFor($admin);

        // Onderbalk: Overzicht, Tickets (met het aantal open tickets), Wachtrij, Boekingen en Account.
        $profile = $this->withToken($token)->getJson(route('api.app.me'))->assertOk();
        $this->assertSame(['overzicht', 'tickets', 'wachtrij', 'admin-boekingen', 'account'], array_column($profile->json('menu.tabs'), 'screen'));
        $this->assertSame(2, $profile->json('counts.tickets'));

        $this->withToken($token)->getJson(route('api.app.admin.overview'))
            ->assertOk()
            ->assertJsonPath('open_disputes', 1)
            ->assertJsonPath('open_damages', 1)
            ->assertJsonPath('live_rooms', 1);

        $tickets = $this->withToken($token)->getJson(route('api.app.admin.tickets'))->assertOk();
        $this->assertSame('De microfoon deed het niet.', $tickets->json('disputes.0.reason'));
        $this->assertSame('/dashboard/admin/tickets#ticket-'.$dispute->id, $tickets->json('disputes.0.path'));
        $this->assertTrue($tickets->json('damages.0.open'));
    }

    public function test_the_admin_handles_tickets_in_the_app_with_the_website_rules(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $dispute = $this->booking(['status' => 'disputed', 'disputed_at' => now(), 'dispute_reason' => 'De microfoon deed het niet.']);
        $damage = $this->booking(['damage_reported_at' => now(), 'damage_reason' => 'Kabel kapot.', 'start_hour' => 15, 'end_hour' => 18]);
        $token = $this->tokenFor($admin);

        // Te korte toelichting: de foutmelding van de website.
        $this->withToken($token)->postJson(route('api.app.admin.tickets.resolve', $dispute), ['refund_percent' => 50, 'resolution_note' => 'kort'])
            ->assertUnprocessable()->assertJsonValidationErrors('resolution_note');

        $this->withToken($token)->postJson(route('api.app.admin.tickets.resolve', $dispute), ['refund_percent' => 50, 'resolution_note' => 'Beide partijen gesproken, half terug.'])
            ->assertOk()
            ->assertJsonPath('outcome', 'resolved')
            ->assertJsonCount(0, 'tickets.disputes');
        $this->assertSame('completed', $dispute->fresh()->status->value);
        Notification::assertSentTo($this->artist, DisputeResolved::class);

        // Nog een keer: al afgehandeld.
        $this->withToken($token)->postJson(route('api.app.admin.tickets.resolve', $dispute), ['refund_percent' => 50, 'resolution_note' => 'Beide partijen gesproken, half terug.'])
            ->assertStatus(409)->assertJsonPath('outcome', 'already_resolved');

        $this->withToken($token)->postJson(route('api.app.admin.tickets.respond', $damage), ['damage_response' => 'We nemen contact op met de artiest.'])
            ->assertOk()->assertJsonPath('outcome', 'damage_responded');
        $this->assertNotNull($damage->fresh()->damage_resolved_at);
    }

    public function test_the_admin_reviews_rooms_in_the_app(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $this->tokenFor($admin);
        $room = fn (string $title) => $this->room->studio->rooms()->create([
            'title' => $title, 'description' => 'Nieuwe ruimte.', 'type' => 'opname',
            'hourly_rate_cents' => 4000, 'min_hours' => 2, 'capacity' => 4, 'status' => 'in_review',
        ]);
        $first = $room('Mix Suite');
        $second = $room('Booth');

        $this->assertSame(2, $this->withToken($token)->getJson(route('api.app.me'))->json('counts.queue'));
        $this->assertCount(2, $this->withToken($token)->getJson(route('api.app.admin.queue'))->assertOk()->json('rooms'));
        $this->withToken($token)->getJson(route('api.app.admin.queue.show', $first))
            ->assertOk()->assertJsonPath('title', 'Mix Suite')->assertJsonPath('in_review', true)->assertJsonPath('hourly_cents', 4000);

        $this->withToken($token)->postJson(route('api.app.admin.queue.approve', $first))->assertOk()->assertJsonPath('outcome', 'approved');
        $this->assertSame('live', $first->fresh()->status->value);
        // Al beoordeeld: niet nog een keer.
        $this->withToken($token)->postJson(route('api.app.admin.queue.approve', $first))->assertStatus(409);

        $this->withToken($token)->postJson(route('api.app.admin.queue.reject', $second), [])->assertUnprocessable();
        $this->withToken($token)->postJson(route('api.app.admin.queue.reject', $second), ['rejection_reason' => 'Foto\'s te donker.'])->assertOk();
        $this->assertSame('afgekeurd', $second->fresh()->status->value);
        $this->assertSame(0, $this->withToken($token)->getJson(route('api.app.me'))->json('counts.queue'));
    }

    public function test_the_admin_sees_bookings_users_revenue_and_blog_in_the_app(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $this->tokenFor($admin);
        $booking = $this->booking();
        $this->booking(['status' => 'pending_confirmation', 'confirmed_at' => null, 'requested_at' => now(), 'start_hour' => 15, 'end_hour' => 18]);

        $this->assertCount(2, $this->withToken($token)->getJson(route('api.app.admin.bookings'))->assertOk()->json('bookings'));
        $this->assertCount(1, $this->withToken($token)->getJson(route('api.app.admin.bookings', ['status' => 'confirmed']))->json('bookings'));

        // Status wijzigen: annuleren betaalt terug en mailt beide partijen, net als op de website.
        $this->withToken($token)->postJson(route('api.app.admin.bookings.status', $booking), ['status' => 'cancelled'])
            ->assertOk()->assertJsonPath('booking.status', 'cancelled');
        $this->assertSame('admin', $booking->fresh()->cancelled_by);
        Notification::assertSentTo($this->artist, BookingCancelled::class);

        $users = $this->withToken($token)->getJson(route('api.app.admin.users', ['rol' => 'verhuurder']))->assertOk()->json('users');
        $this->assertSame([$this->host->id], array_column($users, 'id'));

        $this->withToken($token)->getJson(route('api.app.admin.revenue'))->assertOk()->assertJsonPath('total_count', 0);

        $post = Post::create(['title' => 'Je eerste EP', 'slug' => 'je-eerste-ep', 'status' => 'concept']);
        $this->withToken($token)->getJson(route('api.app.admin.posts'))->assertOk()->assertJsonPath('posts.0.edit_path', route('admin.posts.edit', $post, false));
        $this->withToken($token)->deleteJson(route('api.app.admin.posts.destroy', $post))->assertOk();
        $this->assertNull(Post::find($post->id));
    }

    public function test_only_the_admin_sees_the_tickets(): void
    {
        $this->withToken($this->tokenFor($this->artist))->postJson(route('api.app.admin.bookings.status', $this->booking()), ['status' => 'cancelled'])->assertForbidden();
        $this->withToken($this->tokenFor($this->host))->getJson(route('api.app.admin.queue'))->assertForbidden();
        $this->withToken($this->tokenFor($this->artist))->getJson(route('api.app.admin.tickets'))->assertForbidden();
        $this->withToken($this->tokenFor($this->host))->getJson(route('api.app.admin.overview'))->assertForbidden();
    }

    public function test_a_host_never_sees_the_artist_screens(): void
    {
        $this->withToken($this->tokenFor($this->host))->getJson(route('api.app.bookings'))->assertForbidden();
    }

    public function test_a_host_accepts_and_declines_requests_with_the_website_rules(): void
    {
        $first = $this->booking(['status' => 'pending_confirmation', 'confirmed_at' => null, 'requested_at' => now()]);
        $second = $this->booking(['status' => 'pending_confirmation', 'confirmed_at' => null, 'requested_at' => now(), 'start_hour' => 15, 'end_hour' => 18]);
        $token = $this->tokenFor($this->host);

        $inbox = $this->withToken($token)->getJson(route('api.app.inbox'))->assertOk();
        $this->assertCount(2, $inbox->json('requests'));
        $this->assertSame(15000, $inbox->json('requests.0.rent_cents'));

        $this->withToken($token)->postJson(route('api.app.inbox.accept', $first))
            ->assertOk()
            ->assertJsonPath('outcome', 'accepted')
            ->assertJsonCount(1, 'inbox.requests')
            ->assertJsonCount(1, 'inbox.upcoming');
        $this->assertSame('confirmed', $first->fresh()->status->value);

        $this->withToken($token)->postJson(route('api.app.inbox.decline', $second))->assertOk()->assertJsonPath('outcome', 'declined');
        $this->assertSame('declined', $second->fresh()->status->value);

        // Al afgehandeld: geen tweede keer, wel de melding van de website.
        $this->withToken($token)->postJson(route('api.app.inbox.accept', $second))->assertStatus(409)->assertJsonPath('outcome', 'already_handled');
    }

    public function test_another_host_cannot_answer_a_request(): void
    {
        $booking = $this->booking(['status' => 'pending_confirmation', 'confirmed_at' => null, 'requested_at' => now()]);
        $otherHost = User::factory()->create(['role' => 'verhuurder']);

        $this->withToken($this->tokenFor($otherHost))->postJson(route('api.app.inbox.accept', $booking))->assertForbidden();
        $this->assertSame('pending_confirmation', $booking->fresh()->status->value);
    }

    public function test_photos_point_to_the_server_the_app_talks_to(): void
    {
        $this->room->photos()->create(['path' => 'rooms/1/foto.webp', 'sort_order' => 0]);

        $photo = $this->withServerVariables(['HTTP_HOST' => '192.168.1.20:8000'])
            ->withToken($this->tokenFor($this->artist))
            ->getJson('http://192.168.1.20:8000/api/app/studios')
            ->assertOk()
            ->json('studios.0.photo');

        $this->assertStringStartsWith('http://192.168.1.20:8000/storage/rooms/1/', $photo);
    }

    public function test_the_agenda_shows_seven_days_with_the_bookings(): void
    {
        $this->booking(['date' => today()->addDays(2)]);

        $days = $this->withToken($this->tokenFor($this->host))->getJson(route('api.app.agenda'))->assertOk()->json('days');

        $this->assertCount(7, $days);
        $this->assertTrue($days[0]['today']);
        $this->assertCount(1, $days[2]['entries']);
        $this->assertSame('booking', $days[2]['entries'][0]['kind']);
    }

    public function test_studio_search_only_lists_live_rooms(): void
    {
        $this->room->studio->rooms()->create([
            'title' => 'Concept room', 'description' => 'Nog niet klaar.', 'type' => 'opname',
            'hourly_rate_cents' => 4000, 'min_hours' => 1, 'capacity' => 2, 'status' => 'concept',
        ]);

        $studios = $this->withToken($this->tokenFor($this->artist))->getJson(route('api.app.studios', ['q' => 'Arnhem']))->assertOk()->json('studios');

        $this->assertSame([$this->room->id], array_column($studios, 'id'));
    }

    public function test_only_the_app_may_call_the_api_from_a_browser(): void
    {
        $preflight = fn (string $origin) => $this->call('OPTIONS', route('api.app.login'), [], [], [], [
            'HTTP_ORIGIN' => $origin,
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $this->assertSame('https://localhost', $preflight('https://localhost')->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame('capacitor://localhost', $preflight('capacitor://localhost')->headers->get('Access-Control-Allow-Origin'));
        $this->assertNull($preflight('https://evil.example')->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_a_web_code_signs_in_once_and_only_to_own_pages(): void
    {
        $token = $this->tokenFor($this->artist);

        $url = $this->withToken($token)->postJson(route('api.app.web-code'), ['naar' => '/dashboard/artiest', 'terug' => 'https://localhost'])
            ->assertOk()->json('url');

        $this->app['auth']->forgetGuards();
        $this->get($url)->assertRedirect('/dashboard/artiest');
        $this->assertAuthenticatedAs($this->artist, 'web');
        $this->assertSame('https://localhost', session('app_bundle'));
        // Vanuit de app: een balk terug naar de app (een iPhone heeft geen terugknop).
        $this->get('/dashboard/artiest')->assertSee('Terug naar de app')->assertSee('href="https://localhost/"', false);

        auth('web')->logout();
        session()->forget('app_bundle');
        $this->actingAs($this->artist)->get('/dashboard/artiest')->assertDontSee('Terug naar de app');
        auth('web')->logout();
        $this->get($url)->assertRedirect(route('login'));

        // Alleen een pad op deze server, en alleen terug naar de app zelf.
        $this->withToken($token)->postJson(route('api.app.web-code'), ['naar' => 'https://evil.example', 'terug' => 'https://localhost'])->assertUnprocessable();
        $this->withToken($token)->postJson(route('api.app.web-code'), ['naar' => '/dashboard', 'terug' => 'https://evil.example'])->assertUnprocessable();
    }
}
