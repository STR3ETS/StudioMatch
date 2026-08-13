<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Models\Room;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Demodata voor presentaties en staging: studio's met live ruimtes,
 * beschikbaarheid en boekingen in verschillende statussen.
 * Idempotent: draait alleen als de demodata nog niet bestaat.
 */
class DemoSeeder extends Seeder
{
    private const DEMO_PHOTOS = [
        '/temp-studio-1.webp',
        '/temp-studio-2.webp',
        '/temp-studio-3.jpg',
        '/temp-studio-4.jpg',
        '/temp-studio-5.jpg',
    ];

    public function run(): void
    {
        if (User::where('email', 'demo-verhuurder@studiomatch.test')->exists()) {
            $this->command->info('Demodata bestaat al, seeder overgeslagen.');

            return;
        }

        // Vaste testaccounts (bestaan mogelijk al in lokale omgevingen).
        User::firstOrCreate(['email' => 'admin@studiomatch.test'], [
            'name' => 'Test Admin', 'password' => 'wachtwoord123', 'role' => 'admin',
        ]);
        User::firstOrCreate(['email' => 'verhuurder@studiomatch.test'], [
            'name' => 'Test Verhuurder', 'password' => 'wachtwoord123', 'role' => 'verhuurder',
        ]);
        $artist = User::firstOrCreate(['email' => 'artiest@studiomatch.test'], [
            'name' => 'Test Artiest', 'password' => 'wachtwoord123', 'role' => 'artiest',
        ]);

        // Demo-verhuurder met bedrijfsprofiel en drie studio's.
        $host = User::create([
            'name' => 'Demi Demeester',
            'email' => 'demo-verhuurder@studiomatch.test',
            'password' => 'wachtwoord123',
            'role' => 'verhuurder',
        ]);

        $host->hostProfile()->create([
            'name' => 'Demeester Studio\'s B.V.',
            'phone' => '020 1234567',
            'owner_type' => 'ondernemer',
            'btw_plichtig' => true,
            'kvk_number' => '87654321',
            'vat_number' => 'NL867654321B01',
        ]);

        $artists = collect([
            ['name' => 'Sam de Wit', 'email' => 'demo-sam@studiomatch.test'],
            ['name' => 'Nora Willems', 'email' => 'demo-nora@studiomatch.test'],
        ])->map(fn ($data) => User::create([...$data, 'password' => 'wachtwoord123', 'role' => 'artiest']));

        $studios = [
            [
                'studio' => ['name' => 'Redlight Recordings', 'phone' => '020 1234567', 'street' => 'Prinsengracht 263', 'postal_code' => '1016 GV', 'city' => 'Amsterdam', 'lat' => 52.3752, 'lng' => 4.8840],
                'rooms' => [
                    ['title' => 'Live Room A', 'type' => 'opname', 'rate' => 5500, 'capacity' => 8, 'engineer' => true, 'status' => 'live',
                        'description' => "Ruime live room met akoestische behandeling en een aparte controlroom. Ideaal voor bands, vocals en volledige producties.\n\nInclusief ervaren engineer die met je meedenkt."],
                    ['title' => 'Mix Suite', 'type' => 'mix_master', 'rate' => 4000, 'capacity' => 3, 'engineer' => false, 'status' => 'live',
                        'description' => 'Compacte mixstudio met uitstekende monitoring. Perfect voor mix- en mastersessies.'],
                ],
            ],
            [
                'studio' => ['name' => 'Havenklank', 'phone' => '010 7654321', 'street' => 'Maashaven Oostzijde 155', 'postal_code' => '3072 HS', 'city' => 'Rotterdam', 'lat' => 51.8990, 'lng' => 4.4930],
                'rooms' => [
                    ['title' => 'Studio Noord', 'type' => 'opname', 'rate' => 3500, 'capacity' => 5, 'engineer' => false, 'status' => 'live',
                        'description' => 'Sfeervolle opnamestudio in een oud havenpakhuis. Warme akoestiek en veel analoge apparatuur.'],
                ],
            ],
            [
                'studio' => ['name' => 'Strijp Sound', 'phone' => null, 'street' => 'Torenallee 45', 'postal_code' => '5617 BA', 'city' => 'Eindhoven', 'lat' => 51.4480, 'lng' => 5.4560],
                'rooms' => [
                    ['title' => 'De Machinekamer', 'type' => 'opname', 'rate' => 4500, 'capacity' => 6, 'engineer' => true, 'status' => 'live',
                        'description' => 'Industriële opnamestudio op Strijp-S met hoge plafonds en een royale opnameruimte.'],
                    ['title' => 'Master Lab', 'type' => 'mix_master', 'rate' => 6000, 'capacity' => 2, 'engineer' => true, 'status' => 'in_review',
                        'description' => 'Hoogwaardige masteringstudio met geoptimaliseerde akoestiek. Nog in review als demo voor de goedkeuringswachtrij.'],
                ],
            ],
        ];

        $liveRooms = collect();

        foreach ($studios as $data) {
            $studio = $host->studios()->create($data['studio']);

            foreach ($data['rooms'] as $roomData) {
                $room = $studio->rooms()->create([
                    'title' => $roomData['title'],
                    'description' => $roomData['description'],
                    'type' => $roomData['type'],
                    'hourly_rate_cents' => $roomData['rate'],
                    'min_hours' => 2,
                    'capacity' => $roomData['capacity'],
                    'engineer_included' => $roomData['engineer'],
                    'house_rules' => "Maximaal {$roomData['capacity']} personen in de studio\nNiet roken in de opnameruimte\nEigen apparatuur in overleg",
                    'equipment' => ['mic_condenser', 'mic_dynamic', 'monitors', 'midi61', 'guitar_acoustic'],
                    'equipment_extra' => 'Neumann U87, SSL-console',
                    'daws' => ['Logic', 'Pro Tools', 'Ableton'],
                    'facilities' => ['wifi', 'parking', 'coffee', 'ac'],
                    'status' => $roomData['status'],
                ]);

                $room->seedDefaultHours();
                $this->seedPhotos($room);

                if ($roomData['status'] === 'live') {
                    $liveRooms->push($room);
                }
            }
        }

        // Boekingen in verschillende statussen voor de demo.
        $this->seedBooking($liveRooms[0], $artist, today()->addDays(3), 14, 17, BookingStatus::PendingConfirmation);
        $this->seedBooking($liveRooms[0], $artists[0], today()->addDays(5), 10, 13, BookingStatus::Confirmed);
        $this->seedBooking($liveRooms[1], $artists[1], today()->addDays(7), 12, 16, BookingStatus::Confirmed);
        $this->seedBooking($liveRooms[2], $artist, today()->subDays(6), 10, 14, BookingStatus::Completed);
        $this->seedBooking($liveRooms[3], $artists[0], today()->subDays(2), 18, 21, BookingStatus::Completed);

        $this->command->info('Demodata aangemaakt: 1 verhuurder, 3 studio\'s, 5 ruimtes en 5 boekingen.');
    }

