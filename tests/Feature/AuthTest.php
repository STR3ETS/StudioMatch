<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_as_artist_asks_to_verify_the_email_first(): void
    {
        $response = $this->post('/registreren', [
            'role' => 'artiest',
            'first_name' => 'Test',
            'last_name' => 'Artiest',
            'email' => 'artiest@example.com',
            'password' => 'Wachtwoord123', 'password_confirmation' => 'Wachtwoord123',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();
        $this->assertSame('artiest', User::first()->role->value);
    }

    public function test_registration_as_host_asks_to_verify_the_email_first(): void
    {
        $response = $this->post('/registreren', [
            'role' => 'verhuurder',
            'first_name' => 'Test',
            'last_name' => 'Verhuurder',
            'email' => 'verhuurder@example.com',
            'password' => 'Wachtwoord123', 'password_confirmation' => 'Wachtwoord123',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();
        $this->assertSame('verhuurder', User::first()->role->value);
    }

    public function test_the_header_offers_login_and_register_as_separate_buttons(): void
    {
        // Feedback: op mobiel was er één "Log in / Registreer"-knop die naar registreren ging.
        $html = $this->get('/')->assertOk()->getContent();
        $header = substr($html, 0, strpos($html, 'data-mobile-menu'));

        $this->assertStringContainsString('href="' . route('login') . '"', $header);
        $this->assertStringContainsString('href="' . route('register') . '"', $header);
        $this->assertStringNotContainsString('Log in / Registreer', $html);

        // De inlogknop mag niet meer verborgen zijn op kleine schermen.
        $this->assertDoesNotMatchRegularExpression('/href="' . preg_quote(route('login'), '/') . '" class="hidden/', $header);
    }

    public function test_the_dashboard_logo_on_mobile_leads_to_the_website(): void
    {
        $user = User::factory()->create(['role' => 'artiest']);

        $html = $this->actingAs($user)->get('/dashboard/artiest')->assertOk()->getContent();
        $topbar = substr($html, strpos($html, '<header'));

        $this->assertMatchesRegularExpression('/<a href="' . preg_quote(route('home'), '/') . '"[^>]*><img src="\/logos\/sm-primary-logo-blauw/', $topbar);
    }

    public function test_the_consent_checkbox_links_to_the_documents_you_agree_to(): void
    {
        // Je kunt niet akkoord gaan met iets dat je niet kunt lezen; de links stonden op '#'.
        $html = $this->get('/registreren')->assertOk()->getContent();

        $this->assertStringContainsString('href="' . route('legal.terms') . '"', $html);
        $this->assertStringContainsString('href="' . route('legal.privacy') . '"', $html);
    }

    public function test_the_public_pages_have_no_dead_links_left(): void
    {
        foreach (['/', '/registreren', '/studios', '/contact'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            $this->assertSame(0, substr_count($html, 'href="#"'), $path . ' heeft nog een link die nergens heen gaat.');
        }
    }

    public function test_the_social_media_icons_link_to_the_real_accounts(): void
    {
        foreach (['/', '/contact'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            foreach (config('studio.socials') as $social) {
                $this->assertStringContainsString('href="' . $social['url'] . '" target="_blank"', $html, $path . ' mist ' . $social['label']);
            }
        }
    }

    public function test_registration_requires_terms_and_valid_role(): void
    {
        $response = $this->post('/registreren', [
            'role' => 'admin',
            'first_name' => 'Test',
            'last_name' => 'Gebruiker',
            'email' => 'test@example.com',
            'password' => 'Wachtwoord123', 'password_confirmation' => 'Wachtwoord123',
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
