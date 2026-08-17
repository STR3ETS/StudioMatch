<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Notifications\ContactMessage;
use App\Notifications\HostWelcome;
use App\Notifications\ResponseReminder;
use App\Notifications\RoomSubmitted;
use App\Notifications\RoomSubmittedAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ScopeExtrasTest extends TestCase
{
    use RefreshDatabase;

    private User $artist;

    private User $host;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artist = User::factory()->create(['role' => 'artiest']);
        $this->host = User::factory()->create(['role' => 'verhuurder']);

        $studio = $this->host->studios()->create([
            'name' => 'Redlight Recordings',
            'street' => 'Prinsengracht 263',
            'postal_code' => '1016 GV',
            'city' => 'Amsterdam',
        ]);

        $this->room = $studio->rooms()->create([
            'title' => 'Live room A',
            'description' => 'Fijne ruimte.',
            'type' => 'opname',
            'hourly_rate_cents' => 5000,
            'min_hours' => 2,
            'capacity' => 6,
            'status' => 'live',
        ]);
    }

    public function test_contact_form_notifies_admins(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->post('/contact', [
            'name' => 'Sam de Wit',
            'email' => 'sam@voorbeeld.nl',
            'subject' => 'general',
            'message' => 'Ik heb een vraag over het boeken van een studio.',
        ])->assertRedirect(route('contact'))->assertSessionHas('status');

        Notification::assertSentTo($admin, ContactMessage::class);
    }

    public function test_contact_form_validates_input(): void
    {
        $this->post('/contact', ['name' => 'Sam', 'email' => 'geen-email', 'subject' => 'x', 'message' => 'kort'])
            ->assertSessionHasErrors(['email', 'subject', 'message']);
    }

    public function test_host_gets_response_reminder_after_12_hours(): void
    {
        Notification::fake();

        $booking = $this->room->bookings()->create([
            'user_id' => $this->artist->id,
            'date' => today()->addDays(3),
            'start_hour' => 10,
            'end_hour' => 12,
            'hourly_rate_cents' => 5000,
            'rent_cents' => 10000,
            'service_fee_cents' => 900,
            'vat_cents' => 189,
            'total_cents' => 11089,
            'status' => 'pending_confirmation',
            'terms_accepted_at' => now(),
            'requested_at' => now()->subHours(13),
        ]);

        $this->artisan('bookings:maintain')->assertSuccessful();

        Notification::assertSentTo($this->host, ResponseReminder::class);
        $this->assertNotNull($booking->fresh()->response_reminder_sent_at);

        Notification::fake();
        $this->artisan('bookings:maintain')->assertSuccessful();
        Notification::assertNothingSent();
    }

    public function test_resubmitting_rejected_room_notifies_host_and_admin(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->room->update(['status' => 'afgekeurd', 'rejection_reason' => 'Foto\'s te donker.']);

        $this->actingAs($this->host)->put('/dashboard/verhuurder/ruimtes/' . $this->room->id, [
            'title' => 'Live room A',
            'description' => 'Fijne ruimte met nieuwe fotos.',
            'type' => 'opname',
            'hourly_rate' => 50,
            'min_hours' => 2,
            'capacity' => 6,
        ])->assertRedirect();

        $this->assertSame('in_review', $this->room->fresh()->status->value);
        Notification::assertSentTo($this->host, RoomSubmitted::class);
        Notification::assertSentTo($admin, RoomSubmittedAdmin::class);
    }

    public function test_new_host_receives_welcome_notification(): void
    {
        Notification::fake();

        $this->post('/registreren', [
            'role' => 'verhuurder',
            'name' => 'Nieuwe Verhuurder',
            'email' => 'nieuw@voorbeeld.nl',
            'password' => 'Wachtwoord123', 'password_confirmation' => 'Wachtwoord123',
            'terms' => '1',
        ])->assertRedirect();

        Notification::assertSentTo(User::where('email', 'nieuw@voorbeeld.nl')->first(), HostWelcome::class);
    }

    public function test_artist_registration_sends_no_welcome(): void
    {
        Notification::fake();

        $this->post('/registreren', [
            'role' => 'artiest',
            'name' => 'Nieuwe Artiest',
            'email' => 'artiest-nieuw@voorbeeld.nl',
            'password' => 'Wachtwoord123', 'password_confirmation' => 'Wachtwoord123',
            'terms' => '1',
        ])->assertRedirect();

        Notification::assertNotSentTo(User::where('email', 'artiest-nieuw@voorbeeld.nl')->first(), HostWelcome::class);
    }
}
