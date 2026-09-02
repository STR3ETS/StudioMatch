<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use App\Support\Hours;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NightBookingTest extends TestCase
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

        // Elke dag open van 20:00 tot 04:00 de volgende ochtend.
        for ($weekday = 1; $weekday <= 7; $weekday++) {
            $this->room->hours()->create([
                'weekday' => $weekday,
                'is_open' => true,
                'open_hour' => 20,
                'close_hour' => 28,
            ]);
        }

        $this->room->refresh();
    }

    private function nextWednesday(): string
    {
        return today()->next(Carbon::WEDNESDAY)->toDateString();
    }

    private function book(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->artist)->post('/studios/' . $this->room->slug . '/boeken', array_merge([
            'date' => $this->nextWednesday(),
            'start' => 22,
            'hours' => 4,
            'terms' => '1',
        ], $overrides));
    }

    public function test_a_session_may_run_past_midnight(): void
    {
        $this->book()->assertRedirect();

        $booking = $this->room->bookings()->firstOrFail();
        $this->assertSame(22, (int) $booking->start_hour);
        $this->assertSame(26, (int) $booking->end_hour);

        // 26 uur na middernacht is de volgende dag om 02:00.
        $this->assertSame(
            today()->next(Carbon::WEDNESDAY)->addDay()->setTime(2, 0)->toDateTimeString(),
            $booking->endsAt()->toDateTimeString()
        );
    }

    public function test_the_time_range_shows_the_next_day(): void
    {
        $this->book();

        $this->assertSame('22:00 – 02:00 (+1)', $this->room->bookings()->firstOrFail()->timeRange());
        $this->assertTrue($this->room->bookings()->firstOrFail()->runsPastMidnight());
    }

    public function test_a_night_session_blocks_the_early_hours_of_the_next_day(): void
    {
        $this->book();

        // 01:00 tot 03:00 op de volgende ochtend overlapt met de lopende sessie.
        $this->assertFalse($this->room->fresh()->isBookableFor(
            Carbon::parse($this->nextWednesday())->addDay(),
            1,
            3
        ));

        // De avond erna valt binnen het rooster en is nog vrij.
        $this->assertTrue($this->room->fresh()->isBookableFor(
            Carbon::parse($this->nextWednesday())->addDay(),
            21,
            23
        ));
    }

    public function test_free_hours_exclude_what_the_previous_night_takes(): void
    {
        $this->book();

        $free = $this->room->fresh()->freeHoursByDate();
        $nextMorning = today()->next(Carbon::WEDNESDAY)->addDay()->toDateString();

        $this->assertNotContains(22, $free[$this->nextWednesday()]);
        $this->assertNotContains(25, $free[$this->nextWednesday()]);
        $this->assertNotContains(1, $free[$nextMorning]);
        $this->assertContains(20, $free[$nextMorning]);
    }

    public function test_a_session_cannot_run_past_the_configured_limit(): void
    {
        $this->book(['start' => 23, 'hours' => 12])->assertSessionHasErrors('slot');

        $this->assertSame(0, $this->room->bookings()->count());
    }

    public function test_a_session_outside_the_opening_window_is_refused(): void
    {
        $this->book(['start' => 10, 'hours' => 4])->assertSessionHasErrors('slot');

        $this->assertSame(0, $this->room->bookings()->count());
    }

    public function test_hosts_can_close_after_midnight(): void
    {
        $days = [];
        foreach ($this->room->hours as $day) {
            $days[$day->weekday] = ['is_open' => 1, 'open_hour' => 20, 'close_hour' => 28];
        }

        $this->actingAs($this->host)
            ->put('/dashboard/verhuurder/beschikbaarheid/' . $this->room->id . '/schema', ['days' => $days])
            ->assertSessionHasNoErrors();

        $this->assertSame(28, (int) $this->room->hours()->where('weekday', 1)->value('close_hour'));
    }

    public function test_the_hour_notation_rolls_over(): void
    {
        $this->assertSame('22:00', Hours::clock(22));
        $this->assertSame('24:00', Hours::clock(24));
        $this->assertSame('01:00', Hours::clock(25));
        $this->assertSame('06:00', Hours::clock(30));
        $this->assertSame('20:00 – 24:00', Hours::range(20, 24));
        $this->assertStringContainsString('(+1)', Hours::range(20, 26));
    }
}
