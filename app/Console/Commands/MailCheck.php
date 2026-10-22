<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class MailCheck extends Command
{
    protected $signature = 'mail:check {--to= : Stuur ook een testmail naar dit adres}';

    protected $description = 'Controleer de mailconfiguratie, de wachtrij en de verbinding met Mailgun';

    public function handle(): int
    {
        $mailer = (string) config('mail.default');
        $domain = (string) config('services.mailgun.domain');
        $secret = (string) config('services.mailgun.secret');
        $endpoint = (string) config('services.mailgun.endpoint');

        $this->table(['Instelling', 'Waarde'], [
            ['APP_ENV', app()->environment()],
            ['APP_URL', (string) config('app.url')],
            ['MAIL_MAILER', $mailer],
            ['MAIL_FROM_ADDRESS', (string) config('mail.from.address')],
            ['CONTACT_EMAIL', (string) config('studio.contact_email')],
            ['MAILGUN_DOMAIN', $domain !== '' ? $domain : '(leeg)'],
            ['MAILGUN_SECRET', $secret !== '' ? 'ingesteld (' . strlen($secret) . ' tekens)' : '(leeg)'],
            ['MAILGUN_ENDPOINT', $endpoint],
            ['QUEUE_CONNECTION', (string) config('queue.default')],
        ]);

        if (config('studio.contact_email') === '') {
            $this->error('CONTACT_EMAIL is leeg: contactformulier-berichten gaan dan naar de adminaccounts in de database.');
        }

        if ($mailer === 'log') {
            $this->warn('MAIL_MAILER staat op "log": mail belandt in storage/logs en verlaat de server niet.');
        }

        $host = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
        $sender = Str::after((string) config('mail.from.address'), '@');

        // Op een ontwikkelmachine wijkt de hostnaam altijd af; daar zegt dit niets.
        if (app()->isProduction() && $host !== '' && $sender !== '' && ! str_ends_with($host, $sender)) {
            $this->warn("De afzender staat op '{$sender}' terwijl de site op '{$host}' draait. Dat struikelt over SPF en DKIM.");
        }

        if ($mailer === 'mailgun') {
            $this->checkMailgun($domain, $secret, $endpoint);
        }

        $this->checkQueue();

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

    /*
     * De instellingen kunnen tot in de puntjes kloppen terwijl er niets aankomt: dan
     * staat de mail nog in de wachtrij omdat de scheduler op de server niet draait.
     */
    private function checkQueue(): void
    {
        $this->newLine();

        try {
            $waiting = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();
        } catch (Throwable $e) {
            $this->error('De wachtrijtabellen zijn niet leesbaar: ' . $e->getMessage());

            return;
        }

        $this->line("Wachtrij: {$waiting} klaarstaand, {$failed} mislukt.");

        if ($waiting > 0) {
            $this->warn('Er staat werk klaar. Loopt dat niet terug, dan draait de cron op php artisan schedule:run niet.');
        }

        if ($failed > 0) {
            $this->line('Laatste fout: ' . Str::limit((string) DB::table('failed_jobs')->latest('failed_at')->value('exception'), 500));
        }

        $locks = DB::table('cache_locks')->where('key', 'like', '%schedule%')->pluck('key');

        if ($locks->isNotEmpty()) {
            $this->warn('Er ligt nog een slot op de geplande verwerking: ' . $locks->implode(', '));
            $this->warn('Opruimen met php artisan cache:clear, anders blijft de wachtrij staan.');
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
