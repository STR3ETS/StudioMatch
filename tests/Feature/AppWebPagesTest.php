<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AppMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Websitepagina's die in de iOS/Android-app openstaan: geen eigen navigatie van de website (kop, onderbalk,
 * footer, cookiemelding), wel één balk terug naar de app. Buiten de app verandert er niets.
 */
class AppWebPagesTest extends TestCase
{
    use RefreshDatabase;

    private const APP = 'Mozilla/5.0 (Linux; Android 16; SM-A536B; wv) AppleWebKit/537.36 Chrome/145.0 Mobile Safari/537.36 StudioMatchApp';

    private const IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 19_0 like Mac OS X) AppleWebKit/605.1.15 StudioMatchApp';

    public function test_the_website_keeps_its_own_navigation_outside_the_app(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-header', false)
            ->assertSee('data-cookie-banner', false)
            ->assertDontSee('data-app-return', false)
            ->assertDontSee('class="in-app"', false);
    }

    public function test_in_the_app_a_page_has_one_bar_back_instead_of_the_website_navigation(): void
    {
        $this->withHeader('User-Agent', self::APP)
            ->withSession(['app_bundle' => 'https://localhost'])
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-app-return', false)
            ->assertSee('class="in-app"', false)
            ->assertSee('href="https://localhost/"', false)
            ->assertDontSee('data-header', false)
            ->assertDontSee('data-cookie-banner', false);
    }

    public function test_a_dashboard_page_in_the_app_shows_its_title_and_no_second_bottom_bar(): void
    {
        $artist = User::factory()->create(['role' => 'artiest']);

        $this->actingAs($artist)
            ->withHeader('User-Agent', self::APP)
            ->withSession(['app_bundle' => 'https://localhost'])
            ->get(route('artist.invoices.index'))
            ->assertOk()
            ->assertSee('data-app-return', false)
            ->assertSee('>Facturen</p>', false)
            ->assertDontSee('<button type="button" data-sidebar-open', false);
    }

    public function test_a_dashboard_page_outside_the_app_keeps_its_bottom_bar(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'artiest']))
            ->get(route('artist.invoices.index'))
            ->assertOk()
            ->assertSee('<button type="button" data-sidebar-open', false)
            ->assertDontSee('data-app-return', false)
            ->assertDontSee('data-app-tabbar', false);
    }

    public function test_in_the_app_a_page_shows_the_tabs_of_the_app_with_its_own_tab_lit(): void
    {
        $host = User::factory()->create(['role' => 'verhuurder']);

        $response = $this->actingAs($host)
            ->withHeader('User-Agent', self::APP)
            ->withSession(['app_bundle' => 'https://localhost'])
            ->get(route('host.invoices.index'))
            ->assertOk()
            ->assertSee('data-app-tabbar', false)
            ->assertSee('href="https://localhost/#/inbox"', false)
            ->assertSee('href="https://localhost/#/agenda"', false);

        // Facturen staat onder Account in de app, dus die tab licht op.
        $this->assertMatchesRegularExpression('/#\/account"\s+class="[^"]*text-ruby-red/', $response->getContent());
    }

    public function test_each_website_page_belongs_to_a_tab(): void
    {
        $artist = User::factory()->make(['role' => 'artiest']);
        $host = User::factory()->make(['role' => 'verhuurder']);

        $this->assertSame('studios', AppMenu::tabFor($artist, 'studios.show'));
        $this->assertSame('boekingen', AppMenu::tabFor($artist, 'bookings.reschedule'));
        $this->assertSame('account', AppMenu::tabFor($artist, 'artist.invoices.index'));

        $admin = User::factory()->make(['role' => 'admin']);
        $this->assertSame('wachtrij', AppMenu::tabFor($admin, 'admin.queue.show'));
        $this->assertSame('admin-boekingen', AppMenu::tabFor($admin, 'admin.bookings.index'));
        $this->assertSame('tickets', AppMenu::tabFor($admin, 'admin.tickets.index'));
        $this->assertSame('account', AppMenu::tabFor($admin, 'admin.users.index'));
        $this->assertSame('inbox', AppMenu::tabFor($host, 'host.bookings.index'));
        $this->assertSame('agenda', AppMenu::tabFor($host, 'host.agenda'));
        $this->assertSame('account', AppMenu::tabFor($host, 'host.studios.index'));
    }

    public function test_an_error_page_in_the_app_leads_back_to_the_app(): void
    {
        $this->withHeader('User-Agent', self::APP)
            ->get('/bestaat-niet')
            ->assertNotFound()
            ->assertSee('Terug naar de app')
            ->assertSee(route('app.back'), false)
            ->assertDontSee('data-header', false);
    }

    public function test_the_way_back_uses_the_app_address_from_the_session(): void
    {
        $this->withSession(['app_bundle' => 'https://localhost'])->get(route('app.back'))->assertOk()->assertSee('location.replace("https://localhost/")', false);
    }

    public function test_without_a_session_the_way_back_follows_the_phone(): void
    {
        $this->withHeader('User-Agent', self::IPHONE)->get(route('app.back'))->assertOk()->assertSee('location.replace("capacitor://localhost/")', false);
        // Android over http (een testserver): de app draait dan ook op http://localhost.
        $this->withHeader('User-Agent', self::APP)->get(route('app.back'))->assertOk()->assertSee('location.replace("http://localhost/")', false);
    }

    public function test_the_way_back_only_goes_to_the_app_itself(): void
    {
        $this->withHeader('User-Agent', self::APP)
            ->withSession(['app_bundle' => 'https://evil.example'])
            ->get(route('app.back'))
            ->assertOk()->assertSee('location.replace("http://localhost/")', false);
    }
}
