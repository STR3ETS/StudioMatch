<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * One half of a name: at least two letters and no digits. Apostrophes, hyphens
 * and spaces are fine, so "van 't Hof" passes as an achternaam.
 */
class NamePart implements ValidationRule
{

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = trim((string) $value);
        $letters = (string) preg_replace('/[^\p{L}]/u', '', $value);

        if (preg_match('/\d/u', $value) || mb_strlen($letters) < 2) {
            $fail(__('auth.fields.name_part_invalid'));
        }
    }
}
