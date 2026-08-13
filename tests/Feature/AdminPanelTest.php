<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Notifications\BookingCancelled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $artist;

    private User $host;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->artist = User::factory()->create(['role' => 'artiest', 'name' => 'Anna Artiest']);
        $this->host = User::factory()->create(['role' => 'verhuurder', 'name' => 'Bram Verhuurder']);

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
        ], $attributes));
    }

    public function test_bookings_page_lists_and_filters(): void
    {
        $this->booking();
        $this->booking(['status' => 'cancelled', 'start_hour' => 14, 'end_hour' => 16]);

        $this->actingAs($this->admin)->get('/dashboard/admin/boekingen')
            ->assertOk()
            ->assertSee('Live room A')
            ->assertSee('Anna Artiest');

        $this->actingAs($this->admin)->get('/dashboard/admin/boekingen?status=cancelled')
            ->assertOk()
            ->assertSee('Geannuleerd');
    }

    public function test_admin_can_change_booking_status_manually(): void
    {
        Notification::fake();
        $booking = $this->booking();

        $this->actingAs($this->admin)->patch('/dashboard/admin/boekingen/' . $booking->id . '/status', [
            'status' => 'cancelled',
        ])->assertRedirect();

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status->value);
        $this->assertSame('admin', $booking->cancelled_by);
        Notification::assertSentTo($this->artist, BookingCancelled::class);
        Notification::assertSentTo($this->host, BookingCancelled::class);
    }

    public function test_status_change_without_cancellation_sends_no_mail(): void
    {
        Notification::fake();
        $booking = $this->booking();

        $this->actingAs($this->admin)->patch('/dashboard/admin/boekingen/' . $booking->id . '/status', [
            'status' => 'completed',
        ]);

        $this->assertSame('completed', $booking->fresh()->status->value);
        Notification::assertNothingSent();
    }

    public function test_bookings_export_returns_csv(): void
    {
        $this->booking();

        $response = $this->actingAs($this->admin)->get('/dashboard/admin/boekingen/export');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $content = $response->streamedContent();
        $this->assertStringContainsString('Live room A', $content);
        $this->assertStringContainsString('Anna Artiest', $content);
    }

    public function test_users_page_lists_and_filters(): void
    {
        $this->actingAs($this->admin)->get('/dashboard/admin/gebruikers')
            ->assertOk()
            ->assertSee('Anna Artiest')
            ->assertSee('Bram Verhuurder');

        $this->actingAs($this->admin)->get('/dashboard/admin/gebruikers?role=verhuurder')
            ->assertOk()
            ->assertSee('Bram Verhuurder')
            ->assertDontSee('Anna Artiest');
    }

    public function test_users_export_returns_csv(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard/admin/gebruikers/export');

        $response->assertOk();
        $this->assertStringContainsString('Anna Artiest', $response->streamedContent());
    }

    public function test_revenue_page_shows_per_studio_totals(): void
    {
        $this->booking();
        $this->booking(['status' => 'completed', 'date' => today()->subDays(3), 'start_hour' => 14, 'end_hour' => 17]);

        $this->actingAs($this->admin)->get('/dashboard/admin/omzet')
            ->assertOk()
            ->assertSee('Redlight Recordings')
            ->assertSee('€ 300,00')
            ->assertSee('€ 32,68');
    }

    public function test_overview_shows_open_tickets_notice(): void
    {
        $this->actingAs($this->admin)->get('/dashboard/admin')
            ->assertOk()
            ->assertDontSee(__('admin.overview.to_tickets'));

        $this->booking(['status' => 'disputed', 'disputed_at' => now(), 'dispute_reason' => 'Er was iets kapot.']);

        $this->actingAs($this->admin)->get('/dashboard/admin')
            ->assertOk()
            ->assertSee('1 open ticket wacht op afhandeling');
    }

    public function test_non_admin_cannot_access_admin_pages(): void
    {
        $this->actingAs($this->host)->get('/dashboard/admin/boekingen')->assertRedirect(route('dashboard.host'));
        $this->actingAs($this->artist)->get('/dashboard/admin/gebruikers')->assertRedirect(route('dashboard.artist'));
    }

    public function test_sitemap_lists_live_rooms_only(): void
    {
        $hidden = $this->room->studio->rooms()->create([
            'title' => 'Verborgen ruimte',
            'description' => 'Nog in review.',
            'type' => 'opname',
            'hourly_rate_cents' => 4000,
            'min_hours' => 2,
            'capacity' => 4,
            'status' => 'in_review',
        ]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee($this->room->slug)
            ->assertDontSee($hidden->slug);
    }

    public function test_studio_page_contains_local_business_schema(): void
    {
        $this->get('/studios/' . $this->room->slug)
            ->assertOk()
            ->assertSee('LocalBusiness', false)
            ->assertSee('Prinsengracht 263', false);
    }

    public function test_public_page_contains_cookie_banner(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-cookie-banner', false)
            ->assertSee(__('cookies.accept'));
    }
}
