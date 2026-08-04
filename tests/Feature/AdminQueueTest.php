<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use App\Notifications\RoomApproved;
use App\Notifications\RoomRejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminQueueTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function roomInReview(): Room
    {
        $host = User::factory()->create(['role' => 'verhuurder']);

        $studio = $host->studios()->create([
            'name' => 'Test Studio',
            'street' => 'Teststraat 1',
            'postal_code' => '1234 AB',
            'city' => 'Amsterdam',
        ]);

        return $studio->rooms()->create([
            'title' => 'Live room',
            'description' => 'Een fijne ruimte.',
            'type' => 'opname',
            'hourly_rate_cents' => 4500,
            'min_hours' => 2,
            'capacity' => 6,
            'status' => 'in_review',
        ]);
    }

    public function test_admin_lands_on_admin_dashboard_after_login(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('dashboard.admin'));
    }

    public function test_overview_shows_platform_stats_and_queue_count(): void
    {
        $this->roomInReview();

        $this->actingAs($this->admin())->get('/dashboard/admin')
            ->assertOk()
            ->assertSee(__('admin.queue.title'))
            ->assertSee('1 ruimte wacht op beoordeling');
    }

    public function test_queue_shows_rooms_in_review(): void
    {
        $room = $this->roomInReview();

        $this->actingAs($this->admin())->get('/dashboard/admin/wachtrij')
            ->assertOk()
            ->assertSee('Live room')
            ->assertSee('Test Studio');
    }

    public function test_admin_can_approve_room(): void
    {
        Notification::fake();
        $room = $this->roomInReview();

        $response = $this->actingAs($this->admin())->patch('/dashboard/admin/wachtrij/' . $room->id . '/goedkeuren');

        $response->assertRedirect(route('admin.queue.index'));
        $this->assertSame('live', $room->fresh()->status->value);
        Notification::assertSentTo($room->studio->user, RoomApproved::class);
    }

    public function test_admin_can_reject_room_with_reason(): void
    {
        Notification::fake();
        $room = $this->roomInReview();

        $this->actingAs($this->admin())->patch('/dashboard/admin/wachtrij/' . $room->id . '/afwijzen', [
            'rejection_reason' => 'De foto\'s zijn te donker.',
        ]);

        $room->refresh();
        $this->assertSame('afgekeurd', $room->status->value);
        $this->assertSame('De foto\'s zijn te donker.', $room->rejection_reason);
        Notification::assertSentTo($room->studio->user, RoomRejected::class);
    }

    public function test_rejecting_requires_a_reason(): void
    {
        Notification::fake();
        $room = $this->roomInReview();

        $response = $this->actingAs($this->admin())->patch('/dashboard/admin/wachtrij/' . $room->id . '/afwijzen');

        $response->assertSessionHasErrors('rejection_reason');
        $this->assertSame('in_review', $room->fresh()->status->value);
        Notification::assertNothingSent();
    }

    public function test_host_sees_rejection_reason_and_resubmit_clears_it(): void
    {
        $room = $this->roomInReview();
        $room->update(['status' => 'afgekeurd', 'rejection_reason' => 'Omschrijving te summier.']);
        $host = $room->studio->user;

        $this->actingAs($host)->get('/dashboard/verhuurder/ruimtes/' . $room->id . '/bewerken')
            ->assertOk()
            ->assertSee('Omschrijving te summier.');

        $this->actingAs($host)->put('/dashboard/verhuurder/ruimtes/' . $room->id, [
            'title' => 'Live room',
            'description' => 'Een veel uitgebreidere omschrijving van de ruimte.',
            'type' => 'opname',
            'hourly_rate' => '45',
            'min_hours' => 2,
            'capacity' => 6,
        ]);

        $room->refresh();
        $this->assertSame('in_review', $room->status->value);
        $this->assertNull($room->rejection_reason);
    }

    public function test_live_room_is_not_in_queue_and_cannot_be_approved_again(): void
    {
        Notification::fake();
        $room = $this->roomInReview();
        $room->update(['status' => 'live']);

        $this->actingAs($this->admin())->get('/dashboard/admin/wachtrij')->assertDontSee('Live room');
        $this->actingAs($this->admin())->patch('/dashboard/admin/wachtrij/' . $room->id . '/goedkeuren')->assertNotFound();
    }

    public function test_non_admins_cannot_access_the_queue(): void
    {
        $host = User::factory()->create(['role' => 'verhuurder']);
        $artist = User::factory()->create(['role' => 'artiest']);

        $this->actingAs($host)->get('/dashboard/admin')->assertRedirect(route('dashboard.host'));
        $this->actingAs($artist)->get('/dashboard/admin')->assertRedirect(route('dashboard.artist'));
    }
}
