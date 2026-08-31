<?php

namespace App\Http\Controllers\Concerns;

use App\Support\Geocoder;
use Illuminate\Validation\ValidationException;

trait VerifiesAddress
{

    /**
     * Check street, postcode and city against the Dutch address register. Returns the
     * coordinates on a match, null when the register is unreachable, and throws a
     * validation error when the address simply does not exist.
     */
    protected function verifiedCoords(array $address, string $message): ?array
    {
        $result = Geocoder::verify($address['street'], $address['postal_code'], $address['city']);

        if ($result === false) {
            throw ValidationException::withMessages(['street' => $message]);
        }

        return $result;
    }
}
