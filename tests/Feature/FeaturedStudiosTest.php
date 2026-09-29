<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * De uitgelichte studio's op de homepage mengen populair met nieuw: studio's met meer
 * afgeronde boekingen komen hoger, maar nieuwe aanmeldingen houden altijd hun plek.
 */
class FeaturedStudiosTest extends TestCase
{
    use RefreshDatabase;

    private User $host;

    private User $artist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->host = User::factory()->create(['role' => 'verhuurder']);
        $this->artist = User::factory()->create(['role' => 'artiest']);
    }

    private function room(string $title, int $completedBookings = 0): Room
    {
        $studio = $this->host->studios()->create([
            'name' => $title . ' Studio',
            'street' => 'Teststraat 1',
            'postal_code' => '1234 AB',
            'city' => 'Amsterdam',
        ]);

        $room = $studio->rooms()->create([
            'title' => $title,
            'description' => 'Een fijne ruimte.',
            'type' => 'opname',
            'hourly_rate_cents' => 5000,
            'min_hours' => 2,
            'capacity' => 4,
            'status' => 'live',
        ]);

        for ($i = 0; $i < $completedBookings; $i++) {
            $room->bookings()->create([
                'user_id' => $this->artist->id,
                'date' => today()->subDays(10 + $i),
                'start_hour' => 10,
                'end_hour' => 12,
                'hourly_rate_cents' => 5000,
                'rent_cents' => 10000,
                'service_fee_cents' => 900,
                'vat_cents' => 189,
                'total_cents' => 11089,
                'status' => 'completed',
                'terms_accepted_at' => now(),
                'requested_at' => now(),
            ]);
        }

        return $room;
    }

    public function test_without_any_completed_bookings_the_newest_rooms_are_featured(): void
    {
        $oldest = $this->room('Oudste');
        $newest = $this->room('Nieuwste');

        $featured = Room::featured();

        $this->assertSame([$newest->id, $oldest->id], $featured->pluck('id')->all());
    }

    public function test_rooms_with_more_completed_bookings_come_first(): void
    {
        $quiet = $this->room('Rustig');
        $busy = $this->room('Druk', completedBookings: 3);
        $medium = $this->room('Gemiddeld', completedBookings: 1);

        $order = Room::featured()->pluck('title')->all();

        $this->assertSame(['Druk', 'Gemiddeld'], array_slice($order, 0, 2));
        $this->assertContains('Rustig', $order);
        $this->assertSame($quiet->id, Room::featured()->last()->id);
        $this->assertSame($busy->id, Room::featured()->first()->id);
        $this->assertNotNull($medium);
    }

    public function test_new_rooms_keep_their_place_next_to_the_popular_ones(): void
    {
        // Vijf drukke ruimtes, maar hoogstens vier mogen de populaire plekken innemen.
        foreach (range(1, 5) as $i) {
            $this->room('Druk ' . $i, completedBookings: $i);
        }

        $brandNew = $this->room('Splinternieuw');

        $featured = Room::featured();
        $titles = $featured->pluck('title')->all();

        $this->assertCount(6, $featured);
        $this->assertSame('Splinternieuw', $titles[4], 'Een nieuwe aanmelding hoort direct na de vier populairste te staan.');
        $this->assertContains('Splinternieuw', $titles);
    }

    public function test_only_completed_bookings_count_as_popularity(): void
    {
        $cancelled = $this->room('Geannuleerd');
        $cancelled->bookings()->create([
            'user_id' => $this->artist->id,
            'date' => today()->subDay(),
            'start_hour' => 10,
            'end_hour' => 12,
            'hourly_rate_cents' => 5000,
            'rent_cents' => 10000,
            'service_fee_cents' => 900,
            'vat_cents' => 189,
            'total_cents' => 11089,
            'status' => 'cancelled',
            'terms_accepted_at' => now(),
            'requested_at' => now(),
        ]);

        $completed = $this->room('Afgerond', completedBookings: 1);

        $this->assertSame($completed->id, Room::featured()->first()->id);
    }

    public function test_rooms_that_are_not_live_are_never_featured(): void
    {
        $this->room('Zichtbaar', completedBookings: 2);
        $hidden = $this->room('Verborgen', completedBookings: 5);
        $hidden->update(['status' => 'concept']);

        $titles = Room::featured()->pluck('title')->all();

        $this->assertContains('Zichtbaar', $titles);
        $this->assertNotContains('Verborgen', $titles);
    }

    public function test_the_homepage_shows_the_featured_rooms(): void
    {
        $this->room('Druk', completedBookings: 2);
        $this->room('Nieuw');

        $this->get('/')->assertOk()->assertSee('Druk')->assertSee('Nieuw');
    }
}
