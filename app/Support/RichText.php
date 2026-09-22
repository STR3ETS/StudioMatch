<?php

namespace App\Support;

use Dom\Element;
use Dom\HTMLDocument;
use Dom\Node;

/**
 * Knijpt de HTML uit de tekst-editor terug tot een korte, veilige lijst tags.
 *
 * Een contenteditable-veld levert van alles op: spans met inline styles, lege divs,
 * geplakte opmaak uit Word. Alles wat hier niet expliciet is toegestaan wordt uitgepakt
 * (de tekst blijft, de tag verdwijnt), zodat er nooit een script of een style-injectie
 * in de database belandt en de blogpagina's er consistent uitzien.
 */
class RichText
{
    /** Toegestane tags, met per tag de attributen die mogen blijven staan. */
    private const ALLOWED = [
        'p' => [],
        'br' => [],
        'strong' => [],
        'em' => [],
        'u' => [],
        's' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'h3' => [],
        'blockquote' => [],
        'a' => ['href'],
    ];

    /** Tags die hetzelfde betekenen als een toegestane tag en dus worden omgezet. */
    private const RENAME = [
        'b' => 'strong',
        'i' => 'em',
        'strike' => 's',
        'del' => 's',
        'div' => 'p',
        'h1' => 'h3',
        'h2' => 'h3',
        'h4' => 'h3',
        'h5' => 'h3',
        'h6' => 'h3',
    ];

    /** Tags waarvan ook de inhoud weg moet, niet alleen de tag zelf. */
    private const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'noscript', 'template'];

    private const SCHEMES = ['http', 'https', 'mailto', 'tel'];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $document = @HTMLDocument::createFromString(
            '<div id="sm-root">' . $html . '</div>',
            LIBXML_NOERROR,
            'UTF-8'
        );

        $root = $document?->getElementById('sm-root');

        if ($root === null) {
            return '';
        }

        self::walk($root, $document);

        $clean = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $clean .= $document->saveHtml($child);
        }

        // Editors strooien met harde spaties. Die leveren later vreemde afbrekingen op,
        // en een alinea die alleen daaruit bestaat is voor de lezer gewoon leeg.
        $clean = str_replace(["\u{00A0}", '&nbsp;'], ' ', $clean);
        $clean = preg_replace('/<p>(\s|<br\s*\/?>)*<\/p>/i', '', $clean) ?? $clean;

        return self::isBlank($clean) ? '' : trim($clean);
    }

    /** Leeg voor de gebruiker: geen tekst en geen enkel zichtbaar element. */
    public static function isBlank(?string $html): bool
    {
        return trim(strip_tags((string) $html)) === '' && ! preg_match('/<(img|br)\b/i', (string) $html);
    }

    /** Platte tekst, bijvoorbeeld voor een samenvatting of een meta-omschrijving. */
    public static function plain(?string $html): string
    {
        $text = strip_tags(preg_replace('/<\/(p|li|h3|blockquote)>/i', ' ', (string) $html) ?? '');

        return trim(preg_replace('/\s+/u', ' ', html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    }

    private static function walk(Node $node, HTMLDocument $document): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof Element) {
                // Tekst blijft staan; commentaar en processing instructions gaan eruit.
                if ($child->nodeType !== XML_TEXT_NODE) {
                    $child->parentNode?->removeChild($child);
                }

                continue;
            }

            $name = strtolower($child->tagName);

            if (in_array($name, self::DROP, true)) {
                $child->parentNode?->removeChild($child);

                continue;
            }

            if (isset(self::RENAME[$name])) {
                $child = self::rename($child, self::RENAME[$name], $document);
                $name = strtolower($child->tagName);
            }

            if (! array_key_exists($name, self::ALLOWED)) {
                self::walk($child, $document);
                self::unwrap($child);

                continue;
            }

            self::stripAttributes($child, self::ALLOWED[$name]);

            if ($name === 'a' && ! self::keepLink($child)) {
                self::walk($child, $document);
                self::unwrap($child);

                continue;
            }

            self::walk($child, $document);
        }
    }

    private static function rename(Element $element, string $tag, HTMLDocument $document): Element
    {
        $replacement = $document->createElement($tag);

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $replacement->setAttribute($attribute->name, $attribute->value);
        }

        while ($element->firstChild !== null) {
            $replacement->appendChild($element->firstChild);
        }

        $element->parentNode?->replaceChild($replacement, $element);

        return $replacement;
    }

    /** Haalt de tag weg maar houdt de inhoud op dezelfde plek. */
    private static function unwrap(Element $element): void
    {
        $parent = $element->parentNode;

        if ($parent === null) {
            return;
        }

        while ($element->firstChild !== null) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    private static function stripAttributes(Element $element, array $allowed): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            if (! in_array(strtolower($attribute->name), $allowed, true)) {
                $element->removeAttribute($attribute->name);
            }
        }
    }

    private static function keepLink(Element $link): bool
    {
        $href = trim($link->getAttribute('href'));

        if ($href === '') {
            return false;
        }

        $scheme = parse_url($href, PHP_URL_SCHEME);

        // Relatieve links naar de eigen site mogen, verder alleen bekende schema's.
        if ($scheme === null) {
            if (! str_starts_with($href, '/') && ! str_starts_with($href, '#')) {
                return false;
            }
        } elseif (! in_array(strtolower($scheme), self::SCHEMES, true)) {
            return false;
        }

        if ($scheme !== null && in_array(strtolower($scheme), ['http', 'https'], true)) {
            $link->setAttribute('target', '_blank');
            $link->setAttribute('rel', 'noopener nofollow');
        }

        return true;
    }
}
