<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_page_renders_for_every_role(): void
    {
        foreach (['artiest', 'verhuurder', 'admin'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get('/dashboard/account')
                ->assertOk()
                ->assertSee(__('account.profile.title'))
                ->assertSee(__('account.password.title'))
                ->assertSee(__('account.delete.title'));
        }
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create(['role' => 'artiest']);

        $this->actingAs($user)->put('/dashboard/account/profiel', [
            'name' => 'Nieuwe Naam',
            'email' => 'nieuw@example.com',
        ])->assertRedirect(route('account.edit'));

        $user->refresh();
        $this->assertSame('Nieuwe Naam', $user->name);
        $this->assertSame('nieuw@example.com', $user->email);
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'bezet@example.com']);
        $user = User::factory()->create(['role' => 'artiest']);

        $this->actingAs($user)->put('/dashboard/account/profiel', [
            'name' => $user->name,
            'email' => 'bezet@example.com',
        ])->assertSessionHasErrors('email');
    }

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $user = User::factory()->create(['role' => 'verhuurder']);

        $this->actingAs($user)->put('/dashboard/account/wachtwoord', [
            'current_password' => 'password',
            'password' => 'NieuwWachtwoord123',
            'password_confirmation' => 'NieuwWachtwoord123',
        ])->assertRedirect(route('account.edit'));

        $this->assertTrue(Hash::check('NieuwWachtwoord123', $user->fresh()->password));
    }

    public function test_weak_password_gives_validation_error_not_server_error(): void
    {
        $user = User::factory()->create(['role' => 'verhuurder']);

        $this->actingAs($user)->from('/dashboard/account')->put('/dashboard/account/wachtwoord', [
            'current_password' => 'password',
            'password' => 'zwakwachtwoord',
            'password_confirmation' => 'zwakwachtwoord',
        ])->assertRedirect('/dashboard/account')->assertSessionHasErrors('password');
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'artiest']);

        $this->actingAs($user)->put('/dashboard/account/wachtwoord', [
            'current_password' => 'verkeerd',
            'password' => 'nieuwwachtwoord123',
            'password_confirmation' => 'nieuwwachtwoord123',
        ])->assertSessionHasErrors('current_password');
    }

    public function test_user_can_delete_account_with_password(): void
    {
        $user = User::factory()->create(['role' => 'artiest']);

        $this->actingAs($user)->delete('/dashboard/account', [
            'delete_password' => 'password',
        ])->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_deleting_account_requires_correct_password(): void
    {
        $user = User::factory()->create(['role' => 'artiest']);

        $this->actingAs($user)->delete('/dashboard/account', [
            'delete_password' => 'verkeerd',
        ])->assertSessionHasErrors('delete_password');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_deleting_host_account_removes_studios_and_rooms(): void
    {
        $host = User::factory()->create(['role' => 'verhuurder']);
        $studio = $host->studios()->create([
            'name' => 'Test Studio',
            'street' => 'Teststraat 1',
            'postal_code' => '1234 AB',
            'city' => 'Amsterdam',
        ]);
        $studio->rooms()->create([
            'title' => 'Live room',
            'description' => 'Een fijne ruimte.',
            'type' => 'opname',
            'hourly_rate_cents' => 4500,
            'min_hours' => 2,
            'capacity' => 6,
        ]);

        $this->actingAs($host)->delete('/dashboard/account', [
            'delete_password' => 'password',
        ]);

        $this->assertDatabaseCount('studios', 0);
        $this->assertDatabaseCount('rooms', 0);
    }
}
