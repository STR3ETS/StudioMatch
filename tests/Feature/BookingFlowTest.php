<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingDeclined;
use App\Notifications\BookingReceived;
use App\Notifications\BookingRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $artist;

    private User $host;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artist = User::factory()->create(['role' => 'artiest', 'street' => 'Keizersgracht 12', 'postal_code' => '1015 CN', 'city' => 'Amsterdam']);
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
            'house_rules' => "Niet roken\nMax 6 personen",
            'status' => 'live',
        ]);

        $this->room->seedDefaultHours();
    }

    private function slot(): array
    {
        return ['date' => today()->next('monday')->toDateString(), 'start' => 10, 'hours' => 3];
    }

    private function bookingViaFlow(): Booking
    {
        $this->actingAs($this->artist)->post('/studios/' . $this->room->slug . '/boeken', [
            ...$this->slot(),
            'terms' => '1',
        ]);

        return Booking::latest('id')->first();
    }

    public function test_booking_with_optional_engineer_adds_surcharge(): void
    {
        $this->room->update(['engineer_included' => false, 'engineer_rate_cents' => 1000]);

        $this->actingAs($this->artist)->post('/studios/' . $this->room->slug . '/boeken', [
            ...$this->slot(),
            'engineer' => '1',
            'terms' => '1',
        ]);

        $booking = Booking::latest('id')->first();

        $this->assertTrue($booking->with_engineer);
        $this->assertSame(6000, (int) $booking->hourly_rate_cents);
        $this->assertSame(18000, (int) $booking->rent_cents);
        $this->assertSame(1620, (int) $booking->service_fee_cents);
        $this->assertSame(340, (int) $booking->vat_cents);
        $this->assertSame(19960, (int) $booking->total_cents);
    }

    public function test_engineer_param_is_ignored_when_room_has_no_optional_engineer(): void
    {
        $this->actingAs($this->artist)->post('/studios/' . $this->room->slug . '/boeken', [
            ...$this->slot(),
            'engineer' => '1',
            'terms' => '1',
        ]);

        $booking = Booking::latest('id')->first();

        $this->assertFalse($booking->with_engineer);
        $this->assertSame(15000, (int) $booking->rent_cents);
    }

    public function test_guest_is_redirected_to_login_from_checkout(): void
    {
        $this->get('/studios/' . $this->room->slug . '/boeken?date=2030-01-07&start=10&hours=2')
            ->assertRedirect(route('login'));
    }

    public function test_checkout_shows_price_breakdown_and_house_rules(): void
    {
        $slot = $this->slot();

        $this->actingAs($this->artist)
            ->get('/studios/' . $this->room->slug . '/boeken?' . http_build_query($slot))
            ->assertOk()
            ->assertSee('€ 150,00')
            ->assertSee('€ 13,50')
            ->assertSee('€ 2,84')
            ->assertSee('€ 166,34')
            ->assertSee('Niet roken');
    }

    public function test_checkout_rejects_unavailable_slot(): void
    {
        $sunday = today()->next('sunday')->toDateString();

        $this->actingAs($this->artist)
            ->get('/studios/' . $this->room->slug . '/boeken?date=' . $sunday . '&start=10&hours=2')
            ->assertSessionHasErrors('slot');
    }

    public function test_booking_is_created_with_hold_and_terms_logged(): void
    {
        $booking = $this->bookingViaFlow();

        $this->assertSame('pending_payment', $booking->status->value);
        $this->assertSame(15000, $booking->rent_cents);
        $this->assertSame(1350, $booking->service_fee_cents);
        $this->assertSame(284, $booking->vat_cents);
        $this->assertSame(16634, $booking->total_cents);
        $this->assertNotNull($booking->terms_accepted_at);
        $this->assertTrue($booking->expires_at->isFuture());
    }

    public function test_held_slot_blocks_double_booking(): void
    {
        $this->bookingViaFlow();

        $other = User::factory()->create(['role' => 'artiest']);

        $this->actingAs($other)->post('/studios/' . $this->room->slug . '/boeken', [
            ...$this->slot(),
            'terms' => '1',
        ])->assertSessionHasErrors('slot');

        $this->assertSame(1, Booking::count());
    }

    public function test_expired_hold_frees_the_slot(): void
    {
        $booking = $this->bookingViaFlow();
        $booking->update(['expires_at' => now()->subMinute()]);

        $other = User::factory()->create(['role' => 'artiest', 'street' => 'Teststraat 1', 'postal_code' => '1234 AB', 'city' => 'Utrecht']);

        $this->actingAs($other)->post('/studios/' . $this->room->slug . '/boeken', [
            ...$this->slot(),
            'terms' => '1',
        ])->assertSessionDoesntHaveErrors('slot');

        $this->assertSame(2, Booking::count());
    }

    public function test_paying_sends_request_to_host(): void
    {
        Notification::fake();
        $booking = $this->bookingViaFlow();

        $this->actingAs($this->artist)
            ->post('/boekingen/' . $booking->id . '/betalen')
            ->assertRedirect(route('dashboard.artist'));

        $this->assertSame('pending_confirmation', $booking->fresh()->status->value);
        Notification::assertSentTo($this->host, BookingRequested::class);
        Notification::assertSentTo($this->artist, BookingReceived::class);
    }

    public function test_host_can_accept_booking(): void
    {
        Notification::fake();
        $booking = $this->bookingViaFlow();
        $booking->update(['status' => 'pending_confirmation']);

        $this->actingAs($this->host)->patch('/dashboard/verhuurder/boekingen/' . $booking->id . '/accepteren');

        $booking->refresh();
        $this->assertSame('confirmed', $booking->status->value);
        $this->assertNotNull($booking->confirmed_at);
        Notification::assertSentTo($this->artist, BookingConfirmed::class);
        Notification::assertSentTo($this->host, BookingConfirmed::class);
    }

    public function test_host_can_decline_booking(): void
    {
        Notification::fake();
        $booking = $this->bookingViaFlow();
        $booking->update(['status' => 'pending_confirmation']);

        $this->actingAs($this->host)->patch('/dashboard/verhuurder/boekingen/' . $booking->id . '/weigeren');

        $this->assertSame('declined', $booking->fresh()->status->value);
        Notification::assertSentTo($this->artist, BookingDeclined::class);
    }

    public function test_other_host_cannot_accept_booking(): void
    {
        $booking = $this->bookingViaFlow();
        $booking->update(['status' => 'pending_confirmation']);
        $other = User::factory()->create(['role' => 'verhuurder']);

        $this->actingAs($other)
            ->patch('/dashboard/verhuurder/boekingen/' . $booking->id . '/accepteren')
            ->assertForbidden();
    }

    public function test_artist_can_cancel_confirmed_booking(): void
    {
        Notification::fake();
        $booking = $this->bookingViaFlow();
        $booking->update(['status' => 'confirmed']);

        $this->actingAs($this->artist)->post('/boekingen/' . $booking->id . '/annuleren');

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status->value);
        $this->assertSame('artist', $booking->cancelled_by);
        Notification::assertSentTo($this->artist, BookingCancelled::class);
        Notification::assertSentTo($this->host, BookingCancelled::class);
    }

    public function test_booked_slot_disappears_from_search_with_time_filter(): void
    {
        $room2 = $this->room->studio->rooms()->create([
            'title' => 'Live room B',
            'description' => 'Tweede ruimte.',
            'type' => 'opname',
            'hourly_rate_cents' => 4000,
            'min_hours' => 2,
            'capacity' => 4,
            'status' => 'live',
        ]);
        $room2->seedDefaultHours();

        $booking = $this->bookingViaFlow();
        $booking->update(['status' => 'confirmed']);
        $slot = $this->slot();

        $this->get('/studios?date=' . $slot['date'] . '&start=10&end=13')
            ->assertDontSee('Live room A')
            ->assertSee('Live room B');

        $this->get('/studios?date=' . $slot['date'] . '&start=14&end=16')
            ->assertSee('Live room A');
    }

    public function test_maintain_command_expires_and_auto_cancels(): void
    {
        Notification::fake();

        $expired = $this->bookingViaFlow();
        $expired->update(['expires_at' => now()->subMinute()]);

        $stale = Booking::create([
            'room_id' => $this->room->id,
            'user_id' => $this->artist->id,
            'date' => today()->addDays(10),
            'start_hour' => 14,
            'end_hour' => 16,
            'hourly_rate_cents' => 5000,
            'rent_cents' => 10000,
            'service_fee_cents' => 900,
            'vat_cents' => 189,
            'total_cents' => 11089,
            'status' => 'pending_confirmation',
            'terms_accepted_at' => now()->subDays(2),
        ]);
        $stale->forceFill(['created_at' => now()->subDays(2)])->save();

        $this->artisan('bookings:maintain')->assertSuccessful();

        $this->assertSame('expired', $expired->fresh()->status->value);
        $this->assertSame('cancelled', $stale->fresh()->status->value);
        $this->assertSame('auto', $stale->fresh()->cancelled_by);
        Notification::assertSentTo($this->artist, BookingCancelled::class);
    }

    public function test_host_cannot_book(): void
    {
        $this->actingAs($this->host)
            ->get('/studios/' . $this->room->slug . '/boeken?date=2030-01-07&start=10&hours=2')
            ->assertRedirect(route('dashboard.host'));
    }

    public function test_host_overview_shows_pending_request_notice(): void
    {
        $this->actingAs($this->host)->get('/dashboard/verhuurder')
            ->assertOk()
            ->assertDontSee(__('host.overview.requests_action'));

        $booking = $this->bookingViaFlow();
        $booking->update(['status' => 'pending_confirmation']);

        $this->actingAs($this->host)->get('/dashboard/verhuurder')
            ->assertOk()
            ->assertSee('1 nieuwe boekingsaanvraag wacht op je reactie');
    }

    public function test_artist_dashboard_shows_bookings(): void
    {
        $booking = $this->bookingViaFlow();
        $booking->update(['status' => 'confirmed']);

        $this->actingAs($this->artist)->get('/dashboard/artiest')
            ->assertOk()
            ->assertSee('Live room A')
            ->assertSee('Prinsengracht 263');
    }
}
