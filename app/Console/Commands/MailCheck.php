<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailCheck extends Command
{
    protected $signature = 'mail:check {--to= : Stuur ook een testmail naar dit adres}';

    protected $description = 'Controleer de mailconfiguratie (Mailgun-domein, API-key en contactadres)';

    public function handle(): int
    {
        $mailer = (string) config('mail.default');
        $domain = (string) config('services.mailgun.domain');
        $secret = (string) config('services.mailgun.secret');
        $endpoint = (string) config('services.mailgun.endpoint');

        $this->table(['Instelling', 'Waarde'], [
            ['MAIL_MAILER', $mailer],
            ['MAIL_FROM_ADDRESS', (string) config('mail.from.address')],
            ['CONTACT_EMAIL', (string) config('studio.contact_email')],
            ['MAILGUN_DOMAIN', $domain !== '' ? $domain : '(leeg)'],
            ['MAILGUN_SECRET', $secret !== '' ? 'ingesteld (' . strlen($secret) . ' tekens)' : '(leeg)'],
            ['MAILGUN_ENDPOINT', $endpoint],
        ]);

        if (config('studio.contact_email') === '') {
            $this->error('CONTACT_EMAIL is leeg: contactformulier-berichten gaan dan naar de adminaccounts in de database.');
        }

        if ($mailer === 'mailgun') {
            $this->checkMailgun($domain, $secret, $endpoint);
        }

        if ($to = $this->option('to')) {
            $this->sendTest($to);
        }

        return self::SUCCESS;
    }

    private function checkMailgun(string $domain, string $secret, string $endpoint): void
    {
        if ($domain === '' || $secret === '') {
            $this->error('MAILGUN_DOMAIN en MAILGUN_SECRET zijn allebei nodig. De secret is de private (sending) API key uit Mailgun.');

            return;
        }

        try {
            $response = Http::withBasicAuth('api', $secret)
                ->timeout(10)
                ->get("https://{$endpoint}/v3/domains/{$domain}");
        } catch (Throwable $e) {
            $this->error('Kon Mailgun niet bereiken: ' . $e->getMessage());

            return;
        }

        if ($response->status() === 401) {
            $this->error('Mailgun weigert de API-key (401). Gebruik de private API key of een sending key van het juiste account.');

            return;
        }

        if ($response->status() === 404) {
            $this->error("Mailgun kent het domein \"{$domain}\" niet op {$endpoint}. Let op: EU-accounts gebruiken api.eu.mailgun.net.");

            return;
        }

        if (! $response->successful()) {
            $this->error('Mailgun antwoordde met status ' . $response->status() . '.');

            return;
        }

        $state = (string) $response->json('domain.state');
        $this->line("Mailgun-domein \"{$domain}\": {$state}");

        if ($state !== 'active') {
            $this->warn('Het domein is nog niet geverifieerd. Zet de DNS-records (SPF, DKIM en de tracking-CNAME) klaar bij de domeinprovider.');
        }

        foreach ((array) $response->json('sending_dns_records') as $record) {
            if (($record['valid'] ?? '') !== 'valid') {
                $this->warn('DNS nog niet in orde: ' . ($record['record_type'] ?? '?') . ' ' . ($record['name'] ?? ''));
            }
        }
    }

    private function sendTest(string $to): void
    {
        try {
            Mail::raw('Testmail vanuit StudioMatch (' . config('app.env') . ').', function ($message) use ($to) {
                $message->to($to)->subject('StudioMatch mailtest');
            });
            $this->info("Testmail verstuurd naar {$to}.");
        } catch (Throwable $e) {
            $this->error('Versturen mislukt: ' . $e->getMessage());
        }
    }
}
