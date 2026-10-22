<?php

namespace Tests\Feature;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mail mag het verzoek nooit slopen, en mag ook nooit stilletjes blijven liggen.
 *
 * Eerst ging alles via de wachtrij, want registreren gaf een 500 zodra Mailgun de
 * daglimiet meldde. Dat loste de ene kant op en brak de andere: draait de scheduler op
 * de server niet, dan blijft de mail eindeloos staan zonder dat iemand het merkt.
 *
 * Nu doet MailDelivery allebei: direct versturen, en bij een weigering alsnog de
 * wachtrij in. De notificaties blijven dus ShouldQueue, want dat vangnet moet werken.
 * Dat gedrag zelf staat in AccountMailDeliveryTest en ContactDeliveryTest; hier bewaken
 * we alleen dat de onderdelen eronder op hun plaats blijven.
 */
class MailResilienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_notification_can_fall_back_to_the_queue(): void
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

        $this->assertSame([], $notQueued, 'Deze notificaties hebben geen vangnet: ' . implode(', ', $notQueued));
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

    public function test_the_lock_on_that_schedule_expires(): void
    {
        // Zonder vervaltijd houdt een afgebroken run de wachtrij 24 uur tegen.
        $event = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->first(fn ($event) => $event->command !== null && str_contains($event->command, 'queue:work'));

        $this->assertTrue($event->withoutOverlapping);
        $this->assertLessThanOrEqual(5, $event->expiresAt);
    }
}
