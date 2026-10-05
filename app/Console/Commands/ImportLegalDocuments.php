<?php

namespace App\Console\Commands;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Console\Command;
use ZipArchive;

/**
 * Zet de aangeleverde Word-documenten om naar de juridische pagina's op de site.
 *
 * De klant levert deze teksten periodiek opnieuw aan, steeds als nieuwe versie. Daarom
 * is dit een commando en geen eenmalig knip- en plakwerk: nieuwe .docx in resources/legal
 * zetten, bijbehorende .pdf in public/documenten, dit draaien en de site is bij.
 */
class ImportLegalDocuments extends Command
{
    protected $signature = 'legal:import';

    protected $description = 'Zet de Word-documenten in resources/legal om naar de juridische pagina\'s';

    private const NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    private const REL_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    /** Welk woord in de bestandsnaam hoort bij welke pagina. */
    private const KEYS = [
        'voorwaarden' => 'terms',
        'privacy' => 'privacy',
        'cookie' => 'cookies',
        'disclaimer' => 'disclaimer',
    ];

    public function handle(): int
    {
        $sourceDir = resource_path('legal');
        $partialDir = resource_path('views/legal/partials');

        if (! is_dir($sourceDir)) {
            $this->error('Map resources/legal bestaat niet.');

            return self::FAILURE;
        }

        if (! is_dir($partialDir) && ! mkdir($partialDir, 0777, true)) {
            $this->error('Kan resources/views/legal/partials niet aanmaken.');

            return self::FAILURE;
        }

        $manifest = [];

        foreach ($this->documents($sourceDir) as $key => $path) {
            [$html, $updated] = str_ends_with(strtolower($path), '.pdf')
                ? $this->convertPdf($path)
                : $this->convert($path);

            if ($html === null) {
                continue;
            }

            file_put_contents(
                $partialDir . '/' . $key . '.blade.php',
                "{{-- Automatisch gegenereerd door: php artisan legal:import. Niet met de hand bewerken. --}}\n"
                . "@verbatim\n" . $html . "@endverbatim\n"
            );

            $manifest[$key] = [
                'updated' => $updated,
                'pdf' => $this->pdfFor($key),
                'source' => basename($path),
            ];

            $this->info(sprintf('%-12s <- %s%s', $key, basename($path), $updated ? ' (' . $updated . ')' : ''));
        }

        if ($manifest === []) {
            $this->error('Geen documenten gevonden in resources/legal.');

            return self::FAILURE;
        }

        ksort($manifest);

        file_put_contents(
            config_path('legal.php'),
            "<?php\n\n"
            . "/*\n * Automatisch gegenereerd door: php artisan legal:import.\n"
            . " * De teksten zelf staan in resources/views/legal/partials.\n */\n\n"
            . 'return ' . var_export($manifest, true) . ";\n"
        );

        $this->newLine();
        $this->info('config/legal.php bijgewerkt. Draai daarna php artisan config:clear.');

        return self::SUCCESS;
    }

    /**
     * De bronbestanden per pagina. Komt een document in beide vormen binnen, dan wint
     * .docx: daar zitten de hyperlinks in, en die gaan bij het exporteren naar PDF verloren.
     *
     * @return array<string, string>
     */
    private function documents(string $directory): array
    {
        $found = [];

        foreach (['pdf', 'docx'] as $extension) {
            foreach (glob($directory . '/*.' . $extension) as $path) {
                $key = $this->keyFor(basename($path));

                if ($key === null) {
                    $this->warn('Overgeslagen, geen pagina herkend: ' . basename($path));

                    continue;
                }

                $found[$key] = $path;
            }
        }

        ksort($found);

        return $found;
    }

