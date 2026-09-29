<?php

namespace Tests\Feature;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Registreren gaf een 500 zodra Mailgun de daglimiet meldde: het account was al
 * aangemaakt, maar de verificatiemail knalde er middenin uit. Mail hoort daarom via de
 * wachtrij te gaan, zodat een storing bij de provider nooit meer het verzoek raakt.
 */
class MailResilienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_puts_the_mail_on_the_queue_instead_of_sending_it_inline(): void
    {
        config(['queue.default' => 'database']);

        $this->post('/registreren', [
            'role' => 'artiest',
            'first_name' => 'Sam',
            'last_name' => 'de Wit',
            'email' => 'sam@voorbeeld.nl',
            'password' => 'Wachtwoord123',
            'password_confirmation' => 'Wachtwoord123',
            'terms' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'sam@voorbeeld.nl']);

        // De verificatiemail staat klaar in de wachtrij en is dus niet tijdens het
        // verzoek verstuurd. Daar kan de mailprovider het verzoek niet meer mee slopen.
        $this->assertGreaterThan(0, DB::table('jobs')->count());
    }

    public function test_a_password_reset_request_also_goes_through_the_queue(): void
    {
        config(['queue.default' => 'database']);

        \App\Models\User::factory()->create(['role' => 'artiest', 'email' => 'sam@voorbeeld.nl']);

        $this->post('/wachtwoord-vergeten', ['email' => 'sam@voorbeeld.nl']);

        $this->assertGreaterThan(0, DB::table('jobs')->count());
    }

    public function test_every_notification_is_queued(): void
    {
        $notQueued = [];

        foreach (glob(app_path('Notifications/*.php')) as $file) {
            $class = 'App\\Notifications\\' . basename($file, '.php');

            if (! class_exists($class)) {
                continue;
            }

            if (! is_subclass_of($class, ShouldQueue::class)) {
                $notQueued[] = class_basename($class);
            }
        }

        $this->assertSame([], $notQueued, 'Deze notificaties gaan nog buiten de wachtrij om: ' . implode(', ', $notQueued));
    }

    public function test_the_queue_is_drained_by_the_scheduler(): void
    {
        $commands = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->map(fn ($event) => $event->command);

        $this->assertTrue(
            $commands->contains(fn (?string $command) => $command !== null && str_contains($command, 'queue:work')),
            'Zonder geplande verwerking blijft de mail in de wachtrij staan.'
        );
    }
}
