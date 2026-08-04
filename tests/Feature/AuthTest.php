<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_as_artist_redirects_to_artist_dashboard(): void
    {
        $response = $this->post('/registreren', [
            'role' => 'artiest',
            'name' => 'Test Artiest',
            'email' => 'artiest@example.com',
            'password' => 'wachtwoord123',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('dashboard.artist'));
        $this->assertAuthenticated();
        $this->assertSame('artiest', User::first()->role->value);
    }

    public function test_registration_as_host_redirects_to_host_dashboard(): void
    {
        $response = $this->post('/registreren', [
            'role' => 'verhuurder',
            'name' => 'Test Verhuurder',
            'email' => 'verhuurder@example.com',
            'password' => 'wachtwoord123',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('dashboard.host'));
        $this->assertAuthenticated();
        $this->assertSame('verhuurder', User::first()->role->value);
    }

    public function test_registration_requires_terms_and_valid_role(): void
    {
        $response = $this->post('/registreren', [
            'role' => 'admin',
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'wachtwoord123',
        ]);

        $response->assertSessionHasErrors(['role', 'terms']);
        $this->assertGuest();
    }

    public function test_login_redirects_artist_to_artist_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'artiest']);

        $response = $this->post('/inloggen', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard.artist'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_redirects_host_to_host_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'verhuurder']);

        $response = $this->post('/inloggen', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard.host'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_wrong_password_fails(): void
    {
        $user = User::factory()->create();

        $response = $this->from('/inloggen')->post('/inloggen', [
            'email' => $user->email,
            'password' => 'verkeerd-wachtwoord',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_artist_cannot_view_host_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'artiest']);

        $this->actingAs($user)
            ->get('/dashboard/verhuurder')
            ->assertRedirect(route('dashboard.artist'));
    }

    public function test_host_cannot_view_artist_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'verhuurder']);

        $this->actingAs($user)
            ->get('/dashboard/artiest')
            ->assertRedirect(route('dashboard.host'));
    }

    public function test_dashboard_dispatches_by_role(): void
    {
        $host = User::factory()->create(['role' => 'verhuurder']);

        $this->actingAs($host)->get('/dashboard')->assertRedirect(route('dashboard.host'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_authenticated_user_cannot_view_login_page(): void
    {
        $user = User::factory()->create(['role' => 'artiest']);

        $this->actingAs($user)->get('/inloggen')->assertRedirect(route('dashboard'));
    }

    public function test_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/uitloggen')->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_dashboards_render_for_their_role(): void
    {
        $artist = User::factory()->create(['role' => 'artiest', 'name' => 'Anna Jansen']);
        $host = User::factory()->create(['role' => 'verhuurder', 'name' => 'Bram de Boer']);

        $this->actingAs($artist)->get('/dashboard/artiest')->assertOk()->assertSee('Anna');
        $this->actingAs($host)->get('/dashboard/verhuurder')->assertOk()->assertSee('Bram');
    }
}
