<?php

namespace App\Console\Commands;

use App\Models\Studio;
use App\Support\Geocoder;
use Illuminate\Console\Command;

class GeocodeStudios extends Command
{
    protected $signature = 'studios:geocode {--force : Ook studio\'s die al coördinaten hebben opnieuw geocoden}';

    protected $description = 'Geocodeer studio-adressen via de PDOK Locatieserver';

    public function handle(): int
    {
        $studios = Studio::query()
            ->when(! $this->option('force'), fn ($query) => $query->whereNull('lat'))
            ->get();

        foreach ($studios as $studio) {
            $coords = Geocoder::geocode($studio->street, $studio->postal_code, $studio->city);

            if ($coords === null) {
                $this->warn("Geen resultaat voor \"{$studio->name}\" ({$studio->fullAddress()}).");

                continue;
            }

            $studio->update($coords);
            $this->info("\"{$studio->name}\" → {$coords['lat']}, {$coords['lng']}");
        }

        return self::SUCCESS;
    }
}
