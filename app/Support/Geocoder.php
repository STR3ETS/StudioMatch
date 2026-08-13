<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Throwable;

class Geocoder
{

    public static function geocode(string $street, string $postalCode, string $city): ?array
    {
        try {
            $point = Http::timeout(5)
                ->get('https://api.pdok.nl/bzk/locatieserver/search/v3_1/free', [
                    'q' => "{$street}, {$postalCode} {$city}",
                    'fq' => 'type:adres',
                    'rows' => 1,
                ])
                ->json('response.docs.0.centroide_ll');

            if (! is_string($point) || ! preg_match('/POINT\(([\d.]+) ([\d.]+)\)/', $point, $matches)) {
                return null;
            }

            return ['lat' => (float) $matches[2], 'lng' => (float) $matches[1]];
        } catch (Throwable) {
            return null;
        }
    }
}
