<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingReceived;
use App\Notifications\BookingRequested;
use App\Notifications\BookingRescheduled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Boeken in de iOS/Android-app: de studio, een moment kiezen, controleren, betalen (demo), annuleren en
 * verzetten via api/app/*, met dezelfde regels en prijs als de website. En de pagina's die in de app openen
 * (studio, foutpagina) leiden terug naar de app in plaats van naar de website.
 */
class AppBookingTest extends TestCase
{
    use RefreshDatabase;

    private const APP = 'Mozilla/5.0 (Linux; Android 16; SM-A536B; wv) AppleWebKit/537.36 StudioMatchApp';

    private User $artist;

    private User $host;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->artist = User::factory()->create(['role' => 'artiest', 'street' => 'Keizersgracht 12', 'postal_code' => '1015 CN', 'city' => 'Amsterdam']);
        $this->host = User::factory()->create(['role' => 'verhuurder']);
        $studio = $this->host->studios()->create(['name' => 'Redlight', 'street' => 'Prinsengracht 263', 'postal_code' => '1016 GV', 'city' => 'Amsterdam']);
        $this->room = $studio->rooms()->create([
            'title' => 'Live room', 'description' => 'Fijne ruimte.', 'type' => 'opname',
            'hourly_rate_cents' => 5000, 'min_hours' => 2, 'capacity' => 6, 'status' => 'live',
            'house_rules' => "Niet roken\nMax 6 personen",
        ]);
        $this->room->seedDefaultHours();
    }

    private function slot(): array
    {
        return ['date' => today()->next('monday')->toDateString(), 'start' => 10, 'hours' => 3];
    }

    private function token(?User $user = null): string
    {
        return ($user ?? $this->artist)->createToken('test')->plainTextToken;
    }

    private function reserve(): Booking
    {
        $this->withToken($this->token())
            ->postJson(route('api.app.studios.book', $this->room), [...$this->slot(), 'terms' => 1])
            ->assertCreated();

        return Booking::latest('id')->first();
    }

    public function test_the_studio_screen_has_what_is_needed_to_pick_a_moment(): void
    {
        $response = $this->withToken($this->token())->getJson(route('api.app.studios.show', $this->room))->assertOk()
            ->assertJsonPath('name', 'Redlight - Live room')
            ->assertJsonPath('rules', ['Niet roken', 'Max 6 personen'])
            ->assertJsonPath('booking.hourly_cents', 5000)
            ->assertJsonPath('booking.min_hours', 2);

        $this->assertContains(10, $response->json('booking.free_hours.'.$this->slot()['date']));
    }

    public function test_only_an_artist_books_and_only_a_visible_room(): void
    {
        $this->withToken($this->token($this->host))->getJson(route('api.app.studios.show', $this->room))->assertForbidden();

        $this->room->update(['status' => 'concept']);
        $this->app['auth']->forgetGuards();
        $this->withToken($this->token())->getJson(route('api.app.studios.show', $this->room))->assertNotFound();
    }

    public function test_checkout_gives_the_same_price_as_the_website_and_refuses_a_taken_moment(): void
    {
        $this->withToken($this->token())
            ->getJson(route('api.app.studios.checkout', $this->room).'?'.http_build_query($this->slot()))
            ->assertOk()
            ->assertJsonPath('total_cents', 15000 + 1350 + 284)
            ->assertJsonPath('needs_address', false);

        $this->reserve();

        $this->withToken($this->token())
            ->getJson(route('api.app.studios.checkout', $this->room).'?'.http_build_query($this->slot()))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('slot');
    }

    public function test_reserving_holds_the_slot_and_needs_the_terms(): void
    {
        $this->withToken($this->token())->postJson(route('api.app.studios.book', $this->room), $this->slot())
            ->assertUnprocessable()->assertJsonValidationErrors('terms');

        $booking = $this->reserve();

        $this->assertSame('pending_payment', $booking->status->value);
        $this->assertSame(16634, $booking->total_cents);
        $this->assertTrue($booking->expires_at->isFuture());
    }

    public function test_without_an_address_the_artist_gives_one_that_exists(): void
    {
        $this->artist->update(['street' => null, 'postal_code' => null, 'city' => null]);
        Http::fake(['api.pdok.nl/*' => Http::response(['response' => ['docs' => []]])]);

        $this->withToken($this->token())->getJson(route('api.app.studios.checkout', $this->room).'?'.http_build_query($this->slot()))
            ->assertOk()->assertJsonPath('needs_address', true);

        $this->withToken($this->token())->postJson(route('api.app.studios.book', $this->room), [...$this->slot(), 'terms' => 1])
            ->assertUnprocessable()->assertJsonValidationErrors(['street', 'postal_code', 'city']);
    }

    public function test_paying_in_the_demo_sends_the_request_to_the_studio(): void
    {
        $booking = $this->reserve();

        $this->withToken($this->token())->getJson(route('api.app.bookings.show', $booking))
            ->assertOk()->assertJsonPath('payment.stripe', false)->assertJsonPath('actions.0.screen', 'betalen/'.$booking->id);

        $this->withToken($this->token())->postJson(route('api.app.bookings.pay', $booking))
            ->assertOk()->assertJsonPath('booking.status', 'pending_confirmation')->assertJsonPath('message', __('booking.paid'));

        Notification::assertSentTo($this->host, BookingRequested::class);
        Notification::assertSentTo($this->artist, BookingReceived::class);

        // Twee keer betalen kan niet.
        $this->withToken($this->token())->postJson(route('api.app.bookings.pay', $booking))->assertConflict();
    }

    public function test_paying_too_late_releases_the_slot(): void
    {
        $booking = $this->reserve();
        $booking->update(['expires_at' => now()->subMinute()]);

        $this->withToken($this->token())->postJson(route('api.app.bookings.pay', $booking))
            ->assertConflict()->assertJsonPath('booking.status', 'expired');
    }

    public function test_an_artist_cannot_touch_the_booking_of_another(): void
    {
        $booking = $this->reserve();
        $other = User::factory()->create(['role' => 'artiest']);
        // Sanctum onthoudt binnen een test de vorige gebruiker; vergeten, zodat het token van de ander telt.
        $this->app['auth']->forgetGuards();

        $this->withToken($this->token($other))->postJson(route('api.app.bookings.pay', $booking))->assertNotFound();
        $this->withToken($this->token($other))->postJson(route('api.app.bookings.cancel', $booking))->assertNotFound();
        $this->assertSame('pending_payment', $booking->fresh()->status->value);
    }

    public function test_cancelling_says_what_comes_back_and_then_cancels(): void
    {
        $booking = $this->reserve();
        $booking->update(['status' => 'pending_confirmation']);

        $cancel = collect($this->withToken($this->token())->getJson(route('api.app.bookings.show', $booking))->json('actions'))->firstWhere('danger', true);
        $this->assertStringContainsString('€ 166,34', $cancel['cancel']);

        $this->withToken($this->token())->postJson(route('api.app.bookings.cancel', $booking))
            ->assertOk()->assertJsonPath('booking.status', 'cancelled');
        Notification::assertSentTo($this->host, BookingCancelled::class);
    }

    public function test_rescheduling_moves_the_booking_once(): void
    {
        $booking = $this->reserve();
        $booking->update(['status' => 'confirmed', 'confirmed_at' => now()]);
        $newDate = today()->next('monday')->addWeek()->toDateString();

        $this->withToken($this->token())->getJson(route('api.app.bookings.reschedule', $booking))
            ->assertOk()->assertJsonPath('hours', 3);

        $this->withToken($this->token())->postJson(route('api.app.bookings.reschedule.store', $booking), ['date' => $newDate, 'start' => 14])
            ->assertOk()->assertJsonPath('booking.status', 'pending_confirmation');

        $this->assertSame(14, $booking->fresh()->start_hour);
        Notification::assertSentTo($this->host, BookingRescheduled::class);
        $this->withToken($this->token())->getJson(route('api.app.bookings.reschedule', $booking))->assertNotFound();
    }

    public function test_other_requests_of_the_app_do_not_use_up_the_booking_limit(): void
    {
        $token = $this->token();
        foreach (range(1, 15) as $ignored) {
            $this->withToken($token)->getJson(route('api.app.bookings'))->assertOk();
        }

        $this->withToken($token)->postJson(route('api.app.studios.book', $this->room), [...$this->slot(), 'terms' => 1])->assertCreated();
    }

    public function test_the_studio_page_opens_in_the_app_for_an_artist(): void
    {
        $this->actingAs($this->artist)
            ->withSession(['app_bundle' => 'https://localhost'])
            ->withHeader('User-Agent', self::APP)
            ->get(route('studios.show', $this->room))
            ->assertOk()
            ->assertSee('location.replace("https://localhost/#/studio/'.$this->room->id.'")', false);
    }

    public function test_the_studio_page_stays_on_the_website(): void
    {
        $this->actingAs($this->artist)->get(route('studios.show', $this->room))->assertOk()->assertSee('data-availability', false);
    }

    public function test_an_error_page_in_the_app_leads_to_the_app_and_not_to_the_website(): void
    {
        $this->actingAs($this->artist)
            ->withHeader('User-Agent', self::APP)
            ->get('/bestaat-niet')
            ->assertNotFound()
            ->assertSee(route('app.back'), false)
            ->assertDontSee(__('errors.home'));

        $this->withSession(['app_bundle' => 'https://localhost'])->get(route('app.back', ['scherm' => 'studios']))
            ->assertOk()->assertSee('location.replace("https://localhost/#/studios")', false);
        // Alleen een schermnaam: al het andere gaat naar het beginscherm.
        $this->withSession(['app_bundle' => 'https://localhost'])->get(route('app.back', ['scherm' => '//evil.example']))
            ->assertOk()->assertSee('location.replace("https://localhost/")', false);
    }

    public function test_a_server_error_page_in_the_app_leads_to_the_app(): void
    {
        Route::middleware('web')->get('/_test/kapot', fn () => abort(500));

        $this->withHeader('User-Agent', self::APP)->get('/_test/kapot')
            ->assertStatus(500)->assertSee(route('app.back'), false)->assertDontSee(__('errors.home'));
        $this->withHeader('User-Agent', 'Mozilla/5.0 Safari')->get('/_test/kapot')->assertStatus(500)->assertSee(__('errors.home'));
    }
}
