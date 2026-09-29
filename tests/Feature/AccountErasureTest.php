<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\Studio;
use App\Models\User;
use App\Support\AccountAnonymiser;
use App\Support\Invoices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Een account moet gewist kunnen worden, maar facturen moeten fiscaal zeven jaar
 * bewaard blijven. Deze tests leggen vast dat die twee elkaar niet in de weg zitten.
 */
class AccountErasureTest extends TestCase
{
    use RefreshDatabase;

    private User $artist;

    private User $host;

    private Studio $studio;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artist = User::factory()->create([
            'role' => 'artiest',
            'name' => 'Sam de Wit',
            'email' => 'sam@voorbeeld.nl',
            'street' => 'Prinsengracht 263',
            'postal_code' => '1016 GV',
            'city' => 'Amsterdam',
        ]);

        $this->host = User::factory()->create(['role' => 'verhuurder', 'name' => 'Jamie Bakker']);
        $this->host->hostProfile()->create([
            'name' => 'Redlight Recordings BV',
            'phone' => '0612345678',
            'owner_type' => 'ondernemer',
            'btw_plichtig' => true,
            'kvk_number' => '12345678',
            'vat_number' => 'NL001234567B01',
        ]);

        $this->studio = $this->host->studios()->create([
            'name' => 'Redlight Recordings',
            'street' => 'Keizersgracht 10',
            'postal_code' => '1015 CS',
            'city' => 'Amsterdam',
        ]);

        $this->room = $this->studio->rooms()->create([
            'title' => 'Live room A',
            'description' => 'Fijne ruimte.',
            'type' => 'opname',
            'hourly_rate_cents' => 5000,
            'min_hours' => 2,
            'capacity' => 6,
            'status' => 'live',
        ]);
    }

    private function booking(): Booking
    {
        return $this->room->bookings()->create([
            'user_id' => $this->artist->id,
            'date' => today()->addDays(5),
            'start_hour' => 10,
            'end_hour' => 13,
            'hourly_rate_cents' => 5000,
            'rent_cents' => 15000,
            'service_fee_cents' => 1350,
            'vat_cents' => 284,
            'total_cents' => 16634,
            'status' => 'confirmed',
            'terms_accepted_at' => now(),
            'requested_at' => now(),
            'confirmed_at' => now(),
        ]);
    }

    public function test_a_new_booking_records_the_invoice_details_straight_away(): void
    {
        $booking = $this->booking();

        $this->assertSame('Sam de Wit', $booking->buyer_name);
        $this->assertSame('sam@voorbeeld.nl', $booking->buyer_email);
        $this->assertSame('Prinsengracht 263', $booking->buyer_street);
        $this->assertSame('Redlight Recordings BV', $booking->seller_name);
        $this->assertSame('12345678', $booking->seller_kvk_number);
        $this->assertSame('NL001234567B01', $booking->seller_vat_number);
        $this->assertTrue($booking->seller_vat_liable);
        $this->assertSame('Redlight Recordings - Live room A', $booking->room_label);
    }

    public function test_the_invoice_survives_the_buyer_erasing_their_account(): void
    {
        $booking = $this->booking();

        AccountAnonymiser::run($this->artist);

        $invoice = Invoices::build($booking->fresh(), 'huur');

        $this->assertContains('Sam de Wit', $invoice['buyer']);
        $this->assertContains('Prinsengracht 263', $invoice['buyer']);
        $this->assertContains('sam@voorbeeld.nl', $invoice['buyer']);
        $this->assertContains('1016 GV Amsterdam', $invoice['buyer']);
    }

    public function test_the_invoice_survives_the_host_erasing_their_account(): void
    {
        $booking = $this->booking();

        AccountAnonymiser::run($this->host);

        $invoice = Invoices::build($booking->fresh(), 'huur');

        $this->assertContains('Redlight Recordings BV', $invoice['seller']);
        $this->assertContains('KvK 12345678', $invoice['seller']);
        $this->assertContains('Btw NL001234567B01', $invoice['seller']);
        $this->assertStringContainsString('Redlight Recordings - Live room A', $invoice['lines'][0]['label']);
    }

    public function test_erasing_a_host_takes_the_rooms_offline_and_deletes_the_photos(): void
    {
        Storage::fake('public');

        $path = UploadedFile::fake()->image('ruimte.jpg')->store('rooms', 'public');
        $this->room->photos()->create(['path' => $path, 'sort_order' => 0]);

        Storage::disk('public')->assertExists($path);

        AccountAnonymiser::run($this->host);

        $this->assertSame('concept', $this->room->fresh()->status->value);
        $this->assertSame(0, $this->room->photos()->count());
        Storage::disk('public')->assertMissing($path);

        // Het studio-adres is bij een particuliere verhuurder een woonadres, dus dat gaat eruit.
        $this->assertSame('-', $this->studio->fresh()->street);
        $this->assertNull($this->studio->fresh()->lat);
    }

    public function test_the_erased_room_disappears_from_the_public_site(): void
    {
        $this->get('/studios')->assertOk()->assertSee('Live room A');

        AccountAnonymiser::run($this->host);

        $this->get('/studios')->assertOk()->assertDontSee('Live room A');
    }

    public function test_the_company_details_are_wiped_from_the_host_profile(): void
    {
        AccountAnonymiser::run($this->host);

        $profile = $this->host->fresh()->hostProfile;

        $this->assertSame(__('account.delete.removed'), $profile->name);
        $this->assertNull($profile->kvk_number);
        $this->assertNull($profile->vat_number);
        $this->assertNull($profile->stripe_account_id);
        $this->assertFalse((bool) $profile->stripe_payouts_enabled);
    }
}
