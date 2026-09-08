<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MultiDayBookingTest extends TestCase
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
            'day_rate_cents' => 40000,
            'min_hours' => 2,
            'min_days' => 2,
            'capacity' => 6,
            'status' => 'live',
        ]);

        $this->room->seedDefaultHours();
        $this->room->refresh();
    }

    private function monday(int $weeks = 1): Carbon
    {
        return today()->next(Carbon::MONDAY)->addWeeks($weeks - 1);
    }

    private function bookDays(?string $from = null, ?string $until = null): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->artist)->post('/studios/' . $this->room->slug . '/boeken', [
            'date' => $from ?? $this->monday()->toDateString(),
            'end_date' => $until ?? $this->monday()->addDays(2)->toDateString(),
            'terms' => '1',
        ]);
    }

    public function test_a_room_with_a_day_rate_can_be_booked_for_whole_days(): void
    {
        $this->bookDays()->assertRedirect();

        $booking = $this->room->bookings()->firstOrFail();

        $this->assertTrue($booking->isMultiDay());
        $this->assertSame(3, $booking->dayCount());
        $this->assertSame(120000, (int) $booking->rent_cents);
        $this->assertSame(40000, (int) $booking->day_rate_cents);
    }

    public function test_a_multi_day_booking_has_no_start_time(): void
    {
        $this->bookDays();

        $booking = $this->room->bookings()->firstOrFail();

        $this->assertSame($this->monday()->startOfDay()->toDateTimeString(), $booking->startsAt()->toDateTimeString());
        $this->assertSame($this->monday()->addDays(3)->startOfDay()->toDateTimeString(), $booking->endsAt()->toDateTimeString());
        $this->assertStringContainsString('3', $booking->timeRange());
        $this->assertFalse($booking->canReschedule());
    }

    public function test_the_whole_range_is_blocked_for_other_bookings(): void
    {
        $this->bookDays();

        // Een los uur midden in de periode kan niet meer.
        $this->assertFalse($this->room->fresh()->isBookableFor($this->monday()->addDay(), 10, 12));

        // En een tweede meerdaagse boeking die erover valt ook niet.
        $this->bookDays(
            $this->monday()->addDays(2)->toDateString(),
            $this->monday()->addDays(4)->toDateString()
        )->assertSessionHasErrors('slot');

        $this->assertSame(1, $this->room->bookings()->count());
    }

    public function test_an_hourly_booking_blocks_a_multi_day_request(): void
    {
        $this->actingAs($this->artist)->post('/studios/' . $this->room->slug . '/boeken', [
            'date' => $this->monday()->addDay()->toDateString(),
            'start' => 10,
            'hours' => 3,
            'terms' => '1',
        ])->assertRedirect();

        $this->bookDays()->assertSessionHasErrors('slot');

        $this->assertSame(1, $this->room->bookings()->count());
    }

    public function test_a_room_without_a_day_rate_cannot_be_booked_per_day(): void
    {
        $this->room->update(['day_rate_cents' => null]);

        $this->bookDays()->assertNotFound();

        $this->assertSame(0, $this->room->bookings()->count());
    }

    public function test_a_single_day_can_be_booked_on_the_day_rate(): void
    {
        $this->room->update(['min_days' => 1]);

        $this->bookDays($this->monday()->toDateString(), $this->monday()->toDateString())
            ->assertRedirect();

        $booking = $this->room->bookings()->firstOrFail();

        $this->assertTrue($booking->isMultiDay());
        $this->assertSame(1, $booking->dayCount());
        $this->assertSame(40000, (int) $booking->rent_cents);
    }

    public function test_the_minimum_number_of_days_is_enforced(): void
    {
        $this->room->update(['min_days' => 3]);

        $this->bookDays($this->monday()->toDateString(), $this->monday()->addDay()->toDateString())
            ->assertSessionHasErrors('slot');

        $this->assertSame(0, $this->room->bookings()->count());
    }

    public function test_free_whole_days_leaves_out_booked_days(): void
    {
        $this->bookDays();

        $free = $this->room->fresh()->freeWholeDays(30);

        $this->assertNotContains($this->monday()->toDateString(), $free);
        $this->assertNotContains($this->monday()->addDay()->toDateString(), $free);
        $this->assertNotContains($this->monday()->addDays(2)->toDateString(), $free);
        $this->assertContains($this->monday()->addDays(3)->toDateString(), $free);
    }

    public function test_the_agenda_shows_every_day_of_a_multi_day_booking(): void
    {
        $this->bookDays();
        $this->room->bookings()->update(['status' => 'confirmed']);

        $response = $this->actingAs($this->host)->get(route('host.agenda'))->assertOk();

        foreach ([0, 1, 2] as $offset) {
            $response->assertSee($this->monday()->addDays($offset)->translatedFormat('j F'), escape: false);
        }
    }
}
