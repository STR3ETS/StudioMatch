<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Zet de teksten van de app (lang/{nl,en}/app.php, deel 'ui') als JSON in de app-build, zodat het
 * inlogscherm ook bij de allereerste start zonder verbinding teksten heeft. De app haalt daarna de
 * actuele teksten van de server (api/app/teksten); de langbestanden blijven de enige bron.
 */
class ExportAppTexts extends Command
{
    protected $signature = 'app:texts';

    protected $description = 'Teksten van de iOS/Android-app naar mobile/app/texts.json (draait mee met npm run app:build)';

    public function handle(): int
    {
        $texts = collect(array_keys(config('localization.supported', [])))
            ->mapWithKeys(fn (string $locale) => [$locale => trans('app.ui', [], $locale)])
            ->all();

        file_put_contents(base_path('mobile/app/texts.json'), json_encode($texts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->info('mobile/app/texts.json bijgewerkt ('.implode(', ', array_keys($texts)).').');

        return self::SUCCESS;
    }
}
