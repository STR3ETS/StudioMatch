<?php

namespace Tests\Feature;

use App\Enums\RoomStatus;
use App\Models\Room;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HostAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private function hostWithRoom(): array
    {
        $host = User::factory()->create(['role' => 'verhuurder']);

        $studio = $host->studios()->create([
            'name' => 'Test Studio',
            'street' => 'Teststraat 1',
            'postal_code' => '1234 AB',
            'city' => 'Amsterdam',
        ]);

        $room = $studio->rooms()->create([
            'title' => 'Live room',
            'description' => 'Een fijne ruimte.',
            'type' => 'opname',
            'hourly_rate_cents' => 4500,
            'min_hours' => 2,
            'capacity' => 6,
        ]);

        return [$host, $room];
    }

    private function scheduleDays(array $overrides = []): array
    {
        $days = [];
        for ($weekday = 1; $weekday <= 7; $weekday++) {
            $days[$weekday] = array_merge(
                ['is_open' => '1', 'open_hour' => 9, 'close_hour' => 21],
                $overrides[$weekday] ?? [],
            );
        }

        return $days;
    }

    public function test_edit_page_seeds_default_weekly_schedule(): void
    {
        [$host, $room] = $this->hostWithRoom();

        $this->actingAs($host)
            ->get('/dashboard/verhuurder/beschikbaarheid/' . $room->id)
            ->assertOk();

        $this->assertSame(7, $room->hours()->count());
        $this->assertTrue($room->hours()->where('weekday', 1)->first()->is_open);
        $this->assertFalse($room->hours()->where('weekday', 7)->first()->is_open);
    }

    public function test_host_can_update_weekly_schedule(): void
    {
        [$host, $room] = $this->hostWithRoom();
        $room->seedDefaultHours();

        $response = $this->actingAs($host)->put('/dashboard/verhuurder/beschikbaarheid/' . $room->id . '/schema', [
            'days' => $this->scheduleDays([
                6 => ['is_open' => '1', 'open_hour' => 10, 'close_hour' => 18],
                7 => ['is_open' => '', 'open_hour' => 9, 'close_hour' => 21],
            ]),
        ]);

        $response->assertRedirect(route('host.availability.edit', $room));
        $saturday = $room->hours()->where('weekday', 6)->first();
        $this->assertTrue($saturday->is_open);
        $this->assertSame(10, (int) $saturday->open_hour);
        $this->assertSame(18, (int) $saturday->close_hour);
        $this->assertFalse($room->hours()->where('weekday', 7)->first()->is_open);
    }

    public function test_closing_time_must_be_after_opening_time(): void
    {
        [$host, $room] = $this->hostWithRoom();
        $room->seedDefaultHours();

        $response = $this->actingAs($host)->put('/dashboard/verhuurder/beschikbaarheid/' . $room->id . '/schema', [
            'days' => $this->scheduleDays([
                2 => ['open_hour' => 20, 'close_hour' => 10],
            ]),
        ]);

        $response->assertSessionHasErrors('days.2.close_hour');
    }

    public function test_host_can_add_block_exception(): void
    {
        [$host, $room] = $this->hostWithRoom();

        $this->actingAs($host)->post('/dashboard/verhuurder/beschikbaarheid/' . $room->id . '/uitzonderingen', [
            'date' => today()->addDays(3)->toDateString(),
            'type' => 'block',
            'start_hour' => 14,
            'end_hour' => 17,
            'label' => 'Eigen sessie',
        ]);

        $this->assertDatabaseHas('room_exceptions', [
            'room_id' => $room->id,
            'type' => 'block',
            'start_hour' => 14,
            'end_hour' => 17,
        ]);
    }

    public function test_closed_exception_needs_no_hours(): void
    {
        [$host, $room] = $this->hostWithRoom();

        $this->actingAs($host)->post('/dashboard/verhuurder/beschikbaarheid/' . $room->id . '/uitzonderingen', [
            'date' => today()->addDay()->toDateString(),
            'type' => 'closed',
        ]);

        $this->assertDatabaseHas('room_exceptions', ['room_id' => $room->id, 'type' => 'closed', 'start_hour' => null]);
    }

    public function test_open_exception_requires_hours(): void
    {
        [$host, $room] = $this->hostWithRoom();

        $response = $this->actingAs($host)->post('/dashboard/verhuurder/beschikbaarheid/' . $room->id . '/uitzonderingen', [
            'date' => today()->addDay()->toDateString(),
            'type' => 'open',
        ]);

        $response->assertSessionHasErrors(['start_hour', 'end_hour']);
    }

    public function test_exception_date_cannot_be_in_the_past(): void
    {
        [$host, $room] = $this->hostWithRoom();

        $response = $this->actingAs($host)->post('/dashboard/verhuurder/beschikbaarheid/' . $room->id . '/uitzonderingen', [
            'date' => today()->subDay()->toDateString(),
            'type' => 'closed',
        ]);

        $response->assertSessionHasErrors('date');
    }

    public function test_host_can_delete_exception(): void
    {
        [$host, $room] = $this->hostWithRoom();
        $exception = $room->exceptions()->create(['date' => today()->addDay(), 'type' => 'closed']);

        $this->actingAs($host)->delete('/dashboard/verhuurder/beschikbaarheid/' . $room->id . '/uitzonderingen/' . $exception->id);

        $this->assertDatabaseCount('room_exceptions', 0);
    }

    public function test_vacation_mode_toggles_and_shows_effective_status(): void
    {
        [$host, $room] = $this->hostWithRoom();
        $room->update(['status' => RoomStatus::Live]);

        $this->actingAs($host)->put('/dashboard/verhuurder/beschikbaarheid/' . $room->id . '/vakantie', [
            'on_vacation' => '1',
            'vacation_until' => today()->addWeek()->toDateString(),
        ]);

        $room->refresh();
        $this->assertTrue($room->on_vacation);
        $this->assertSame(RoomStatus::Vakantie, $room->effectiveStatus());

        $this->actingAs($host)->put('/dashboard/verhuurder/beschikbaarheid/' . $room->id . '/vakantie', []);

        $room->refresh();
        $this->assertFalse($room->on_vacation);
        $this->assertNull($room->vacation_until);
        $this->assertSame(RoomStatus::Live, $room->effectiveStatus());
    }

    public function test_expired_vacation_shows_live_again(): void
    {
        [$host, $room] = $this->hostWithRoom();
        $room->update([
            'status' => RoomStatus::Live,
            'on_vacation' => true,
            'vacation_until' => today()->subDay(),
        ]);

        $this->assertSame(RoomStatus::Live, $room->fresh()->effectiveStatus());
    }

    public function test_host_cannot_manage_another_hosts_availability(): void
    {
        [, $room] = $this->hostWithRoom();
        $other = User::factory()->create(['role' => 'verhuurder']);

        $this->actingAs($other)
            ->get('/dashboard/verhuurder/beschikbaarheid/' . $room->id)
            ->assertForbidden();
    }

    public function test_new_rooms_get_default_schedule_on_creation(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        [$host, $existingRoom] = $this->hostWithRoom();

        $this->actingAs($host)->post('/dashboard/verhuurder/studios/' . $existingRoom->studio_id . '/ruimtes', [
            'title' => 'Studio B',
            'description' => 'Tweede ruimte.',
            'type' => 'mix',
            'hourly_rate' => '30',
            'engineer_option' => 'none',
            'min_hours' => 2,
            'capacity' => 4,
            'photos' => collect(range(1, 5))->map(fn ($i) => \Illuminate\Http\UploadedFile::fake()->image("room{$i}.jpg"))->all(),
        ]);

        $room = Room::where('title', 'Studio B')->first();
        $this->assertSame(7, $room->hours()->count());
    }
}
