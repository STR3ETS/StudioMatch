<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * De verkooppagina's lieten lang een gestippeld kader zien met de tekst welke
 * schermafbeelding daar hoorde te komen. Die staan er nu echt in.
 */
class MarketingScreenshotsTest extends TestCase
{
    use RefreshDatabase;

    public static function pages(): array
    {
        return [
            'verhuurders' => ['/voor-studios', ['verhuurder-dashboard', 'verhuurder-omzet', 'beschikbaarheid']],
            'hoe werkt het' => ['/hoe-werkt-het', ['zoeken', 'boeken', 'boekingen']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function test_the_page_shows_real_screenshots_instead_of_placeholders(string $path, array $images): void
    {
        $html = $this->get($path)->assertOk()->getContent();

        foreach ($images as $image) {
            $this->assertStringContainsString('screenshots/' . $image . '.webp', $html);
            $this->assertFileExists(public_path('screenshots/' . $image . '.webp'));
        }

        // Het gestippelde kader hoort nergens meer te staan.
        $this->assertStringNotContainsString('border-dashed border-prussian-blue/20', $html);
    }

    public function test_a_mockup_without_an_image_still_falls_back_to_the_placeholder(): void
    {
        // Zo blijft een pagina heel als er ooit een afbeelding ontbreekt.
        $html = $this->blade('<x-phone-mockup label="Komt nog" />');

        $this->assertStringContainsString('Komt nog', $html);
        $this->assertStringContainsString('border-dashed', $html);
    }

    public function test_every_screenshot_has_the_shape_of_the_phone_frame(): void
    {
        foreach (glob(public_path('screenshots/*.webp')) as $file) {
            [$width, $height] = getimagesize($file);

            // Het lijstje op de pagina is 9:19; wijkt een afbeelding af, dan wordt hij bijgesneden.
            $this->assertEqualsWithDelta(
                9 / 19,
                $width / $height,
                0.01,
                basename($file) . ' heeft niet de verhouding van het telefoonlijstje.'
            );

            $this->assertLessThan(120 * 1024, filesize($file), basename($file) . ' is te zwaar voor een webpagina.');
        }
    }
}
