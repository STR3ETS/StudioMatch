<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HostDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function host(): User
    {
        return User::factory()->create(['role' => 'verhuurder']);
    }

    private function studioFor(User $user, array $attributes = []): Studio
    {
        return $user->studios()->create(array_merge([
            'name' => 'Test Studio',
            'street' => 'Teststraat 1',
            'postal_code' => '1234 AB',
            'city' => 'Amsterdam',
        ], $attributes));
    }

    private function roomFor(Studio $studio, array $attributes = []): Room
    {
        return $studio->rooms()->create(array_merge([
            'title' => 'Live room',
            'description' => 'Een fijne ruimte.',
            'type' => 'opname',
            'hourly_rate_cents' => 4500,
            'min_hours' => 2,
            'capacity' => 6,
        ], $attributes));
    }

    public function test_host_can_save_business_profile(): void
    {
        $host = $this->host();

        $response = $this->actingAs($host)->put('/dashboard/verhuurder/bedrijfsgegevens', [
            'name' => 'Redlight Recordings B.V.',
            'phone' => '020 1234567',
            'owner_type' => 'ondernemer',
            'btw_plichtig' => '1',
            'kvk_number' => '12345678',
            'vat_number' => 'NL123456789B01',
        ]);

        $response->assertRedirect(route('host.profile.edit'));
        $this->assertDatabaseHas('host_profiles', [
            'user_id' => $host->id,
            'name' => 'Redlight Recordings B.V.',
            'owner_type' => 'ondernemer',
        ]);
    }

    public function test_kvk_is_required_for_business_owners(): void
    {
        $host = $this->host();

        $response = $this->actingAs($host)->put('/dashboard/verhuurder/bedrijfsgegevens', [
            'name' => 'Test',
            'phone' => '0612345678',
            'owner_type' => 'ondernemer',
            'btw_plichtig' => '0',
        ]);

        $response->assertSessionHasErrors('kvk_number');
    }

    public function test_host_can_create_multiple_studios_including_same_address(): void
    {
        Http::fake();
        $host = $this->host();

        foreach (['Redlight A', 'Redlight B'] as $name) {
            $this->actingAs($host)->post('/dashboard/verhuurder/studios', [
                'name' => $name,
                'street' => 'Prinsengracht 263',
                'postal_code' => '1016 GV',
                'city' => 'Amsterdam',
            ])->assertRedirect();
        }

        $this->actingAs($host)->post('/dashboard/verhuurder/studios', [
            'name' => 'Redlight Zuid',
            'street' => 'Van Woustraat 12',
            'postal_code' => '1073 LL',
            'city' => 'Amsterdam',
        ]);

        $this->assertSame(3, $host->studios()->count());
        $this->assertSame(2, $host->studios()->where('street', 'Prinsengracht 263')->count());
    }

    public function test_studio_address_is_geocoded_on_save(): void
    {
        Http::fake([
            'api.pdok.nl/*' => Http::response([
                'response' => ['docs' => [['centroide_ll' => 'POINT(5.92976488 51.98858137)']]],
            ]),
        ]);

        $host = $this->host();

        $this->actingAs($host)->post('/dashboard/verhuurder/studios', [
            'name' => 'Badman Studio',
            'street' => 'Velperweg 49-103',
            'postal_code' => '6824 BG',
            'city' => 'Arnhem',
        ]);

        $studio = $host->studios()->first();
        $this->assertEqualsWithDelta(51.98858137, $studio->lat, 0.0001);
        $this->assertEqualsWithDelta(5.92976488, $studio->lng, 0.0001);
    }

    public function test_host_can_create_room_within_studio(): void
    {
        Storage::fake('public');
        $host = $this->host();
        $studio = $this->studioFor($host);

        $response = $this->actingAs($host)->post('/dashboard/verhuurder/studios/' . $studio->id . '/ruimtes', [
            'title' => 'Studio A',
            'description' => 'Ruime live room met akoestische behandeling.',
            'type' => 'opname',
            'hourly_rate' => '45.50',
            'min_hours' => 2,
            'capacity' => 6,
            'photos' => collect(range(1, 5))->map(fn ($i) => UploadedFile::fake()->image("room{$i}.jpg", 1200, 800))->all(),
        ]);

        $room = Room::first();
        $response->assertRedirect(route('host.rooms.edit', $room));
        $this->assertSame($studio->id, $room->studio_id);
        $this->assertSame(4550, $room->hourly_rate_cents);
        $this->assertSame('in_review', $room->status->value);
        $this->assertCount(5, $room->photos);
        Storage::disk('public')->assertExists($room->photos->first()->path);
    }

    public function test_host_cannot_add_room_to_another_hosts_studio(): void
    {
        $host = $this->host();
        $otherStudio = $this->studioFor($this->host());

        $this->actingAs($host)
            ->get('/dashboard/verhuurder/studios/' . $otherStudio->id . '/ruimtes/nieuw')
            ->assertForbidden();
    }

    public function test_host_cannot_view_or_edit_another_hosts_studio(): void
    {
        $host = $this->host();
        $otherStudio = $this->studioFor($this->host());

        $this->actingAs($host)->get('/dashboard/verhuurder/studios/' . $otherStudio->id)->assertForbidden();
        $this->actingAs($host)->delete('/dashboard/verhuurder/studios/' . $otherStudio->id)->assertForbidden();
    }

    public function test_host_cannot_edit_another_hosts_room(): void
    {
        $host = $this->host();
        $room = $this->roomFor($this->studioFor($this->host()));

        $this->actingAs($host)
            ->get('/dashboard/verhuurder/ruimtes/' . $room->id . '/bewerken')
            ->assertForbidden();
    }

    public function test_deleting_studio_removes_rooms_and_photo_files(): void
    {
        Storage::fake('public');
        $host = $this->host();
        $studio = $this->studioFor($host);
        $room = $this->roomFor($studio);
        $path = UploadedFile::fake()->image('a.jpg')->store('rooms/' . $room->id, 'public');
        $room->photos()->create(['path' => $path]);

        $this->actingAs($host)->delete('/dashboard/verhuurder/studios/' . $studio->id);

        $this->assertDatabaseCount('studios', 0);
        $this->assertDatabaseCount('rooms', 0);
        $this->assertDatabaseCount('room_photos', 0);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_room_creation_requires_at_least_five_photos(): void
    {
        Storage::fake('public');
        $host = $this->host();
        $studio = $this->studioFor($host);

        $response = $this->actingAs($host)->post('/dashboard/verhuurder/studios/' . $studio->id . '/ruimtes', [
            'title' => 'Studio A',
            'description' => 'Ruime live room.',
            'type' => 'opname',
            'hourly_rate' => '45',
            'min_hours' => 2,
            'capacity' => 6,
            'photos' => collect(range(1, 4))->map(fn ($i) => UploadedFile::fake()->image("room{$i}.jpg"))->all(),
        ]);

        $response->assertSessionHasErrors('photos');
        $this->assertDatabaseCount('rooms', 0);
    }

    public function test_photo_cannot_be_deleted_below_the_minimum_of_five(): void
    {
        Storage::fake('public');
        $host = $this->host();
        $room = $this->roomFor($this->studioFor($host));
        foreach (range(1, 5) as $i) {
            $room->photos()->create(['path' => "rooms/{$room->id}/foto{$i}.jpg", 'sort_order' => $i]);
        }

        $response = $this->actingAs($host)->delete('/dashboard/verhuurder/ruimtes/' . $room->id . '/fotos/' . $room->photos->first()->id);

        $response->assertSessionHasErrors('photos');
        $this->assertSame(5, $room->photos()->count());
    }

    public function test_updating_rejected_room_puts_it_back_in_review(): void
    {
        $host = $this->host();
        $room = $this->roomFor($this->studioFor($host), ['status' => 'afgekeurd']);

        $this->actingAs($host)->put('/dashboard/verhuurder/ruimtes/' . $room->id, [
            'title' => 'Live room (aangepast)',
            'description' => 'Een fijne ruimte.',
            'type' => 'opname',
            'hourly_rate' => '45',
            'min_hours' => 2,
            'capacity' => 6,
        ]);

        $this->assertSame('in_review', $room->fresh()->status->value);
    }

    public function test_artist_cannot_access_host_pages(): void
    {
        $artist = User::factory()->create(['role' => 'artiest']);

        $this->actingAs($artist)
            ->get('/dashboard/verhuurder/studios')
            ->assertRedirect(route('dashboard.artist'));
    }

    public function test_overview_checklist_reflects_progress(): void
    {
        $host = $this->host();

        $this->actingAs($host)->get('/dashboard/verhuurder')->assertOk()->assertSee('1 / 5');

        $host->hostProfile()->create([
            'name' => 'Test',
            'phone' => '0612345678',
            'owner_type' => 'particulier',
            'btw_plichtig' => false,
        ]);
        $this->studioFor($host);

        $this->actingAs($host->fresh())->get('/dashboard/verhuurder')->assertOk()->assertSee('3 / 5');
    }

    public function test_overview_shows_room_status_updates(): void
    {
        $host = $this->host();
        $studio = $this->studioFor($host, ['name' => 'Studio Noord']);
        $this->roomFor($studio, ['title' => 'Live room', 'status' => 'afgekeurd', 'rejection_reason' => 'De foto\'s zijn te donker.']);
        $this->roomFor($studio, ['title' => 'Mix room', 'status' => 'in_review']);

        $this->actingAs($host)->get('/dashboard/verhuurder')
            ->assertOk()
            ->assertSee(__('host.overview.status_title'))
            ->assertSee('afgekeurd')
            ->assertSee('De foto\'s zijn te donker.')
            ->assertSee('wacht op beoordeling');
    }

    public function test_overview_hides_status_section_when_all_rooms_are_live(): void
    {
        $host = $this->host();
        $this->roomFor($this->studioFor($host), ['status' => 'live']);

        $this->actingAs($host)->get('/dashboard/verhuurder')
            ->assertOk()
            ->assertDontSee(__('host.overview.status_title'));
    }

    public function test_studios_index_flags_studios_with_rejected_rooms(): void
    {
        $host = $this->host();
        $studioWithRejection = $this->studioFor($host, ['name' => 'Studio Noord']);
        $this->roomFor($studioWithRejection, ['status' => 'afgekeurd']);
        $this->studioFor($host, ['name' => 'Studio Zuid', 'street' => 'Zuidstraat 5']);

        $response = $this->actingAs($host)->get('/dashboard/verhuurder/studios');

        $response->assertOk()->assertSee(__('host.studios.action_needed'));
        $this->assertSame(1, substr_count($response->getContent(), __('host.studios.action_needed')));
    }

    public function test_studio_page_shows_its_rooms(): void
    {
        $host = $this->host();
        $studio = $this->studioFor($host, ['name' => 'Studio Noord']);
        $this->roomFor($studio, ['title' => 'Live room Noord']);

        $this->actingAs($host)->get('/dashboard/verhuurder/studios/' . $studio->id)
            ->assertOk()
            ->assertSee('Studio Noord')
            ->assertSee('Live room Noord');
    }

    public function test_deleting_room_redirects_to_its_studio(): void
    {
        $host = $this->host();
        $studio = $this->studioFor($host);
        $room = $this->roomFor($studio);

        $this->actingAs($host)
            ->delete('/dashboard/verhuurder/ruimtes/' . $room->id)
            ->assertRedirect(route('host.studios.show', $studio));

        $this->assertDatabaseCount('rooms', 0);
    }
}
