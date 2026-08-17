<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StripeTest extends TestCase
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

        $this->room->seedDefaultHours();
    }

    private function booking(array $attributes = []): Booking
    {
        return $this->room->bookings()->create(array_merge([
            'user_id' => $this->artist->id,
            'date' => today()->addDays(10)->next('monday'),
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

    private function hostProfile(): void
    {
        $this->host->hostProfile()->create([
            'name' => 'Redlight Recordings B.V.',
            'phone' => '0612345678',
            'owner_type' => 'ondernemer',
            'btw_plichtig' => true,
            'kvk_number' => '12345678',
            'vat_number' => 'NL123456789B01',
        ]);
    }

    public function test_webhook_rejects_requests_without_configured_secret(): void
    {
        $this->post('/stripe/webhook', [])->assertStatus(400);
    }

    public function test_host_sees_stripe_page_in_simulation_mode(): void
    {
        $this->hostProfile();

        $this->actingAs($this->host->fresh())
            ->get('/dashboard/verhuurder/uitbetalingen')
            ->assertOk()
            ->assertSee(__('host.stripe.title'))
            ->assertSee(__('host.stripe.demo_note'));
    }

    public function test_host_without_profile_is_redirected_to_business_details_first(): void
    {
        $this->actingAs($this->host)
            ->get('/dashboard/verhuurder/uitbetalingen')
            ->assertRedirect(route('host.profile.edit'));
    }

    public function test_onboarding_is_blocked_when_stripe_is_not_configured(): void
    {
        $this->hostProfile();

        $this->actingAs($this->host->fresh())
            ->from('/dashboard/verhuurder/uitbetalingen')
            ->post('/dashboard/verhuurder/uitbetalingen/onboarding')
            ->assertRedirect('/dashboard/verhuurder/uitbetalingen')
            ->assertSessionHasErrors('stripe');
    }

    public function test_paid_route_without_session_returns_to_payment_page(): void
    {
        $booking = $this->booking([
            'status' => 'pending_payment',
            'expires_at' => now()->addMinutes(10),
            'confirmed_at' => null,
        ]);

        $this->actingAs($this->artist)
            ->get('/boekingen/' . $booking->id . '/betaald')
            ->assertRedirect(route('bookings.payment', $booking));
    }

    public function test_host_decline_records_a_full_refund(): void
    {
        Notification::fake();
        $booking = $this->booking(['status' => 'pending_confirmation', 'confirmed_at' => null]);

        $this->actingAs($this->host)
            ->patch('/dashboard/verhuurder/boekingen/' . $booking->id . '/weigeren')
            ->assertRedirect(route('host.bookings.index'));

        $this->assertSame(16634, $booking->fresh()->refunded_cents);
    }

    public function test_artist_cancellation_far_ahead_records_a_full_refund(): void
    {
        Notification::fake();
        $booking = $this->booking();

        $this->actingAs($this->artist)
            ->post('/boekingen/' . $booking->id . '/annuleren')
            ->assertRedirect(route('dashboard.artist'));

        $this->assertSame(16634, $booking->fresh()->refunded_cents);
    }

    public function test_artist_cancellation_within_48_hours_records_half_the_rent(): void
    {
        Notification::fake();
        $this->travelTo(today()->addHours(12));
        $booking = $this->booking(['date' => today()->addDay(), 'start_hour' => 22, 'end_hour' => 24]);

        $this->actingAs($this->artist)
            ->post('/boekingen/' . $booking->id . '/annuleren')
            ->assertRedirect(route('dashboard.artist'));

        $this->assertSame(7500 + 1350 + 284, $booking->fresh()->refunded_cents);
    }

    public function test_auto_cancel_records_a_full_refund(): void
    {
        Notification::fake();
        $booking = $this->booking([
            'status' => 'pending_confirmation',
            'confirmed_at' => null,
            'requested_at' => now()->subHours(25),
        ]);

        $this->artisan('bookings:maintain')->assertSuccessful();

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status->value);
        $this->assertSame('auto', $booking->cancelled_by);
        $this->assertSame(16634, $booking->refunded_cents);
    }

    public function test_admin_ticket_cancel_records_a_full_refund(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $booking = $this->booking([
            'status' => 'disputed',
            'disputed_at' => now(),
            'dispute_reason' => 'De apparatuur werkte niet.',
        ]);

        $this->actingAs($admin)
            ->patch('/dashboard/admin/tickets/' . $booking->id . '/afhandelen', [
                'refund_percent' => 100,
                'resolution_note' => 'De apparatuur bleek inderdaad defect te zijn.',
            ])
            ->assertRedirect(route('admin.tickets.index'));

        $this->assertSame(16634, $booking->fresh()->refunded_cents);
    }

    public function test_admin_queue_detail_shows_approve_form_in_simulation_mode(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $room = $this->room->studio->rooms()->create([
            'title' => 'Booth B',
            'description' => 'Kleine booth.',
            'type' => 'opname',
            'hourly_rate_cents' => 3000,
            'min_hours' => 2,
            'capacity' => 2,
            'status' => 'in_review',
        ]);

        $this->actingAs($admin)
            ->get('/dashboard/admin/wachtrij/' . $room->id)
            ->assertOk()
            ->assertSee(__('admin.queue.approve_button'))
            ->assertDontSee(__('admin.queue.stripe_required_title'));
    }

    public function test_refund_is_not_recorded_twice(): void
    {
        Notification::fake();
        $booking = $this->booking(['status' => 'disputed', 'disputed_at' => now(), 'refunded_cents' => 5000]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch('/dashboard/admin/tickets/' . $booking->id . '/afhandelen', [
                'refund_percent' => 100,
                'resolution_note' => 'Volledige terugbetaling na beoordeling van het ticket.',
            ])
            ->assertRedirect(route('admin.tickets.index'));

        $this->assertSame(5000, $booking->fresh()->refunded_cents);
    }
}
