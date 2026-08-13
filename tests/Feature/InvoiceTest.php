<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Support\Invoices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private User $artist;

    private User $host;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artist = User::factory()->create(['role' => 'artiest']);
        $this->host = User::factory()->create(['role' => 'verhuurder']);

        $this->host->hostProfile()->create([
            'name' => 'Redlight Recordings B.V.',
            'phone' => '0612345678',
            'owner_type' => 'ondernemer',
            'btw_plichtig' => true,
            'kvk_number' => '12345678',
            'vat_number' => 'NL123456789B01',
        ]);

        $studio = $this->host->studios()->create([
            'name' => 'Redlight Recordings',
            'street' => 'Prinsengracht 263',
            'postal_code' => '1016 GV',
            'city' => 'Amsterdam',
        ]);

        $this->room = $studio->rooms()->create([
            'title' => 'Live room A',
            'description' => 'Fijne ruimte.',
            'type' => 'opname',
            'hourly_rate_cents' => 5000,
            'min_hours' => 2,
            'capacity' => 6,
            'status' => 'live',
        ]);
    }

    private function booking(array $attributes = []): Booking
    {
        return $this->room->bookings()->create(array_merge([
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
        ], $attributes));
    }

    public function test_paid_booking_has_rent_and_fee_documents(): void
    {
        $booking = $this->booking();

        $this->assertSame(['huur', 'commissie'], Invoices::documentsFor($booking));
    }

    public function test_unpaid_booking_has_no_documents(): void
    {
        $booking = $this->booking(['status' => 'pending_payment', 'expires_at' => now()->addMinutes(10)]);

        $this->assertSame([], Invoices::documentsFor($booking));

        $this->actingAs($this->artist)
            ->get('/facturen/' . $booking->id . '/huur')
            ->assertNotFound();
    }

    public function test_refund_adds_credit_documents(): void
    {
        $booking = $this->booking(['status' => 'cancelled', 'cancelled_by' => 'artist', 'refunded_cents' => 16634]);

        $this->assertSame(['huur', 'commissie', 'credit-huur', 'credit-commissie'], Invoices::documentsFor($booking));
        $this->assertSame(15000, Invoices::creditRentCents($booking));
        $this->assertSame(1634, Invoices::creditFeeCents($booking));
    }

    public function test_half_refund_credits_half_the_rent(): void
    {
        $booking = $this->booking(['status' => 'cancelled', 'cancelled_by' => 'artist', 'refunded_cents' => 7500 + 1350 + 284]);

        $this->assertSame(7500, Invoices::creditRentCents($booking));
        $this->assertSame(1634, Invoices::creditFeeCents($booking));
    }

    public function test_vat_registered_host_gets_rent_invoice_title(): void
    {
        $data = Invoices::build($this->booking(), 'huur');

        $this->assertSame(__('invoice.types.rent_invoice'), $data['title']);
        $this->assertNotNull($data['vat']);
        $this->assertSame(12397, $data['vat']['excl']);
        $this->assertSame(2603, $data['vat']['vat']);
    }

    public function test_non_vat_host_gets_rent_receipt_without_vat(): void
    {
        $this->host->hostProfile->update(['btw_plichtig' => false]);

        $data = Invoices::build($this->booking()->fresh(), 'huur');

        $this->assertSame(__('invoice.types.rent_receipt'), $data['title']);
        $this->assertNull($data['vat']);
    }

    public function test_artist_can_download_invoice_pdf(): void
    {
        $booking = $this->booking();

        $response = $this->actingAs($this->artist)->get('/facturen/' . $booking->id . '/huur');

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_host_can_download_rent_but_not_fee_invoice(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->host)->get('/facturen/' . $booking->id . '/huur')->assertOk();
        $this->actingAs($this->host)->get('/facturen/' . $booking->id . '/commissie')->assertNotFound();
    }

    public function test_other_users_cannot_download(): void
    {
        $booking = $this->booking();
        $other = User::factory()->create(['role' => 'artiest']);

        $this->actingAs($other)->get('/facturen/' . $booking->id . '/huur')->assertForbidden();
    }

    public function test_invoice_pages_render(): void
    {
        $this->booking();

        $this->actingAs($this->artist)
            ->get('/dashboard/facturen')
            ->assertOk()
            ->assertSee(__('invoice.labels.huur'))
            ->assertSee(__('invoice.labels.commissie'));

        $this->actingAs($this->host->fresh())
            ->get('/dashboard/verhuurder/facturen')
            ->assertOk()
            ->assertSee(__('invoice.labels.huur'))
            ->assertDontSee(__('invoice.labels.commissie'));
    }
}
