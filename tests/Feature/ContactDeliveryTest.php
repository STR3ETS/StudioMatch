<?php

namespace Tests\Feature;

use App\Notifications\ContactConfirmation;
use App\Notifications\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

/**
 * Een vraag via het contactformulier mag nooit stil blijven liggen.
 *
 * Alle notificaties gaan via de wachtrij, maar die wordt op de server pas verwerkt door
 * de scheduler. Draait die niet, dan zou een contactvraag eindeloos blijven staan zonder
 * dat iemand het merkt. Daarom gaat deze mail meteen de deur uit, met de wachtrij als
 * vangnet wanneer dat niet lukt.
 */
class ContactDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private const MESSAGE = [
        'name' => 'Sam de Wit',
        'email' => 'sam@voorbeeld.nl',
        'subject' => 'general',
        'message' => 'Ik heb een vraag over het boeken van een studio.',
    ];

    public function test_both_mails_go_out_on_a_normal_submission(): void
    {
        Notification::fake();
        config(['studio.contact_email' => 'info@studiomatch.nl']);

        $this->post('/contact', self::MESSAGE)
            ->assertRedirect(route('contact'))
            ->assertSessionHasNoErrors();

        Notification::assertSentOnDemand(
            ContactMessage::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'info@studiomatch.nl'
        );

        Notification::assertSentOnDemand(
            ContactConfirmation::class,
            fn ($notification, $channels, $notifiable) => array_key_exists('sam@voorbeeld.nl', $notifiable->routes['mail'])
        );
    }

    public function test_the_mail_is_sent_straight_away_and_not_parked_in_the_queue(): void
    {
        config(['queue.default' => 'database', 'studio.contact_email' => 'info@studiomatch.nl']);

        $this->mock(ChannelManager::class, function ($mock) {
            // Direct versturen, niet via de wachtrij.
            $mock->shouldReceive('sendNow')->twice();
            $mock->shouldReceive('send')->never();
        });

        $this->post('/contact', self::MESSAGE)->assertRedirect(route('contact'));

        $this->assertSame(0, \Illuminate\Support\Facades\DB::table('jobs')->count());
    }

    public function test_a_refusal_by_the_mail_provider_does_not_lose_the_message(): void
    {
        Log::spy();
        config(['studio.contact_email' => 'info@studiomatch.nl']);

        $this->mock(ChannelManager::class, function ($mock) {
            $mock->shouldReceive('sendNow')->twice()->andThrow(new RuntimeException('mailprovider weigert'));

            // Alsnog in de wachtrij, zodat het later opnieuw geprobeerd wordt.
            $mock->shouldReceive('send')->twice();
        });

        $this->post('/contact', self::MESSAGE)
            ->assertRedirect(route('contact'))
            ->assertSessionHasNoErrors();

        Log::shouldHaveReceived('error')->twice();
    }

    public function test_without_a_configured_address_the_admins_receive_the_message(): void
    {
        Notification::fake();
        config(['studio.contact_email' => '']);

        $admin = \App\Models\User::factory()->create(['role' => 'admin']);

        $this->post('/contact', self::MESSAGE)->assertRedirect(route('contact'));

        Notification::assertSentTo($admin, ContactMessage::class);
    }
}
