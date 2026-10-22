<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordQueued;
use App\Notifications\VerifyEmailQueued;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

/**
 * Op een verificatiemail en een herstelmail staat iemand te wachten.
 *
 * Alle notificaties staan op ShouldQueue en de wachtrij wordt op de server pas verwerkt
 * door de scheduler. Draait die niet, dan verdwijnen deze twee geruisloos: de bezoeker
 * ziet een vriendelijke bevestiging, maar er komt nooit iets binnen. Daarom gaan ze
 * direct de deur uit, met de wachtrij als vangnet.
 */
class AccountMailDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private const REGISTRATION = [
        'role' => 'artiest',
        'first_name' => 'Noah',
        'last_name' => 'Tester',
        'email' => 'noah@voorbeeld.nl',
        'password' => 'Wachtwoord123',
        'password_confirmation' => 'Wachtwoord123',
        'terms' => '1',
    ];

    public function test_the_verification_mail_leaves_immediately_instead_of_waiting_for_the_queue(): void
    {
        config(['queue.default' => 'database']);

        $this->mock(ChannelManager::class, function ($mock) {
            $mock->shouldReceive('sendNow')->once();
            $mock->shouldReceive('send')->never();
        });

        $this->post('/registreren', self::REGISTRATION);

        $this->assertSame(0, DB::table('jobs')->count());
    }

    public function test_the_password_reset_mail_leaves_immediately_too(): void
    {
        config(['queue.default' => 'database']);

        User::factory()->create(['email' => 'noah@voorbeeld.nl']);

        $this->mock(ChannelManager::class, function ($mock) {
            $mock->shouldReceive('sendNow')->once();
            $mock->shouldReceive('send')->never();
        });

        $this->post('/wachtwoord-vergeten', ['email' => 'noah@voorbeeld.nl']);

        $this->assertSame(0, DB::table('jobs')->count());
    }

    public function test_a_refusal_by_the_mail_provider_still_creates_the_account(): void
    {
        Log::spy();

        $this->mock(ChannelManager::class, function ($mock) {
            $mock->shouldReceive('sendNow')->once()->andThrow(new RuntimeException('mailprovider weigert'));

            // Alsnog in de wachtrij, zodat het later opnieuw geprobeerd wordt.
            $mock->shouldReceive('send')->once();
        });

        $this->post('/registreren', self::REGISTRATION)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'noah@voorbeeld.nl']);
        Log::shouldHaveReceived('error')->once();
    }

    public function test_both_notifications_still_reach_the_right_person(): void
    {
        Notification::fake();

        $this->post('/registreren', self::REGISTRATION);

        $user = User::where('email', 'noah@voorbeeld.nl')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmailQueued::class);

        // Na registreren ben je ingelogd, en het herstelformulier is alleen voor gasten.
        Auth::logout();

        $this->post('/wachtwoord-vergeten', ['email' => 'noah@voorbeeld.nl']);

        Notification::assertSentTo($user, ResetPasswordQueued::class);
    }
}
