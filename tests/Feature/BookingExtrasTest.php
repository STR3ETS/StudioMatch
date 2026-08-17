<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingRescheduled;
use App\Notifications\ProblemReported;
use App\Notifications\SessionReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class BookingExtrasTest extends TestCase
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
            'date' => today()->next('monday'),
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

    public function test_artist_can_reschedule_once_to_a_free_slot(): void
    {
        Notification::fake();
        $booking = $this->booking(['date' => today()->addDays(10)->next('monday')]);
        $newDate = $booking->date->copy()->addDay();

        $this->actingAs($this->artist)->post('/boekingen/' . $booking->id . '/verzetten', [
            'date' => $newDate->toDateString(),
            'start' => 14,
        ])->assertRedirect(route('dashboard.artist'));

        $booking->refresh();
        $this->assertSame($newDate->toDateString(), $booking->date->toDateString());
        $this->assertSame(14, (int) $booking->start_hour);
        $this->assertSame(17, (int) $booking->end_hour);
        $this->assertSame('pending_confirmation', $booking->status->value);
        $this->assertNotNull($booking->rescheduled_at);
        Notification::assertSentTo($this->artist, BookingRescheduled::class);
        Notification::assertSentTo($this->host, BookingRescheduled::class);
    }

    public function test_rescheduling_twice_is_not_allowed(): void
    {
        $booking = $this->booking([
            'date' => today()->addDays(10)->next('monday'),
            'rescheduled_at' => now(),
        ]);

        $this->actingAs($this->artist)
            ->get('/boekingen/' . $booking->id . '/verzetten')
            ->assertNotFound();
    }

    public function test_rescheduling_within_48_hours_is_not_allowed(): void
    {
        $booking = $this->booking(['date' => today()->addDay()]);

        $this->actingAs($this->artist)
            ->get('/boekingen/' . $booking->id . '/verzetten')
            ->assertNotFound();
    }

    public function test_rescheduling_to_a_taken_slot_fails(): void
    {
        $booking = $this->booking(['date' => today()->addDays(10)->next('monday')]);
        $other = User::factory()->create(['role' => 'artiest']);
        $this->booking(['user_id' => $other->id, 'date' => $booking->date->copy()->addDay(), 'start_hour' => 14, 'end_hour' => 17]);

        $this->actingAs($this->artist)->post('/boekingen/' . $booking->id . '/verzetten', [
            'date' => $booking->date->copy()->addDay()->toDateString(),
            'start' => 14,
        ])->assertSessionHasErrors('slot');
    }

    public function test_artist_can_report_problem_within_window(): void
    {
        Notification::fake();
        \Illuminate\Support\Facades\Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $booking = $this->booking(['date' => today(), 'start_hour' => max(0, now()->hour - 1), 'end_hour' => min(24, now()->hour + 1)]);

        $this->actingAs($this->artist)->post('/boekingen/' . $booking->id . '/probleem', [
            'dispute_reason' => 'De apparatuur werkte niet en er was niemand aanwezig.',
            'photos' => [
                \Illuminate\Http\UploadedFile::fake()->image('bewijs1.jpg'),
                \Illuminate\Http\UploadedFile::fake()->image('bewijs2.jpg'),
            ],
        ])->assertRedirect(route('dashboard.artist'));

        $booking->refresh();
        $this->assertSame('disputed', $booking->status->value);
        $this->assertNotNull($booking->disputed_at);
        $this->assertCount(2, $booking->dispute_photos);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($booking->dispute_photos[0]);
        Notification::assertSentTo($this->host, ProblemReported::class);
        Notification::assertSentTo($admin, ProblemReported::class);

        $this->actingAs($admin)->get('/dashboard/admin/tickets')
            ->assertOk()
            ->assertSee(__('admin.tickets.photos'))
            ->assertSee($booking->dispute_photos[0]);
    }

    public function test_problem_cannot_be_reported_before_start_or_after_window(): void
    {
        $future = $this->booking(['date' => today()->addDays(5)]);
        $old = $this->booking(['date' => today()->subDays(3), 'status' => 'completed']);

        $this->actingAs($this->artist)->post('/boekingen/' . $future->id . '/probleem', [
            'dispute_reason' => 'Dit zou niet mogen werken want de sessie is nog niet begonnen.',
        ])->assertNotFound();

        $this->actingAs($this->artist)->post('/boekingen/' . $old->id . '/probleem', [
            'dispute_reason' => 'Dit zou niet mogen werken want het venster is voorbij.',
        ])->assertNotFound();
    }

    public function test_dismissed_dispute_shows_note_and_blocks_new_report(): void
    {
        $booking = $this->booking([
            'date' => today(),
            'start_hour' => max(0, now()->hour - 1),
            'end_hour' => min(24, now()->hour + 1),
            'status' => 'completed',
            'disputed_at' => now()->subHour(),
            'dispute_reason' => 'De mixer was kapot.',
        ]);

        $this->actingAs($this->artist)->get('/dashboard/artiest')
            ->assertOk()
            ->assertSee(__('booking.problem.dismissed', ['date' => $booking->disputed_at->translatedFormat('j F')]));

        $this->actingAs($this->artist)->post('/boekingen/' . $booking->id . '/probleem', [
            'dispute_reason' => 'Ik probeer het gewoon nog een keer.',
        ])->assertNotFound();
    }

    public function test_upheld_dispute_shows_note_to_artist(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $booking = $this->booking([
            'date' => today()->subDay(),
            'status' => 'disputed',
            'disputed_at' => now()->subHours(3),
            'dispute_reason' => 'De mixer was kapot.',
        ]);

        Notification::fake();
        $this->actingAs($admin)->patch('/dashboard/admin/tickets/' . $booking->id . '/afhandelen', [
            'refund_percent' => 100,
            'resolution_note' => 'De studio heeft bevestigd dat de mixer defect was.',
        ]);

        $this->actingAs($this->artist)->get('/dashboard/artiest')
            ->assertOk()
            ->assertSee(__('booking.problem.upheld', ['date' => $booking->fresh()->disputed_at->translatedFormat('j F')]));
    }

    public function test_admin_can_resolve_disputes_with_full_partial_or_no_refund(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $release = $this->booking(['status' => 'disputed', 'disputed_at' => now(), 'dispute_reason' => 'Probleem A']);
        $partial = $this->booking(['status' => 'disputed', 'disputed_at' => now(), 'dispute_reason' => 'Probleem B', 'start_hour' => 14, 'end_hour' => 17]);
        $cancel = $this->booking(['status' => 'disputed', 'disputed_at' => now(), 'dispute_reason' => 'Probleem C', 'start_hour' => 18, 'end_hour' => 21]);

        $this->actingAs($admin)->get('/dashboard/admin/tickets')
            ->assertOk()
            ->assertSee('Probleem A')
            ->assertSee('Probleem B');

        $this->actingAs($admin)->patch('/dashboard/admin/tickets/' . $release->id . '/afhandelen', [
            'refund_percent' => 0,
            'resolution_note' => 'De melding is na overleg met beide partijen afgewezen.',
        ]);
        $this->assertSame('completed', $release->fresh()->status->value);
        $this->assertNull($release->fresh()->refunded_cents);
        $this->assertSame('dismissed', $release->fresh()->disputeOutcome());

        $this->actingAs($admin)->patch('/dashboard/admin/tickets/' . $partial->id . '/afhandelen', [
            'refund_percent' => 50,
            'resolution_note' => 'Beide partijen hebben deels gelijk, we splitsen het verschil.',
        ]);
        $this->assertSame('completed', $partial->fresh()->status->value);
        $this->assertSame(7500 + 1350 + 284, $partial->fresh()->refunded_cents);
        $this->assertSame('partial', $partial->fresh()->disputeOutcome());

        $this->actingAs($admin)->patch('/dashboard/admin/tickets/' . $cancel->id . '/afhandelen', [
            'refund_percent' => 100,
            'resolution_note' => 'De studio was in gebreke, volledige terugbetaling.',
        ]);
        $this->assertSame('cancelled', $cancel->fresh()->status->value);
        $this->assertSame('admin', $cancel->fresh()->cancelled_by);
        $this->assertSame('upheld', $cancel->fresh()->disputeOutcome());
        Notification::assertSentTo($this->artist, \App\Notifications\DisputeResolved::class);
        Notification::assertSentTo($this->host, \App\Notifications\DisputeResolved::class);
    }

    public function test_reminder_is_sent_once_24_hours_before_start(): void
    {
        Notification::fake();
        $booking = $this->booking(['date' => today()->addDay(), 'start_hour' => now()->hour, 'end_hour' => min(24, now()->hour + 2)]);

        $this->artisan('bookings:maintain')->assertSuccessful();

        $this->assertNotNull($booking->fresh()->reminder_sent_at);
        Notification::assertSentTo($this->artist, SessionReminder::class);
        Notification::assertSentTo($this->host, SessionReminder::class);

        Notification::fake();
        $this->artisan('bookings:maintain')->assertSuccessful();
        Notification::assertNothingSent();
    }

    public function test_rescheduled_booking_is_not_auto_cancelled_on_old_created_at(): void
    {
        Notification::fake();
        $booking = $this->booking([
            'date' => today()->addDays(10),
            'status' => 'pending_confirmation',
            'requested_at' => now(),
        ]);
        $booking->forceFill(['created_at' => now()->subDays(3)])->save();

        $this->artisan('bookings:maintain')->assertSuccessful();

        $this->assertSame('pending_confirmation', $booking->fresh()->status->value);
    }

    public function test_host_sees_dispute_history_with_outcome(): void
    {
        $open = $this->booking(['status' => 'disputed', 'disputed_at' => now(), 'dispute_reason' => 'Open melding hier.']);
        $this->booking(['status' => 'completed', 'disputed_at' => now()->subDay(), 'dispute_reason' => 'Afgewezen melding hier.', 'start_hour' => 14, 'end_hour' => 16]);

        $this->actingAs($this->host)->get('/dashboard/verhuurder/boekingen')
            ->assertOk()
            ->assertSee(__('host.bookings.disputes_title'))
            ->assertSee('Open melding hier.')
            ->assertSee(__('host.bookings.dispute_open'))
            ->assertSee('Afgewezen melding hier.')
            ->assertSee(__('host.bookings.dispute_dismissed'));
    }

    public function test_host_agenda_shows_bookings_and_blocks(): void
    {
        $this->booking(['date' => today()->addDays(3)]);
        $this->room->exceptions()->create(['date' => today()->addDays(4), 'type' => 'block', 'start_hour' => 9, 'end_hour' => 12, 'label' => 'Onderhoud']);

        $this->actingAs($this->host)->get('/dashboard/verhuurder/agenda')
            ->assertOk()
            ->assertSee('Live room A')
            ->assertSee('Onderhoud');
    }

    public function test_host_revenue_page_shows_totals(): void
    {
        $this->booking(['date' => today()->subDays(7), 'status' => 'completed']);
        $this->booking(['date' => today()->addDays(7)]);

        $this->actingAs($this->host)->get('/dashboard/verhuurder/omzet')
            ->assertOk()
            ->assertSee('€ 150,00');
    }

    public function test_host_can_report_damage_with_photos(): void
    {
        Notification::fake();
        \Illuminate\Support\Facades\Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $booking = $this->booking(['date' => today()->subDays(2), 'status' => 'completed']);

        $this->actingAs($this->host)->get('/dashboard/verhuurder/schade')
            ->assertOk()
            ->assertSee('Live room A');

        $this->actingAs($this->host)->post('/dashboard/verhuurder/schade/' . $booking->id, [
            'damage_reason' => 'De condensatormicrofoon is beschadigd achtergelaten.',
            'photos' => [\Illuminate\Http\UploadedFile::fake()->image('schade.jpg')],
        ])->assertRedirect(route('host.damage.index'));

        $booking->refresh();
        $this->assertNotNull($booking->damage_reported_at);
        $this->assertCount(1, $booking->damage_photos);
        Notification::assertSentTo($admin, \App\Notifications\DamageReported::class);

        $this->actingAs($this->host)->get('/dashboard/verhuurder/schade')
            ->assertSee('De condensatormicrofoon is beschadigd achtergelaten.');
        $this->actingAs($this->host)->post('/dashboard/verhuurder/schade/' . $booking->id, [
            'damage_reason' => 'Nog een keer proberen te melden.',
        ])->assertNotFound();
    }

    public function test_damage_cannot_be_reported_for_another_hosts_booking(): void
    {
        $booking = $this->booking(['date' => today()->subDays(2), 'status' => 'completed']);
        $other = User::factory()->create(['role' => 'verhuurder']);

        $this->actingAs($other)->post('/dashboard/verhuurder/schade/' . $booking->id, [
            'damage_reason' => 'Dit is niet mijn boeking maar toch proberen.',
        ])->assertForbidden();
    }

    public function test_legal_stub_pages_render(): void
    {
        foreach (['/voorwaarden', '/privacy', '/disclaimer', '/cookiebeleid'] as $uri) {
            $this->get($uri)->assertOk()->assertSee(__('legal.pending_title'));
        }
    }

    public function test_ical_feed_requires_signature_and_lists_events(): void
    {
        $this->booking(['date' => today()->addDays(3)]);

        $this->get('/ical/ruimte/' . $this->room->id)->assertForbidden();

        $this->get(URL::signedRoute('ical.room', ['room' => $this->room->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
            ->assertSee('BEGIN:VCALENDAR', false)
            ->assertSee('BEGIN:VEVENT', false);
    }
}
