<?php

namespace App\Console\Commands;

use App\Models\HostProfile;
use App\Support\StripeService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

/*
 * Mislukt de koppeling, dan ziet een verhuurder alleen "probeer het later opnieuw" en
 * verdwijnt de echte reden in het log. Dit commando vraagt het Stripe gewoon zelf.
 */
class StripeCheck extends Command
{
    protected $signature = 'stripe:check {--host= : E-mailadres van een verhuurder om de onboarding echt te proberen}';

    protected $description = 'Controleer de Stripe-sleutels, de modus en of de Connect-onboarding werkt';

    public function handle(): int
    {
        $secret = (string) config('services.stripe.secret');
        $webhook = (string) config('services.stripe.webhook_secret');

        $this->table(['Instelling', 'Waarde'], [
            ['APP_ENV', app()->environment()],
            ['APP_URL', (string) config('app.url')],
            ['STRIPE_SECRET', $secret === '' ? '(leeg)' : $this->mode($secret)],
            ['STRIPE_WEBHOOK_SECRET', $webhook === '' ? '(leeg)' : 'ingesteld (' . count(explode(',', $webhook)) . ' stuks)'],
        ]);

        $this->urls();

        if (! StripeService::enabled()) {
            $this->error('STRIPE_SECRET is leeg. Zonder sleutel kan er niets gekoppeld worden.');

            return self::FAILURE;
        }

        if (! $this->platform()) {
            return self::FAILURE;
        }

        $this->connect();

        if ($email = $this->option('host')) {
            $this->onboarding($email);
        }

        return self::SUCCESS;
    }

    /*
     * Stripe weigert een terugkeer-URL die niet over https gaat of die naar een adres
     * wijst dat het zelf niet kan bereiken. Die URL bouwen wij uit APP_URL.
     */
    private function urls(): void
    {
        $url = (string) config('app.url');

        if (! str_starts_with($url, 'https://')) {
            $this->error("APP_URL staat op '{$url}'. Stripe eist https voor de terugkeer-URL's en weigert de koppeling anders.");
        }

        if (str_contains($url, 'plesk.page') || str_contains($url, '.test') || str_contains($url, 'localhost')) {
            $this->warn("APP_URL wijst naar '{$url}'. Stripe moet dat adres van buitenaf kunnen openen.");
        }

        $this->line('Terugkeer na onboarding: ' . route('host.stripe.return'));
    }

    private function mode(string $secret): string
    {
        return str_starts_with($secret, 'sk_live_') || str_starts_with($secret, 'rk_live_')
            ? 'LIVE'
            : 'test';
    }

    private function platform(): bool
    {
        $this->newLine();

        try {
            $account = StripeService::client()->accounts->retrieve();
        } catch (Throwable $e) {
            $this->error('Stripe weigert de sleutel: ' . $e->getMessage());

            return false;
        }

        $this->line('Platformaccount: ' . $account->id);
        $this->line('Land: ' . ($account->country ?? '?') . ', valuta: ' . strtoupper((string) ($account->default_currency ?? '?')));
        $this->line('Betalingen aan: ' . ($account->charges_enabled ? 'ja' : 'nee'));

        if (! $account->charges_enabled) {
            $this->warn('Zolang betalingen uitstaan is het account nog niet geactiveerd. Rond dat eerst af in het Stripe-dashboard.');
        }

        return true;
    }

    /*
     * De onboarding gebruikt de v2 Core Accounts API. Die staat niet op elk account aan,
     * en dat merk je pas op het moment dat een verhuurder op de knop drukt.
     */
    private function connect(): void
    {
        $this->newLine();

        try {
            StripeService::client()->v2->core->accounts->all(['limit' => 1]);
        } catch (Throwable $e) {
            $this->error('De Connect-onboarding werkt niet: ' . $e->getMessage());
            $this->warn('Dit is de reden die verhuurders te zien krijgen als "probeer het later opnieuw".');

            return;
        }

        $this->info('Connect-onboarding (v2 accounts) is beschikbaar.');
    }

    private function onboarding(string $email): void
    {
        $this->newLine();

        $profile = HostProfile::whereHas('user', fn ($query) => $query->where('email', $email))->first();

        if ($profile === null) {
            $this->error("Geen verhuurdersprofiel gevonden bij {$email}.");

            return;
        }

        $this->line("Onboarding proberen voor {$profile->name}...");

        $url = StripeService::onboardingUrl($profile);

        if ($url === null) {
            $this->error('Mislukt: ' . (StripeService::lastError() ?? 'onbekende fout'));

            return;
        }

        $this->info('Gelukt. Stripe gaf deze link terug:');
        $this->line(Str::limit($url, 120));
    }
}
