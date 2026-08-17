<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Notifications\ContactConfirmation;
use App\Notifications\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FeedbackFixesTest extends TestCase
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

    private function booking(array $attributes = []): Booking
    {
        return $this->room->bookings()->create(array_merge([
            'user_id' => $this->artist->id,
            'date' => today()->addDays(5),
            'start_hour' => 10,
            'end_hour' => 13,
            'hourly_rate_cents' => 5000,
            'rent_cents' => 15000,
            'service_fee_cents' => 1350,
            'vat_cents' => 284,
            'total_cents' => 16634,
            'status' => 'pending_confirmation',
            'terms_accepted_at' => now(),
            'requested_at' => now(),
        ], $attributes));
    }

    public function test_declining_twice_redirects_gracefully_instead_of_404(): void
    {
        Notification::fake();
        $booking = $this->booking();

        $this->actingAs($this->host)
            ->patch('/dashboard/verhuurder/boekingen/' . $booking->id . '/weigeren')
            ->assertRedirect(route('host.bookings.index'));

        $this->actingAs($this->host)
            ->patch('/dashboard/verhuurder/boekingen/' . $booking->id . '/weigeren')
            ->assertRedirect(route('host.bookings.index'))
            ->assertSessionHas('status', __('host.bookings.already_handled'));

        $this->assertSame('declined', $booking->fresh()->status->value);
    }

    public function test_accepting_a_handled_booking_redirects_gracefully(): void
    {
        Notification::fake();
        $booking = $this->booking(['status' => 'cancelled']);

        $this->actingAs($this->host)
            ->patch('/dashboard/verhuurder/boekingen/' . $booking->id . '/accepteren')
            ->assertRedirect(route('host.bookings.index'))
            ->assertSessionHas('status', __('host.bookings.already_handled'));
    }

    public function test_admin_status_change_preserves_the_active_filter(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $booking = $this->booking(['status' => 'confirmed', 'confirmed_at' => now()]);

        $this->actingAs($admin)
            ->patch('/dashboard/admin/boekingen/' . $booking->id . '/status', [
                'status' => 'cancelled',
                'filter_status' => 'confirmed',
            ])
            ->assertRedirect(route('admin.bookings.index', ['status' => 'confirmed']));

        $this->assertSame('cancelled', $booking->fresh()->status->value);
        $this->assertSame(16634, $booking->fresh()->refunded_cents);
    }

    public function test_contact_form_sends_to_configured_address_and_confirms_sender(): void
    {
        Notification::fake();
        config(['studio.contact_email' => 'info@studiomatch.nl']);

        $this->post('/contact', [
            'name' => 'Sam de Wit',
            'email' => 'sam@voorbeeld.nl',
            'subject' => 'general',
            'message' => 'Ik heb een vraag over het boeken van een studio.',
        ])->assertRedirect(route('contact'));

        Notification::assertSentOnDemand(ContactMessage::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'info@studiomatch.nl');
        Notification::assertSentOnDemand(ContactConfirmation::class, fn ($notification, $channels, $notifiable) => array_key_exists('sam@voorbeeld.nl', $notifiable->routes['mail']));
    }

    public function test_registration_stores_the_active_locale(): void
    {
        $this->withSession(['locale' => 'en'])->post('/registreren', [
            'role' => 'artiest',
            'name' => 'New Artist',
            'email' => 'new-artist@example.com',
            'password' => 'Wachtwoord123', 'password_confirmation' => 'Wachtwoord123',
            'terms' => '1',
        ])->assertRedirect();

        $this->assertSame('en', User::where('email', 'new-artist@example.com')->first()->locale);
    }

    public function test_language_switch_updates_user_locale(): void
    {
        $this->actingAs($this->artist)->get('/language/en')->assertRedirect();

        $this->assertSame('en', $this->artist->fresh()->locale);
        $this->assertSame('en', $this->artist->fresh()->preferredLocale());
    }
}
