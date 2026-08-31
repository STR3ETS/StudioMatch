<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Requires a real first and last name: at least two parts, with the first and
 * the last part each holding two or more letters. Tussenvoegsels ("van 't")
 * are allowed in between.
 */
class FullName implements ValidationRule
{

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parts = preg_split('/\s+/u', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($parts) < 2 || preg_match('/\d/u', (string) $value)) {
            $fail(__('auth.fields.name_invalid'));

            return;
        }

        $letters = fn (string $part) => mb_strlen((string) preg_replace('/[^\p{L}]/u', '', $part));

        if ($letters($parts[0]) < 2 || $letters($parts[count($parts) - 1]) < 2) {
            $fail(__('auth.fields.name_invalid'));
        }
    }
}
