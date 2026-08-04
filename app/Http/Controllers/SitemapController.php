<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Sitemap voor zoekmachines (scope §2.1 SEO-basis): vaste pagina's plus
     * alle live studiopagina's.
     */
    public function __invoke(): Response
    {
        $urls = collect([
            ['loc' => route('home')],
            ['loc' => route('studios')],
            ['loc' => route('hosts')],
            ['loc' => route('how')],
            ['loc' => route('faq')],
            ['loc' => route('contact')],
        ]);

        $rooms = Room::publiclyVisible()->get()->map(fn (Room $room) => [
            'loc' => route('studios.show', $room),
            'lastmod' => $room->updated_at->toAtomString(),
        ]);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls->concat($rooms) as $url) {
            $xml .= '  <url><loc>' . e($url['loc']) . '</loc>'
                . (isset($url['lastmod']) ? '<lastmod>' . $url['lastmod'] . '</lastmod>' : '')
                . "</url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
