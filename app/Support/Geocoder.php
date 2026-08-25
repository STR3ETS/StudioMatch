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
        try {
            $response = Http::timeout(5)
                ->get('https://api.pdok.nl/bzk/locatieserver/search/v3_1/free', [
                    'q' => "{$street}, {$postalCode} {$city}",
                    'fq' => 'type:adres',
                    'rows' => 1,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $doc = $response->json('response.docs.0');

            if (! is_array($doc)) {
                return false;
            }

            $wanted = strtoupper(preg_replace('/\s+/', '', $postalCode));
            $found = strtoupper(preg_replace('/\s+/', '', $doc['postcode'] ?? ''));

            if ($wanted === '' || $wanted !== $found) {
                return false;
            }

            $point = $doc['centroide_ll'] ?? null;

            if (! is_string($point) || ! preg_match('/POINT\(([\d.]+) ([\d.]+)\)/', $point, $matches)) {
                return false;
            }

            return ['lat' => (float) $matches[2], 'lng' => (float) $matches[1]];
        } catch (Throwable) {
            return null;
        }
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
