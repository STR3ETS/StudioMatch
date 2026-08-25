<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DemoSeeder extends Seeder
{
    private const DEMO_PHOTOS = [
        '/temp-studio-1.webp',
        '/temp-studio-2.webp',
        '/temp-studio-3.jpg',
        '/temp-studio-4.jpg',
        '/temp-studio-5.jpg',
    ];

    private const EQUIPMENT_SETS = [
        ['mic_condenser', 'mic_dynamic', 'monitors', 'midi61', 'guitar_acoustic'],
        ['mic_condenser', 'monitors', 'piano', 'midi88'],
        ['mic_dynamic', 'monitors', 'drums', 'bass', 'guitar_electric'],
        ['mic_condenser', 'mic_usb', 'monitors', 'midi49'],
    ];

    private const DAW_SETS = [
        ['Logic', 'Pro Tools', 'Ableton'],
        ['Pro Tools', 'Cubase'],
        ['Ableton', 'FL Studio'],
        ['Logic', 'Studio One'],
    ];

    private const FACILITY_SETS = [
        ['wifi', 'parking', 'coffee', 'ac'],
        ['wifi', 'kitchen', 'coffee', 'fridge'],
        ['wifi', 'coffee', 'ac'],
        ['wifi', 'parking', 'kitchen', 'coffee', 'microwave'],
    ];

    public function run(): void
    {
        if (User::where('email', 'demo-verhuurder@studiomatch.test')->exists()) {
            $this->command->info('Demodata bestaat al, seeder overgeslagen. Draai php artisan migrate:fresh --seed voor een schone demoset.');

            return;
        }

        User::firstOrCreate(['email' => 'admin@studiomatch.test'], [
            'name' => 'Test Admin', 'password' => 'wachtwoord123', 'role' => 'admin',
        ]);
        User::firstOrCreate(['email' => 'verhuurder@studiomatch.test'], [
            'name' => 'Test Verhuurder', 'password' => 'wachtwoord123', 'role' => 'verhuurder',
        ]);
        $artist = User::firstOrCreate(['email' => 'artiest@studiomatch.test'], [
            'name' => 'Test Artiest', 'password' => 'wachtwoord123', 'role' => 'artiest',
        ]);

        [$sam, $nora, $yusuf] = collect([
            ['name' => 'Sam de Wit', 'email' => 'demo-sam@studiomatch.test'],
            ['name' => 'Nora Willems', 'email' => 'demo-nora@studiomatch.test'],
            ['name' => 'Yusuf Demir', 'email' => 'demo-yusuf@studiomatch.test'],
        ])->map(fn ($data) => User::create([...$data, 'password' => 'wachtwoord123', 'role' => 'artiest']))->all();

        $demi = $this->host('Demi Demeester', 'demo-verhuurder@studiomatch.test', "Demeester Studio's B.V.", '020 1234567', 'ondernemer', true, '87654321', 'NL867654321B01');
        $jesse = $this->host('Jesse van Dam', 'demo-jesse@studiomatch.test', 'Jesse van Dam', '030 2345678', 'particulier', false, null, null);
        $fatima = $this->host('Fatima el Idrissi', 'demo-fatima@studiomatch.test', 'El Idrissi Audio', '070 3456789', 'ondernemer', true, '65432109', 'NL865432109B01');

        $plan = [
            [$demi, ['name' => 'Redlight Recordings', 'phone' => '020 1234567', 'street' => 'Prinsengracht 263', 'postal_code' => '1016 GV', 'city' => 'Amsterdam', 'lat' => 52.3752, 'lng' => 4.8840], [
                ['key' => 'liveA', 'title' => 'Live Room A', 'type' => 'opname', 'rate' => 5500, 'capacity' => 8, 'engineer' => true, 'status' => 'live',
                    'description' => "Ruime live room met akoestische behandeling en een aparte controlroom. Ideaal voor bands, vocals en volledige producties.\n\nInclusief ervaren engineer die met je meedenkt."],
                ['key' => 'mix', 'title' => 'Mix Suite', 'type' => 'mix', 'rate' => 4000, 'capacity' => 3, 'engineer' => false, 'status' => 'live',
                    'description' => 'Compacte mixstudio met uitstekende monitoring. Perfect voor mix- en mastersessies.'],
            ]],
            [$demi, ['name' => 'Noorderlicht Audio', 'phone' => '020 9876543', 'street' => 'NDSM-plein 28', 'postal_code' => '1033 WB', 'city' => 'Amsterdam', 'lat' => 52.4010, 'lng' => 4.8940], [
                ['title' => 'De Vuurtoren', 'type' => 'opname', 'rate' => 3800, 'capacity' => 6, 'engineer' => false, 'status' => 'afgekeurd',
                    'rejection_reason' => "De foto's zijn te donker en de omschrijving zegt nog niets over de akoestiek. Vul dit aan en dien de ruimte opnieuw in.",
                    'description' => 'Opnamestudio op de NDSM-werf met uitzicht over het IJ.'],
            ]],
            [$demi, ['name' => 'Havenklank', 'phone' => '010 7654321', 'street' => 'Maashaven Oostzijde 155', 'postal_code' => '3072 HS', 'city' => 'Rotterdam', 'lat' => 51.8990, 'lng' => 4.4930], [
                ['key' => 'noord', 'title' => 'Studio Noord', 'type' => 'opname', 'rate' => 3500, 'capacity' => 5, 'engineer' => false, 'engineer_rate' => 1500, 'status' => 'live',
                    'description' => 'Sfeervolle opnamestudio in een oud havenpakhuis. Warme akoestiek en veel analoge apparatuur. Op verzoek boek je een ervaren engineer bij je sessie.'],
                ['title' => 'Vocal Booth', 'type' => 'opname', 'rate' => 2500, 'capacity' => 2, 'engineer' => false, 'status' => 'live', 'vacation' => true,
                    'description' => 'Compacte booth voor vocals en voice-overs. Snel geboekt, snel klaar.'],
            ]],
            [$demi, ['name' => 'Strijp Sound', 'phone' => null, 'street' => 'Torenallee 45', 'postal_code' => '5617 BA', 'city' => 'Eindhoven', 'lat' => 51.4480, 'lng' => 5.4560], [
                ['key' => 'machine', 'title' => 'De Machinekamer', 'type' => 'opname', 'rate' => 4500, 'capacity' => 6, 'engineer' => true, 'status' => 'live',
                    'description' => 'Industriële opnamestudio op Strijp-S met hoge plafonds en een royale opnameruimte.'],
                ['title' => 'Master Lab', 'type' => 'master', 'rate' => 6000, 'capacity' => 2, 'engineer' => true, 'status' => 'in_review',
                    'description' => 'Hoogwaardige masteringstudio met geoptimaliseerde akoestiek en gekalibreerde monitoring.'],
            ]],
            [$jesse, ['name' => 'Domstad Studio', 'phone' => '030 2345678', 'street' => 'Oudegracht 187', 'postal_code' => '3511 NE', 'city' => 'Utrecht', 'lat' => 52.0894, 'lng' => 5.1213], [
                ['key' => 'domstad', 'title' => 'De Werfkelder', 'type' => 'opname', 'rate' => 3200, 'capacity' => 4, 'engineer' => false, 'engineer_rate' => 1000, 'status' => 'live',
                    'description' => 'Opnamestudio in een werfkelder aan de Oudegracht. Natuurlijke demping en een unieke sfeer. Een engineer boek je optioneel bij.'],
            ]],
            [$jesse, ['name' => 'Stadspark Sessies', 'phone' => '050 1122334', 'street' => 'Paterswoldseweg 43', 'postal_code' => '9726 BB', 'city' => 'Groningen', 'lat' => 53.2110, 'lng' => 6.5570], [
                ['title' => 'Groene Kamer', 'type' => 'opname', 'rate' => 2800, 'capacity' => 5, 'engineer' => false, 'status' => 'live',
                    'description' => 'Lichte studio aan het Stadspark met veel daglicht en een relaxte sfeer voor songwriters.'],
            ]],
            [$jesse, ['name' => 'Waalzicht Records', 'phone' => '024 5566778', 'street' => 'Waalkade 68', 'postal_code' => '6511 XP', 'city' => 'Nijmegen', 'lat' => 51.8480, 'lng' => 5.8630], [
                ['key' => 'waal', 'title' => 'Waalstudio', 'type' => 'mix', 'rate' => 4800, 'capacity' => 3, 'engineer' => true, 'status' => 'live',
                    'description' => 'Mix- en masterstudio met uitzicht op de Waal. Inclusief engineer met tien jaar ervaring.'],
            ]],
            [$fatima, ['name' => 'Hofstad Geluid', 'phone' => '070 3456789', 'street' => 'Prinsestraat 74', 'postal_code' => '2513 CG', 'city' => 'Den Haag', 'lat' => 52.0810, 'lng' => 4.3050], [
                ['key' => 'hofzaal', 'title' => 'De Hofzaal', 'type' => 'opname', 'rate' => 5000, 'capacity' => 10, 'engineer' => true, 'status' => 'live',
                    'description' => 'Grote opnamezaal voor ensembles, koren en bands. Vleugel aanwezig en engineer inbegrepen.'],
            ]],
            [$fatima, ['name' => 'Textielstad Studio', 'phone' => '013 6677889', 'street' => 'Spoorlaan 348', 'postal_code' => '5038 CC', 'city' => 'Tilburg', 'lat' => 51.5610, 'lng' => 5.0830], [
                ['title' => 'De Spoel', 'type' => 'opname', 'rate' => 2600, 'capacity' => 4, 'engineer' => false, 'status' => 'live',
                    'description' => 'Betaalbare opnamestudio in de oude textielfabriek. Ideaal voor demo\'s en podcasts.'],
            ]],
            [$fatima, ['name' => 'Spaarne Sound', 'phone' => '023 7788990', 'street' => 'Spaarne 11', 'postal_code' => '2011 CD', 'city' => 'Haarlem', 'lat' => 52.3800, 'lng' => 4.6400], [
                ['title' => 'Spaarnezicht', 'type' => 'master', 'rate' => 5200, 'capacity' => 3, 'engineer' => false, 'status' => 'in_review',
                    'description' => 'Mixstudio aan het Spaarne met moderne monitoring en een stille, geïsoleerde regieruimte.'],
            ]],
        ];

        $rooms = collect();
        $index = 0;

        foreach ($plan as [$owner, $studioData, $roomPlans]) {
            $studio = $owner->studios()->create($studioData);

            foreach ($roomPlans as $roomData) {
                $room = $studio->rooms()->create([
                    'title' => $roomData['title'],
                    'description' => $roomData['description'],
                    'type' => $roomData['type'],
                    'hourly_rate_cents' => $roomData['rate'],
                    'min_hours' => 2,
                    'capacity' => $roomData['capacity'],
                    'engineer_included' => $roomData['engineer'],
                    'engineer_rate_cents' => $roomData['engineer_rate'] ?? null,
                    'house_rules' => "Maximaal {$roomData['capacity']} personen in de studio\nNiet roken in de opnameruimte\nEigen apparatuur in overleg",
                    'equipment' => self::EQUIPMENT_SETS[$index % 4],
                    'equipment_extra' => $index % 2 === 0 ? 'Neumann U87, SSL-console' : null,
                    'daws' => self::DAW_SETS[$index % 4],
                    'facilities' => self::FACILITY_SETS[$index % 4],
                    'status' => $roomData['status'],
                    'rejection_reason' => $roomData['rejection_reason'] ?? null,
                    'on_vacation' => $roomData['vacation'] ?? false,
                    'vacation_until' => isset($roomData['vacation']) ? today()->addDays(10) : null,
                ]);

                $room->seedDefaultHours();
                $this->seedPhotos($room, $index);

                if (isset($roomData['key'])) {
                    $rooms[$roomData['key']] = $room;
                }

                $index++;
            }
        }

        $this->seedBooking($rooms['liveA'], $artist, today()->addDays(3), 14, 17, BookingStatus::PendingConfirmation, ['requested_at' => now()->subHours(2)]);
        $this->seedBooking($rooms['noord'], $sam, today()->addDays(4), 10, 13, BookingStatus::PendingConfirmation, ['requested_at' => now()->subHours(5)]);

        $this->seedBooking($rooms['mix'], $artist, today()->addDays(5), 12, 15, BookingStatus::Confirmed);
        $this->seedBooking($rooms['machine'], $nora, today()->addDays(7), 10, 14, BookingStatus::Confirmed);
        $this->seedBooking($rooms['hofzaal'], $sam, today()->addDay(), 18, 21, BookingStatus::Confirmed);
        $this->seedBooking($rooms['domstad'], $artist, today()->addDays(9), 13, 16, BookingStatus::Confirmed);

        $this->seedBooking($rooms['liveA'], $artist, today()->subDays(6), 10, 14, BookingStatus::Completed, ['transferred_at' => now()->subDays(5)]);
        $this->seedBooking($rooms['noord'], $nora, today()->subDays(12), 18, 21, BookingStatus::Completed, ['transferred_at' => now()->subDays(11)]);

        $this->seedBooking($rooms['machine'], $sam, today()->subDays(8), 12, 15, BookingStatus::Completed, [
            'disputed_at' => now()->subDays(8),
            'dispute_reason' => 'De verwarming stond uit en de eerste drie kwartier konden we niet opnemen.',
        ]);
        $this->seedBooking($rooms['noord'], $artist, today()->subDay(), 16, 19, BookingStatus::Disputed, [
            'disputed_at' => now()->subHours(3),
            'dispute_reason' => 'De condensatormicrofoon deed het niet, we hebben een uur verloren aan het zoeken naar een alternatief.',
        ]);
        $this->seedBooking($rooms['waal'], $yusuf, today()->subDays(10), 14, 17, BookingStatus::Cancelled, [
            'disputed_at' => now()->subDays(10),
            'dispute_reason' => 'De studio was dubbel geboekt, we stonden voor een dichte deur.',
            'cancelled_by' => 'admin',
            'refund' => 100,
        ]);

        $this->seedBooking($rooms['machine'], $artist, today()->addDays(2), 16, 18, BookingStatus::Cancelled, ['cancelled_by' => 'artist', 'refund' => 100]);
        $this->seedBooking($rooms['liveA'], $yusuf, today()->addDay(), 10, 12, BookingStatus::Cancelled, ['cancelled_by' => 'artist', 'refund' => 50]);
        $this->seedBooking($rooms['mix'], $nora, today()->addDays(6), 10, 12, BookingStatus::Cancelled, ['cancelled_by' => 'auto', 'refund' => 100]);
        $this->seedBooking($rooms['liveA'], $sam, today()->addDays(8), 12, 14, BookingStatus::Declined, ['refund' => 100]);
        $this->seedBooking($rooms['mix'], $artist, today(), 20, 22, BookingStatus::Expired, ['expires_at' => now()->subHours(3)]);

        if (now()->hour >= 4) {
            $this->seedBooking($rooms['liveA'], $artist, today(), now()->hour - 3, now()->hour - 1, BookingStatus::Completed);
        } else {
            $this->seedBooking($rooms['liveA'], $artist, today()->subDay(), 20, 22, BookingStatus::Completed);
        }

        $this->seedBooking($rooms['domstad'], $artist, today()->addDay(), 20, 22, BookingStatus::Confirmed);

        User::query()->update(['email_verified_at' => now()]);
        User::where('role', 'artiest')->update(['street' => 'Keizersgracht 12', 'postal_code' => '1015 CN', 'city' => 'Amsterdam']);

        $this->command->info('Demodata aangemaakt: 3 verhuurders, 10 studio\'s, 13 ruimtes en 18 boekingen in alle statussen, inclusief facturen, creditnota\'s en uitbetalingen.');
    }

    private function host(string $name, string $email, string $company, string $phone, string $ownerType, bool $btwPlichtig, ?string $kvk, ?string $vat): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => 'wachtwoord123',
            'role' => 'verhuurder',
        ]);

        $user->hostProfile()->create([
            'name' => $company,
            'phone' => $phone,
            'owner_type' => $ownerType,
            'btw_plichtig' => $btwPlichtig,
            'kvk_number' => $kvk,
            'vat_number' => $vat,
            'stripe_details_submitted' => true,
            'stripe_payouts_enabled' => true,
        ]);

        return $user;
    }

    private function seedPhotos(Room $room, int $offset): void
    {
        $photos = self::DEMO_PHOTOS;
        $shift = $offset % count($photos);
        $photos = [...array_slice($photos, $shift), ...array_slice($photos, 0, $shift)];

        foreach ($photos as $index => $photo) {
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

    private function seedBooking(Room $room, User $artist, $date, int $start, int $end, BookingStatus $status, array $extra = []): void
    {
        $rent = $room->hourly_rate_cents * ($end - $start);
        $fee = (int) round($rent * config('studio.service_fee_percent') / 100);
        $vat = (int) round($fee * config('studio.vat_percent') / 100);

        $refund = $extra['refund'] ?? null;
        unset($extra['refund']);

        $refunded = match ($refund) {
            100 => $rent + $fee + $vat,
            50 => (int) round($rent / 2) + $fee + $vat,
            default => null,
        };

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
            'terms_accepted_at' => now()->subDays(9),
            'requested_at' => now()->subDays(9),
            'confirmed_at' => in_array($status, [BookingStatus::Confirmed, BookingStatus::Completed, BookingStatus::Disputed], true) ? now()->subDays(8) : null,
            'refunded_cents' => $refunded,
            ...$extra,
        ]);
    }
}
