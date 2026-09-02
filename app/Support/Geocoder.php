<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class Geocoder
{

    public static function geocode(string $street, string $postalCode, string $city): ?array
    {
        return self::verify($street, $postalCode, $city) ?: null;
    }

    /**
     * Confirm an address really exists. PDOK searches fuzzily, so a made up street with a
     * real postcode still returns that postcode's actual address. Comparing only the
     * postcode therefore lets nonsense through: street name and house number are checked
     * against what the register returns as well.
     *
     * Returns coordinates on a match, false when the address does not exist, and null
     * when the register could not be reached.
     */
    public static function verify(string $street, string $postalCode, string $city): array|false|null
    {
        try {
            $response = Http::timeout(5)
                ->get('https://api.pdok.nl/bzk/locatieserver/search/v3_1/free', [
                    'q' => "{$street}, {$postalCode} {$city}",
                    'fq' => 'type:adres',
                    'fl' => 'straatnaam,huis_nlt,postcode,woonplaatsnaam,centroide_ll',
                    'rows' => 5,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $docs = $response->json('response.docs');

            if (! is_array($docs) || $docs === []) {
                return false;
            }

            [$wantedStreet, $wantedNumber] = self::splitStreet($street);
            $wantedPostal = self::squash($postalCode);

            if ($wantedStreet === '' || $wantedNumber === '' || $wantedPostal === '') {
                return false;
            }

            foreach ($docs as $doc) {
                if (self::squash($doc['postcode'] ?? '') !== $wantedPostal) {
                    continue;
                }

                if (self::squash($doc['straatnaam'] ?? '') !== $wantedStreet) {
                    continue;
                }

                // "12", "12A" and "12 bis" all have to start with the same number.
                preg_match('/\d+/', (string) ($doc['huis_nlt'] ?? ''), $found);

                if (($found[0] ?? '') !== $wantedNumber) {
                    continue;
                }

                $point = $doc['centroide_ll'] ?? null;

                if (! is_string($point) || ! preg_match('/POINT\(([\d.]+) ([\d.]+)\)/', $point, $matches)) {
                    return false;
                }

                return ['lat' => (float) $matches[2], 'lng' => (float) $matches[1]];
            }

            return false;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * "Prinsengracht 263 B" becomes ['prinsengracht', '263'].
     */
    private static function splitStreet(string $street): array
    {
        $street = trim($street);

        if (! preg_match('/^(.*?)\s*(\d+)/u', $street, $matches)) {
            return ['', ''];
        }

        return [self::squash($matches[1]), $matches[2]];
    }

    /**
     * Lowercase, strip accents and remove everything that is not a letter or digit, so
     * "'s-Gravenhage" and "s gravenhage" compare equal.
     */
    private static function squash(string $value): string
    {
        $value = Str::ascii($value);

        return mb_strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', $value));
    }

    /**
     * Existing addresses matching what the user typed, for the autocomplete dropdown.
     */
    public static function suggest(string $query): array
    {
        try {
            $response = Http::timeout(5)
                ->get('https://api.pdok.nl/bzk/locatieserver/search/v3_1/free', [
                    'q' => $query,
                    'fq' => 'type:adres',
                    'fl' => 'weergavenaam,straatnaam,huis_nlt,postcode,woonplaatsnaam',
                    'rows' => 6,
                ]);

            if (! $response->successful()) {
                return [];
            }

            return collect($response->json('response.docs') ?? [])
                ->map(fn (array $doc) => [
                    'label' => (string) ($doc['weergavenaam'] ?? ''),
                    'street' => trim(($doc['straatnaam'] ?? '') . ' ' . ($doc['huis_nlt'] ?? '')),
                    'postal_code' => self::formatPostalCode((string) ($doc['postcode'] ?? '')),
                    'city' => (string) ($doc['woonplaatsnaam'] ?? ''),
                ])
                ->filter(fn (array $item) => $item['street'] !== '' && $item['postal_code'] !== '')
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    private static function formatPostalCode(string $postalCode): string
    {
        $postalCode = strtoupper(preg_replace('/\s+/', '', $postalCode) ?? '');

        return preg_match('/^(\d{4})([A-Z]{2})$/', $postalCode, $matches)
            ? $matches[1] . ' ' . $matches[2]
            : $postalCode;
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