    /**
     * Leest een PDF uit met het hulpscript op basis van PyMuPDF.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function convertPdf(string $path): array
    {
        $script = base_path('scripts/legal_pdf.py');
        $target = tempnam(sys_get_temp_dir(), 'legal') . '.json';

        $command = sprintf(
            '%s %s %s %s',
            escapeshellarg($this->python()),
            escapeshellarg($script),
            escapeshellarg($path),
            escapeshellarg($target)
        );

        exec($command . ' 2>&1', $output, $status);

        if ($status !== 0 || ! is_file($target)) {
            $this->error('Kon ' . basename($path) . ' niet uitlezen.');
            $this->line('  ' . implode("\n  ", $output));
            $this->line('  Een PDF uitlezen vereist Python met PyMuPDF (pip install pymupdf).');
            $this->line('  Staat Python ergens anders, zet dan PYTHON_BIN in je .env.');

            return [null, null];
        }

        $data = json_decode(file_get_contents($target), true);
        @unlink($target);

        if (! is_array($data)) {
            $this->error('Onleesbaar resultaat voor ' . basename($path) . '.');

            return [null, null];
        }

        return [$this->renderBlocks($data['blocks'] ?? []), $data['updated'] ?? null];
    }

    private function python(): string
    {
        return (string) (env('PYTHON_BIN') ?: 'python');
    }

    /** Zet de blokken uit het hulpscript om naar dezelfde opmaak als de Word-route. */
    private function renderBlocks(array $blocks): string
    {
        $html = '';
        $callout = [];

        $closeCallout = function () use (&$callout, &$html): void {
            if ($callout !== []) {
                $html .= '<div class="sm-callout">' . "\n" . implode("\n", $callout) . "\n</div>\n";
                $callout = [];
            }
        };

        foreach ($blocks as $block) {
            $type = $block['type'] ?? '';

            $piece = match ($type) {
                // Een kop is al vet door de opmaak; een extra <strong> is ruis.
                'h2', 'h3' => '<' . $type . '>' . $this->runsToHtml($block['runs'] ?? [], false) . '</' . $type . '>',
                'p', 'callout' => '<p>' . $this->runsToHtml($block['runs'] ?? []) . '</p>',
                'ul' => $this->listHtml($block['items'] ?? []),
                'table' => $this->rowsToTable($block['head'] ?? [], $block['rows'] ?? []),
                default => '',
            };

            if (trim(strip_tags($piece)) === '' && $type !== 'table') {
                continue;
            }

            // Een kop en de opsomming eronder horen in hetzelfde kader.
            if ($type === 'callout' || ($block['callout'] ?? false)) {
                $callout[] = $piece;

                continue;
            }

            $closeCallout();
            $html .= $piece . "\n";
        }

        $closeCallout();

        return $html;
    }

    private function listHtml(array $items): string
    {
        $html = "<ul>\n";

        foreach ($items as $item) {
            $text = $this->runsToHtml($item);

            if (trim(strip_tags($text)) !== '') {
                $html .= '<li>' . $text . "</li>\n";
            }
        }

        return $html . "</ul>\n";
    }

    private function rowsToTable(array $head, array $rows): string
    {
        if ($head === []) {
            return '';
        }

        $html = "<div class=\"sm-table\">\n<table>\n<thead>\n<tr>";

        foreach ($head as $cell) {
            $html .= '<th>' . $this->cellHtml($cell) . '</th>';
        }

        $html .= "</tr>\n</thead>\n<tbody>\n";

        foreach ($rows as $row) {
            $html .= '<tr>';

            foreach ($row as $cell) {
                $html .= '<td>' . $this->cellHtml($cell) . '</td>';
            }

            $html .= "</tr>\n";
        }

        return $html . "</tbody>\n</table>\n</div>\n";
    }

