<?php

namespace Tests\Feature;

use App\Notifications\ContactConfirmation;
use App\Notifications\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Google reCAPTCHA v2 op het contactformulier. De controle mag spam tegenhouden, maar
 * nooit een bezoeker met een echte vraag buitensluiten door een storing of een verkeerd
 * ingevulde sleutel aan onze kant.
 */
class ContactRecaptchaTest extends TestCase
{
    use RefreshDatabase;

    private const MESSAGE = [
        'name' => 'Sam de Wit',
        'email' => 'sam@voorbeeld.nl',
        'subject' => 'general',
        'message' => 'Ik heb een vraag over het boeken van een studio.',
    ];

    private function withKeys(): void
    {
        config([
            'services.recaptcha.site_key' => 'test-site-key',
            'services.recaptcha.secret' => 'test-secret',
        ]);
    }

    private function googleSays(bool $success, array $errors = []): void
    {
        Http::fake([
            'www.google.com/recaptcha/*' => Http::response(array_filter([
                'success' => $success,
                'error-codes' => $errors ?: null,
            ])),
        ]);
    }

    private function send(array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->post('/contact', array_merge(self::MESSAGE, $extra));
    }

    public function test_without_keys_the_form_works_as_before(): void
    {
        Notification::fake();
        Http::fake();

        config(['services.recaptcha.site_key' => null, 'services.recaptcha.secret' => null]);

        $this->send()->assertRedirect(route('contact'))->assertSessionHasNoErrors();

        Notification::assertSentOnDemand(ContactMessage::class);
        Http::assertNothingSent();
    }

    public function test_without_keys_no_google_script_is_loaded(): void
    {
        config(['services.recaptcha.site_key' => null, 'services.recaptcha.secret' => null]);

        $this->get('/contact')
            ->assertOk()
            ->assertDontSee('google.com/recaptcha')
            ->assertDontSee('g-recaptcha', false);
    }

    public function test_with_keys_the_widget_appears_on_the_contact_page(): void
    {
        $this->withKeys();

        $this->get('/contact')
            ->assertOk()
            ->assertSee('class="g-recaptcha"', false)
            ->assertSee('data-sitekey="test-site-key"', false)
            ->assertSee('https://www.google.com/recaptcha/api.js', false);
    }

    public function test_the_widget_is_only_on_the_contact_page(): void
    {
        $this->withKeys();

        foreach (['/', '/studios', '/faq'] as $path) {
            $this->get($path)->assertOk()->assertDontSee('google.com/recaptcha/api.js');
        }
    }

    public function test_a_submission_without_a_token_is_rejected(): void
    {
        Notification::fake();
        Http::fake();
        $this->withKeys();

        $this->send()->assertSessionHasErrors('g-recaptcha-response');

        Notification::assertNothingSent();
        Http::assertNothingSent();
    }

    public function test_a_valid_token_lets_the_message_through(): void
    {
        Notification::fake();
        $this->withKeys();
        $this->googleSays(true);

        $this->send(['g-recaptcha-response' => 'een-geldig-token'])
            ->assertRedirect(route('contact'))
            ->assertSessionHasNoErrors();

        Notification::assertSentOnDemand(ContactMessage::class);
        Notification::assertSentOnDemand(ContactConfirmation::class);

        Http::assertSent(fn ($request) => $request['secret'] === 'test-secret'
            && $request['response'] === 'een-geldig-token');
    }

    public function test_a_token_google_rejects_stops_the_message(): void
    {
        Notification::fake();
        $this->withKeys();
        $this->googleSays(false, ['invalid-input-response']);

        $this->send(['g-recaptcha-response' => 'vervalst-token'])
            ->assertSessionHasErrors('g-recaptcha-response');

        Notification::assertNothingSent();
    }

    public function test_a_reused_token_stops_the_message(): void
    {
        Notification::fake();
        $this->withKeys();
        $this->googleSays(false, ['timeout-or-duplicate']);

        $this->send(['g-recaptcha-response' => 'al-gebruikt'])
            ->assertSessionHasErrors('g-recaptcha-response');

        Notification::assertNothingSent();
    }

    public function test_google_being_unreachable_does_not_block_a_real_visitor(): void
    {
        Notification::fake();
        Log::spy();
        $this->withKeys();

        Http::fake(fn () => throw new ConnectionException('kon google niet bereiken'));

        $this->send(['g-recaptcha-response' => 'token'])
            ->assertRedirect(route('contact'))
            ->assertSessionHasNoErrors();

        Notification::assertSentOnDemand(ContactMessage::class);
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_a_wrong_secret_does_not_block_a_real_visitor_but_is_logged(): void
    {
        Notification::fake();
        Log::spy();
        $this->withKeys();
        $this->googleSays(false, ['invalid-input-secret']);

        $this->send(['g-recaptcha-response' => 'token'])
            ->assertRedirect(route('contact'))
            ->assertSessionHasNoErrors();

        Notification::assertSentOnDemand(ContactMessage::class);
        Log::shouldHaveReceived('error')->once();
    }

    public function test_the_token_never_ends_up_in_the_email(): void
    {
        Notification::fake();
        $this->withKeys();
        $this->googleSays(true);

        $this->send(['g-recaptcha-response' => 'geheim-token']);

        Notification::assertSentOnDemand(
            ContactMessage::class,
            fn (ContactMessage $notification) => ! array_key_exists('g-recaptcha-response', $notification->data)
        );
    }

    public function test_the_visitor_ip_is_passed_along_to_google(): void
    {
        Notification::fake();
        $this->withKeys();
        $this->googleSays(true);

        $this->send(['g-recaptcha-response' => 'token']);

        Http::assertSent(fn ($request) => filled($request['remoteip']));
    }
}
