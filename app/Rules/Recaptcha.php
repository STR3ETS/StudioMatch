<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Controleert het antwoord van Google reCAPTCHA v2 bij Google zelf.
 *
 * Zonder sleutels doet deze regel niets. Dat is bewust: lokaal en in de tests draait de
 * site dan gewoon door, en het formulier valt niet stil zodra iemand vergeet de sleutels
 * in te vullen op een nieuwe omgeving.
 */
class Recaptcha implements ValidationRule
{
    /** Fouten die aan de bezoeker liggen: die mag hij overdoen. */
    private const VISITOR_ERRORS = [
        'missing-input-response',
        'invalid-input-response',
        'timeout-or-duplicate',
        'bad-request',
    ];

    public function __construct(private ?string $ip = null) {}

    public static function enabled(): bool
    {
        return filled(config('services.recaptcha.site_key'))
            && filled(config('services.recaptcha.secret'));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::enabled()) {
            return;
        }

        if (! is_string($value) || trim($value) === '') {
            $fail(__('contact.form.recaptcha_error'));

            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post(config('services.recaptcha.verify_url'), [
                    'secret' => config('services.recaptcha.secret'),
                    'response' => $value,
                    'remoteip' => $this->ip,
                ]);
        } catch (Throwable $exception) {
            // Google onbereikbaar. Een bezoeker met een echte vraag hoort daar geen last
            // van te hebben, dus die laten we door en we leggen het vast.
            Log::warning('reCAPTCHA niet bereikbaar, bericht doorgelaten.', ['error' => $exception->getMessage()]);

            return;
        }

        $result = $response->json();

        if (($result['success'] ?? false) === true) {
            return;
        }

        $errors = (array) ($result['error-codes'] ?? []);

        if (array_intersect($errors, self::VISITOR_ERRORS) !== []) {
            $fail(__('contact.form.recaptcha_error'));

            return;
        }

        /*
         * Wat hier overblijft is een verkeerde of ontbrekende sleutel, dus een fout aan
         * onze kant. Dan de bezoeker blokkeren zou betekenen dat niemand nog contact kan
         * opnemen terwijl wij van niets weten. Doorlaten en luid loggen is beter.
         */
        Log::error('reCAPTCHA is verkeerd ingesteld, bericht doorgelaten.', ['error-codes' => $errors]);
    }
}
