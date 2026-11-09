<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use App\Notifications\NewTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Pushmeldingen naar de app: de telefoon meldt zich aan, uitloggen meldt hem af, en een nieuw ticket gaat
 * via Firebase naar de telefoons van de beheerder (met Tickets als bestemming).
 */
class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'locale' => 'nl']);
    }

    /**
     * Een serviceaccount-sleutel zoals Firebase hem geeft. Het toegangstoken staat al in de cache (zoals
     * na de eerste push), dus ondertekenen met de sleutel is hier niet nodig.
     */
    private function fakeCredentials(): void
    {
        $path = storage_path('framework/testing/firebase-test.json');
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, json_encode([
            'project_id' => 'studiomatch-test',
            'client_email' => 'push@studiomatch-test.iam.gserviceaccount.com',
            'private_key' => 'niet-gebruikt',
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ]));
        config(['services.firebase.credentials' => $path]);
        Cache::put('firebase-access-token', 'toegang', now()->addMinutes(50));
    }

    private function ticket(): Booking
    {
        $host = User::factory()->create(['role' => 'verhuurder']);
        $studio = $host->studios()->create(['name' => 'Havenklank', 'street' => 'Kade 1', 'postal_code' => '3072 HS', 'city' => 'Rotterdam']);
        $room = $studio->rooms()->create([
            'title' => 'Studio Noord', 'description' => 'Een ruimte.', 'type' => 'opname',
            'hourly_rate_cents' => 5000, 'min_hours' => 1, 'capacity' => 4, 'status' => 'live',
        ]);

        return $room->bookings()->create([
            'user_id' => User::factory()->create(['role' => 'artiest'])->id,
            'date' => today(), 'start_hour' => 10, 'end_hour' => 12, 'hourly_rate_cents' => 5000,
            'rent_cents' => 10000, 'service_fee_cents' => 900, 'vat_cents' => 189, 'total_cents' => 11089,
            'status' => 'disputed', 'disputed_at' => now(), 'dispute_reason' => 'De microfoon deed het niet.',
            'terms_accepted_at' => now(),
        ]);
    }

    public function test_a_phone_registers_for_push_and_logging_out_removes_it(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson(route('api.app.devices'), ['token' => 'telefoon-1', 'platform' => 'android'])->assertOk();
        $this->assertSame(['telefoon-1'], $this->admin->deviceTokens()->pluck('token')->all());

        $this->withToken($token)->postJson(route('api.app.logout'), ['push_token' => 'telefoon-1'])->assertNoContent();
        $this->assertSame(0, $this->admin->deviceTokens()->count());
    }

    public function test_a_new_ticket_reaches_the_admins_phone_and_opens_tickets(): void
    {
        $this->fakeCredentials();
        $this->admin->deviceTokens()->create(['token' => 'telefoon-1', 'platform' => 'android']);
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'toegang']),
            'fcm.googleapis.com/*' => Http::response(['name' => 'projects/studiomatch-test/messages/1']),
        ]);

        Notification::sendNow($this->admin, new NewTicket($this->ticket(), 'dispute'));

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'fcm.googleapis.com/v1/projects/studiomatch-test/messages:send')
            && $request['message']['token'] === 'telefoon-1'
            && $request['message']['notification']['title'] === 'Nieuw ticket: probleem gemeld'
            && str_contains($request['message']['notification']['body'], 'Havenklank - Studio Noord')
            && $request['message']['data']['url'] === '/dashboard/admin/tickets');
    }

    public function test_a_phone_that_no_longer_has_the_app_is_forgotten(): void
    {
        $this->fakeCredentials();
        $this->admin->deviceTokens()->create(['token' => 'oude-telefoon', 'platform' => 'android']);
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'toegang']),
            'fcm.googleapis.com/*' => Http::response(['error' => ['status' => 'NOT_FOUND', 'details' => [['errorCode' => 'UNREGISTERED']]]], 404),
        ]);

        Notification::sendNow($this->admin, new NewTicket($this->ticket(), 'dispute'));

        $this->assertSame(0, $this->admin->deviceTokens()->count());
    }

    public function test_without_firebase_nothing_is_sent_and_nothing_breaks(): void
    {
        config(['services.firebase.credentials' => null]);
        $this->admin->deviceTokens()->create(['token' => 'telefoon-1', 'platform' => 'android']);
        Http::fake();

        Notification::sendNow($this->admin, new NewTicket($this->ticket(), 'dispute'));

        Http::assertNothingSent();
    }
}