    private function cellHtml(?string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', (string) $text) ?? '');

        return $this->autoLink(htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }

    /** @param array<int, array{text: string, bold?: bool}> $runs */
    private function runsToHtml(array $runs, bool $allowBold = true): string
    {
        $html = '';

        foreach ($runs as $run) {
            $text = htmlspecialchars($run['text'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html .= $allowBold && ($run['bold'] ?? false) ? '<strong>' . $text . '</strong>' : $text;
        }

        $html = preg_replace('#</strong>(\s*)<strong>#', '$1', $html) ?? $html;
        $html = trim(preg_replace('/\s+/u', ' ', $html) ?? '');

        return $this->autoLink($html);
    }

    /**
     * Bij het exporteren naar PDF gaan de hyperlinks verloren; wat als adres in de tekst
     * staat maken we daarom alsnog klikbaar.
     */
    private function autoLink(string $html): string
    {
        $html = preg_replace(
            '~(?<![\w/@])((?:https?://|www\.)[^\s<>"\']+[^\s<>"\'.,;:)\]])~i',
            '<a href="$1" target="_blank" rel="noopener">$1</a>',
            $html
        ) ?? $html;

        $html = preg_replace('~href="www\.~i', 'href="https://www.', $html) ?? $html;

        return preg_replace(
            '~(?<![\w.@"/])([\w.+-]+@[\w-]+\.[a-z]{2,})~i',
            '<a href="mailto:$1">$1</a>',
            $html
        ) ?? $html;
    }

    private function keyFor(string $filename): ?string
    {
        $name = mb_strtolower($filename);

        foreach (self::KEYS as $needle => $key) {
            if (str_contains($name, $needle)) {
                return $key;
            }
        }

        return null;
    }

    /** De vaste bestandsnaam per pagina, zodat de downloadlink altijd hetzelfde blijft. */
    private const PDF_NAMES = [
        'terms' => 'algemene-voorwaarden.pdf',
        'privacy' => 'privacyverklaring.pdf',
        'cookies' => 'cookiebeleid.pdf',
        'disclaimer' => 'disclaimer.pdf',
    ];

    /**
     * Zoekt de bijbehorende PDF en geeft hem een vaste naam.
     *
     * De aangeleverde bestanden heten "Cookiebeleid_StudioMatch_2026 V2.pdf": spaties in
     * de url en een versienummer dat elke ronde verandert. Door te hernoemen blijft een
     * gedeelde of opgeslagen link ook na een nieuwe versie gewoon werken.
     */
    private function pdfFor(string $key): ?string
    {
        $needle = (string) array_search($key, self::KEYS, true);
        $target = self::PDF_NAMES[$key];
        $targetPath = public_path('documenten/' . $target);

        foreach (glob(public_path('documenten/*.pdf')) as $pdf) {
            if (basename($pdf) === $target) {
                continue;
            }

            if (str_contains(mb_strtolower(basename($pdf)), $needle)) {
                if (is_file($targetPath)) {
                    unlink($targetPath);
                }

                rename($pdf, $targetPath);
                $this->line('  pdf hernoemd naar ' . $target);

                return 'documenten/' . $target;
            }
        }

        return is_file($targetPath) ? 'documenten/' . $target : null;
    }

    /**
     * @return array{0: string, 1: ?string} de html en de datum van laatste wijziging
     */
    private function convert(string $path): array
    {
        $zip = new ZipArchive();
        $zip->open($path);
        $xml = $zip->getFromName('word/document.xml');
        $rels = $zip->getFromName('word/_rels/document.xml.rels');
        $zip->close();

        $links = $this->relationships($rels);

        $document = new DOMDocument();
        $document->loadXML($xml);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', self::NS);
        $xpath->registerNamespace('r', self::REL_NS);

        $body = $xpath->query('//w:body')->item(0);

        $html = '';
        $listBuffer = [];
        $updated = null;

        foreach ($body->childNodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            if ($node->localName === 'tbl') {
                $html .= $this->flushList($listBuffer) . $this->tableHtml($node, $xpath, $links);

                continue;
            }

            if ($node->localName !== 'p') {
                continue;
            }

            $style = $xpath->query('.//w:pStyle/@w:val', $node)->item(0)?->nodeValue ?? '';
            $text = $this->inline($node, $xpath, $links);
            $plain = trim(strip_tags($text));

            if ($plain === '') {
                continue;
            }

            // De documenttitel staat al als kop boven de pagina.
            if (strcasecmp($style, 'Title') === 0) {
                continue;
            }

            // De regel eronder bevat de datum. De ene tekst zegt "Laatst gewijzigd", de
            // andere "Versie", en daarachter staan soms nog de bedrijfsgegevens. Die
            // houden we wel op de pagina.
            if ($updated === null
                && preg_match('/^(?:laatst gewijzigd|versie)\s*:?\s*(\d{1,2}\s+\p{L}+\s+\d{4})(.*)$/iu', $plain, $match)) {
                $updated = trim($match[1]);
                $rest = trim($match[2], " \t|·-");

                if (mb_strlen($rest) > 10) {
                    $html .= '<p>' . htmlspecialchars($rest, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</p>\n";
                }

                continue;
            }

            // Deze documenten markeren opsommingen met een alineastijl (ListBullet) en
            // niet met een nummering, dus op allebei controleren.
            if (str_contains(mb_strtolower($style), 'list') || $xpath->query('.//w:numPr', $node)->length > 0) {
                $listBuffer[] = $text;

                continue;
            }

            $html .= $this->flushList($listBuffer);

            if (preg_match('/^(Heading|Kop)(\d)/i', $style, $match)) {
                // De paginatitel is al een h1, dus de koppen uit het document schuiven op.
                $level = min(4, (int) $match[2] + 1);
                $html .= "<h{$level}>" . $text . "</h{$level}>\n";

                continue;
            }

            $html .= '<p>' . $text . "</p>\n";
        }

        return [$html . $this->flushList($listBuffer), $updated];
    }

    private function flushList(array &$items): string
    {
        if ($items === []) {
            return '';
        }

        $html = "<ul>\n";
        foreach ($items as $item) {
            $html .= '<li>' . $item . "</li>\n";
        }
        $items = [];

        return $html . "</ul>\n";
    }

    private function tableHtml(DOMElement $table, DOMXPath $xpath, array $links): string
    {
        $rows = $xpath->query('.//w:tr', $table);

        if ($rows->length === 0) {
            return '';
        }

        $cellsOf = function (DOMNode $row) use ($xpath, $links): array {
            $cells = [];

            foreach ($xpath->query('.//w:tc', $row) as $cell) {
                $parts = [];

                foreach ($xpath->query('.//w:p', $cell) as $paragraph) {
                    $text = $this->inline($paragraph, $xpath, $links);
                    if (trim(strip_tags($text)) !== '') {
                        $parts[] = $text;
                    }
                }

                $cells[] = implode('<br>', $parts);
            }

            return $cells;
        };

        $header = $cellsOf($rows->item(0));

        // Word zet ook lege tabellen in een document, puur voor de opmaak. Die leveren
        // op de pagina een leeg kader op, dus die slaan we over.
        $everything = '';
        foreach ($rows as $row) {
            $everything .= implode('', $cellsOf($row));
        }

        if (trim(strip_tags($everything)) === '') {
            return '';
        }

        // Word gebruikt een tabel van een enkele cel ook als opvallend kader. Dat is voor
        // de lezer geen tabel, dus dat wordt een aandachtsblok.
        if ($rows->length === 1 && count($header) === 1) {
            return '<div class="sm-callout">' . $header[0] . "</div>\n";
        }

        // Tabellen zijn op een telefoon al snel te breed, vandaar de eigen schuifbalk.
        $html = "<div class=\"sm-table\">\n<table>\n<thead>\n<tr>";

        foreach ($header as $cell) {
            $html .= '<th>' . $cell . '</th>';
        }

        $html .= "</tr>\n</thead>\n<tbody>\n";

        for ($i = 1; $i < $rows->length; $i++) {
            $html .= '<tr>';

            foreach ($cellsOf($rows->item($i)) as $cell) {
                $html .= '<td>' . $cell . '</td>';
            }

            $html .= "</tr>\n";
        }

        return $html . "</tbody>\n</table>\n</div>\n";
    }

    /** De tekst binnen een alinea, met vetgedrukt en links intact. */
    private function inline(DOMNode $paragraph, DOMXPath $xpath, array $links): string
    {
        $html = '';

        foreach ($xpath->query('.//w:r | .//w:hyperlink', $paragraph) as $node) {
            if ($node->localName === 'hyperlink') {
                $id = $node->getAttributeNS(self::REL_NS, 'id');
                $target = $links[$id] ?? null;
                $text = $this->runs($node, $xpath);

                if ($text === '') {
                    continue;
                }

                // Alleen echte websites openen in een nieuw tabblad; een mailto hoort
                // gewoon de mailclient te openen.
                $external = str_starts_with($target ?? '', 'http');

                $html .= $target
                    ? '<a href="' . htmlspecialchars($target, ENT_QUOTES) . '"'
                        . ($external ? ' target="_blank" rel="noopener"' : '') . '>' . $text . '</a>'
                    : $text;

                continue;
            }

            // Runs binnen een hyperlink zijn hierboven al verwerkt.
            if ($node->parentNode?->localName === 'hyperlink') {
                continue;
            }

            $html .= $this->runText($node, $xpath);
        }

        return $this->tidy($html);
    }

    private function runs(DOMNode $node, DOMXPath $xpath): string
    {
        $text = '';
        foreach ($xpath->query('.//w:r', $node) as $run) {
            $text .= $this->runText($run, $xpath);
        }

        return $text;
    }

    private function runText(DOMNode $run, DOMXPath $xpath): string
    {
        $text = '';
        foreach ($xpath->query('.//w:t | .//w:tab | .//w:br', $run) as $node) {
            $text .= $node->localName === 't' ? $node->nodeValue : ' ';
        }

        if (trim($text) === '') {
            return $text === '' ? '' : ' ';
        }

        $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $bold = $xpath->query('.//w:b[not(@w:val="0")]', $run)->length > 0;
        $italic = $xpath->query('.//w:i[not(@w:val="0")]', $run)->length > 0;

        if ($bold) {
            $text = '<strong>' . $text . '</strong>';
        }

        if ($italic) {
            $text = '<em>' . $text . '</em>';
        }

        return $text;
    }

    private function tidy(string $html): string
    {
        // Word knipt een zin op in losse stukjes, wat naast elkaar staande tags oplevert.
        $html = preg_replace('#</strong>(\s*)<strong>#', '$1', $html) ?? $html;
        $html = preg_replace('#</em>(\s*)<em>#', '$1', $html) ?? $html;
        $html = preg_replace('/[ \t]+/', ' ', $html) ?? $html;

        return trim($html);
    }

    /** @return array<string, string> relatie-id naar doel-url */
    private function relationships(?string $xml): array
    {
        if ($xml === null) {
            return [];
        }

        $document = new DOMDocument();
        $document->loadXML($xml);

        $links = [];

        foreach ($document->getElementsByTagName('Relationship') as $relationship) {
            if (str_ends_with($relationship->getAttribute('Type'), '/hyperlink')) {
                $links[$relationship->getAttribute('Id')] = $relationship->getAttribute('Target');
            }
        }

        return $links;
    }
}
