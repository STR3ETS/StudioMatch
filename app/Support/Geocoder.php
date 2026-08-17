<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Throwable;

class Geocoder
{

    public static function geocode(string $street, string $postalCode, string $city): ?array
    {
        return self::verify($street, $postalCode, $city) ?: null;
    }

    public static function verify(string $street, string $postalCode, string $city): array|false|null
    {
        return self::lookup("{$street}, {$postalCode} {$city}", 'type:adres');
    }

    public static function place(string $query): ?array
    {
        return self::lookup($query, 'type:(woonplaats OR postcode)') ?: null;
    }

    private static function lookup(string $query, string $filter): array|false|null
    {
        try {
            $response = Http::timeout(5)
                ->get('https://api.pdok.nl/bzk/locatieserver/search/v3_1/free', [
                    'q' => $query,
                    'fq' => $filter,
                    'rows' => 1,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $point = $response->json('response.docs.0.centroide_ll');

            if (! is_string($point) || ! preg_match('/POINT\(([\d.]+) ([\d.]+)\)/', $point, $matches)) {
                return false;
            }

            return ['lat' => (float) $matches[2], 'lng' => (float) $matches[1]];
        } catch (Throwable) {
            return null;
        }
    }
}
