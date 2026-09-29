<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Een route om elke statuscode mee op te roepen, zodat we de echte
        // foutafhandeling van Laravel testen en niet alleen de losse view.
        Route::middleware('web')->get('/_test/fout/{code}', fn (int $code) => abort($code));
    }

    public static function fullPageCodes(): array
    {
        return ['401' => [401], '403' => [403], '404' => [404], '419' => [419], '429' => [429]];
    }

    public static function bareCodes(): array
    {
        return ['500' => [500], '503' => [503]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('fullPageCodes')]
    public function test_client_errors_get_a_branded_page_inside_the_site(int $code): void
    {
        $response = $this->get('/_test/fout/' . $code)->assertStatus($code);
        $html = $response->getContent();

        $response->assertSee(__('errors.' . $code . '.heading'))
            ->assertSee(__('errors.' . $code . '.text'));

        // Binnen de site, dus met menu en footer om verder te klikken.
        $this->assertStringContainsString('<footer', $html);
        $this->assertStringContainsString('data-header', $html);
        $this->assertStringContainsString(route('studios'), $html);

        // En nooit in Google.
        $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $html);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('bareCodes')]
    public function test_server_errors_get_a_standalone_page(int $code): void
    {
        $response = $this->get('/_test/fout/' . $code)->assertStatus($code);
        $html = $response->getContent();

        $response->assertSee(__('errors.' . $code . '.heading'));

        // Geen header, footer of gebouwde assets: die kunnen bij een storing zelf stuk zijn.
        $this->assertStringNotContainsString('<footer', $html);
        $this->assertStringNotContainsString('/build/assets/', $html);
        $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $html);
        $this->assertStringContainsString(__('errors.home'), $html);
    }

    public function test_an_unknown_client_code_falls_back_to_the_shared_page(): void
    {
        $this->get('/_test/fout/418')
            ->assertStatus(418)
            ->assertSee(__('errors.4xx.heading'))
            ->assertSee('418');
    }

    public function test_an_unknown_server_code_falls_back_to_the_shared_page(): void
    {
        $this->get('/_test/fout/502')
            ->assertStatus(502)
            ->assertSee(__('errors.5xx.heading'))
            ->assertSee('502');
    }

    public function test_a_real_crash_shows_the_500_page_and_leaks_nothing(): void
    {
        // Zoals het op de server gaat: debug uit, en een fout die nergens wordt opgevangen.
        config(['app.debug' => false]);

        Route::middleware('web')->get('/_test/crash', function () {
            throw new \RuntimeException('Wachtwoord van de database staat in deze melding');
        });

        $this->get('/_test/crash')
            ->assertStatus(500)
            ->assertSee(__('errors.500.heading'))
            ->assertDontSee('Wachtwoord van de database')
            ->assertDontSee('RuntimeException');
    }

    public function test_a_missing_url_shows_the_404_page(): void
    {
        $this->get('/deze-pagina-bestaat-niet')
            ->assertNotFound()
            ->assertSee(__('errors.404.heading'));
    }

    public function test_a_missing_blog_article_shows_the_404_page(): void
    {
        $this->get('/blog/bestaat-niet')
            ->assertNotFound()
            ->assertSee(__('errors.404.heading'));
    }

    public function test_the_error_page_fills_the_screen_so_nothing_shows_under_the_footer(): void
    {
        $html = $this->get('/_test/fout/404')->assertNotFound()->getContent();

        $this->assertStringContainsString('<body class="flex min-h-dvh flex-col">', $html);
        $this->assertStringContainsString('<main class="flex flex-1 flex-col">', $html);
        // Het rode vlak rekt mee op, zodat er geen witte strook tussen inhoud en footer valt.
        $this->assertStringContainsString('flex flex-1 items-center overflow-hidden bg-ruby-red', $html);
    }

    public function test_the_expired_session_page_offers_a_way_back(): void
    {
        $this->get('/_test/fout/419')
            ->assertStatus(419)
            ->assertSee('data-history-back', false)
            ->assertSee(__('errors.back'));
    }

    public function test_the_login_page_is_offered_when_unauthenticated(): void
    {
        $this->get('/_test/fout/401')
            ->assertStatus(401)
            ->assertSee(route('login'), false)
            ->assertSee(__('errors.login'));
    }
}
