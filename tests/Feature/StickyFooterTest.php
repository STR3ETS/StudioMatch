<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Een korte pagina mag nooit eindigen met een strook achtergrond onder de footer.
 * De publieke layout is daarvoor een kolom over de volle schermhoogte, met de inhoud
 * als groeiend deel, zodat de footer onderaan het scherm blijft staan.
 */
class StickyFooterTest extends TestCase
{
    use RefreshDatabase;

    public static function publicPages(): array
    {
        return [
            'home' => ['/'],
            'studios' => ['/studios'],
            'verhuurders' => ['/voor-studios'],
            'hoe werkt het' => ['/hoe-werkt-het'],
            'faq' => ['/faq'],
            'contact' => ['/contact'],
            'blog' => ['/blog'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('publicPages')]
    public function test_every_public_page_pins_the_footer_to_the_bottom(string $path): void
    {
        $html = $this->get($path)->assertOk()->getContent();

        $this->assertStringContainsString('<body class="flex min-h-dvh flex-col">', $html);
        $this->assertStringContainsString('<main class="flex-1">', $html);

        $this->assertLessThan(
            strpos($html, '<footer'),
            strpos($html, '<main'),
            'De footer hoort na de inhoud te komen.'
        );
    }

    public function test_the_empty_blog_still_fills_the_screen(): void
    {
        // De kortst mogelijke pagina van de site: een lege staat zonder artikelen.
        $html = $this->get('/blog')->assertOk()->assertSee(__('blog.empty'))->getContent();

        $this->assertStringContainsString('min-h-dvh', $html);
        $this->assertStringContainsString('flex-1', $html);
    }
}
