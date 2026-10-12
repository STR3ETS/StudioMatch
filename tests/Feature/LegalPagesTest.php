<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * De juridische teksten komen uit de aangeleverde Word-documenten, omgezet met
 * php artisan legal:import. Deze tests bewaken dat die omzetting op de site terechtkomt.
 */
class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public static function documents(): array
    {
        return [
            'algemene voorwaarden' => ['/voorwaarden', 'terms'],
            'privacyverklaring' => ['/privacy', 'privacy'],
            'cookiebeleid' => ['/cookiebeleid', 'cookies'],
            'disclaimer' => ['/disclaimer', 'disclaimer'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('documents')]
    public function test_the_page_shows_the_real_text_instead_of_the_placeholder(string $path, string $key): void
    {
        $response = $this->get($path)->assertOk();

        $response->assertDontSee(__('legal.pending_title'));
        $response->assertSee('StudioMatch');

        // Een juridische tekst is nooit een paar regels; dit vangt een lege omzetting af.
        $this->assertGreaterThan(3000, strlen(strip_tags($response->getContent())));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('documents')]
    public function test_the_page_shows_when_the_document_was_last_changed(string $path, string $key): void
    {
        $updated = config('legal.' . $key . '.updated');

        $this->assertNotNull($updated, "Geen datum gevonden in het document voor '$key'.");

        $this->get($path)->assertOk()->assertSee(__('legal.updated', ['date' => $updated]));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('documents')]
    public function test_the_pdf_can_be_downloaded(string $path, string $key): void
    {
        $pdf = config('legal.' . $key . '.pdf');

        $this->assertNotNull($pdf, "Geen PDF gekoppeld aan '$key'.");
        $this->assertFileExists(public_path($pdf));

        $this->get($path)->assertOk()->assertSee(__('legal.download'));
    }

    public function test_the_headings_tables_and_lists_survive_the_conversion(): void
    {
        $html = $this->get('/cookiebeleid')->assertOk()->getContent();

        $this->assertStringContainsString('<h2>1. Wat zijn cookies en vergelijkbare technieken?</h2>', $html);
        $this->assertStringContainsString('<th>Bewaartermijn</th>', $html);
        $this->assertStringContainsString('<li>het verwerken van betalingen;</li>', $html);
        $this->assertStringContainsString('sm-callout', $html);

        // Een adres in de tekst hoort klikbaar te zijn, ook als het document geen
        // echte hyperlink bevatte.
        $this->assertStringContainsString('href="mailto:info@studiomatch.nl"', $html);
    }

    public function test_the_recaptcha_section_is_published(): void
    {
        // Toegevoegd nadat reCAPTCHA op het contactformulier kwam; zonder deze tekst
        // klopt het cookiebeleid niet meer met wat de site doet.
        $this->get('/cookiebeleid')
            ->assertOk()
            ->assertSee('Google reCAPTCHA')
            ->assertSee('_grecaptcha');

        $this->get('/privacy')->assertOk()->assertSee('reCAPTCHA');
    }

    public function test_the_documents_are_not_dropped_loose_in_the_web_root(): void
    {
        // Een los document in public/ is bereikbaar zonder dat de site ernaar verwijst,
        // en botst bovendien met de routes.
        $this->assertSame([], glob(public_path('*.pdf')) ?: []);
        $this->assertSame([], glob(public_path('*.docx')) ?: []);
        $this->assertNotEmpty(glob(public_path('documenten/*.pdf')));
    }

    public function test_the_generated_text_is_not_interpreted_as_blade(): void
    {
        foreach (glob(resource_path('views/legal/partials/*.blade.php')) as $file) {
            $this->assertStringContainsString(
                '@verbatim',
                file_get_contents($file),
                basename($file) . ' hoort in @verbatim te staan, anders leest Blade een @ of {{ in de tekst als code.'
            );
        }

        // Een e-mailadres in de tekst moet er gewoon staan, niet als Blade-instructie.
        $this->get('/privacy')->assertOk()->assertSee('info@studiomatch.nl');
    }

    public function test_every_legal_page_has_its_text(): void
    {
        // De disclaimer kwam als laatste binnen; sindsdien staat er op geen enkele
        // juridische pagina nog een "tekst volgt".
        foreach (['/voorwaarden', '/privacy', '/cookiebeleid', '/disclaimer'] as $path) {
            $this->get($path)->assertOk()->assertDontSee(__('legal.pending_title'));
        }
    }

    public function test_google_gets_all_four_legal_pages(): void
    {
        $sitemap = $this->get('/sitemap.xml')->assertOk();

        foreach (['/voorwaarden', '/privacy', '/cookiebeleid', '/disclaimer'] as $path) {
            $sitemap->assertSee(url($path), false);
        }
    }

    public function test_the_disclaimer_shows_its_sections(): void
    {
        $this->get('/disclaimer')
            ->assertOk()
            ->assertSee('<h2>1. Identiteit van StudioMatch</h2>', false)
            ->assertSee('<h2>14. Aansprakelijkheid</h2>', false)
            ->assertSee('<h2>18. Contact</h2>', false);
    }

    public function test_the_documents_folder_does_not_shadow_the_terms_url(): void
    {
        // Een map public/voorwaarden zou de webserver de route laten overrulen, waardoor
        // /voorwaarden een 403 geeft in plaats van de pagina.
        $this->assertDirectoryDoesNotExist(public_path('voorwaarden'));
        $this->assertDirectoryExists(public_path('documenten'));
    }

    public function test_the_word_documents_are_not_publicly_reachable(): void
    {
        $this->assertSame([], glob(public_path('**/*.docx')) ?: []);
        $this->assertNotEmpty(glob(resource_path('legal/*.docx')) + glob(resource_path('legal/*.pdf')));
    }

    public function test_the_legal_pages_are_listed_in_the_sitemap(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(url('/voorwaarden'), false)
            ->assertSee(url('/privacy'), false)
            ->assertSee(url('/cookiebeleid'), false);
    }

    public function test_the_footer_links_to_every_legal_page(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        foreach (['/voorwaarden', '/privacy', '/cookiebeleid', '/disclaimer'] as $path) {
            $this->assertStringContainsString('href="' . url($path) . '"', $html);
        }
    }
}