    private function seedPhotos(Room $room): void
    {
        foreach (self::DEMO_PHOTOS as $index => $photo) {
            $source = public_path($photo);

            if (! File::exists($source)) {
                continue;
            }

            $stored = \App\Support\ImageProcessor::store(File::get($source), 'rooms/' . $room->id);

            if ($stored === null) {
                $path = 'rooms/' . $room->id . '/demo-' . ($index + 1) . '.' . pathinfo($photo, PATHINFO_EXTENSION);
                Storage::disk('public')->put($path, File::get($source));
                $stored = ['path' => $path, 'thumb_path' => null];
            }

            $room->photos()->create([...$stored, 'sort_order' => $index]);
        }
    }

    private function seedBooking(Room $room, User $artist, $date, int $start, int $end, BookingStatus $status): void
    {
        $rent = $room->hourly_rate_cents * ($end - $start);
        $fee = (int) round($rent * config('studio.service_fee_percent') / 100);
        $vat = (int) round($fee * config('studio.vat_percent') / 100);

        $room->bookings()->create([
            'user_id' => $artist->id,
            'date' => $date,
            'start_hour' => $start,
            'end_hour' => $end,
            'hourly_rate_cents' => $room->hourly_rate_cents,
            'rent_cents' => $rent,
            'service_fee_cents' => $fee,
            'vat_cents' => $vat,
            'total_cents' => $rent + $fee + $vat,
            'status' => $status,
            'terms_accepted_at' => now()->subDays(8),
            'requested_at' => now()->subDays(8),
            'confirmed_at' => in_array($status, [BookingStatus::Confirmed, BookingStatus::Completed], true) ? now()->subDays(7) : null,
        ]);
    }
}
