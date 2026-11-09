<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
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

        auth('web')->logout();
        $this->get($url)->assertRedirect(route('login'));

        // Alleen een pad op deze server, en alleen terug naar de app zelf.
        $this->withToken($token)->postJson(route('api.app.web-code'), ['naar' => 'https://evil.example', 'terug' => 'https://localhost'])->assertUnprocessable();
        $this->withToken($token)->postJson(route('api.app.web-code'), ['naar' => '/dashboard', 'terug' => 'https://evil.example'])->assertUnprocessable();
    }
}
