<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use App\Support\Hours;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FeedbackPilotTest extends TestCase
{
    use RefreshDatabase;

    private User $host;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->host = User::factory()->create(['role' => 'verhuurder']);
        $this->host->hostProfile()->create([
            'name' => 'Redlight Recordings',
            'phone' => '0612345678',
            'owner_type' => 'ondernemer',
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
            'types' => ['opname', 'productie'],
            'hourly_rate_cents' => 5000,
            'min_hours' => 2,
            'capacity' => 6,
            'status' => 'live',
        ]);

        $this->room->seedDefaultHours();
        $this->room->refresh();
    }

    /* 3. Inloggen en registreren in de header */

    public function test_a_visitor_sees_log_in_and_register_instead_of_account(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(__('nav.login'))
            ->assertSee(__('nav.register'))
            ->assertDontSee(__('nav.account'));
    }

    /* 6. Interface als filteroptie */

    public function test_interface_is_a_filter_option(): void
    {
        $this->assertContains('interface', config('studio.equipment'));

        $this->get('/studios')->assertOk()->assertSee(__('studios.equipment.interface'));
    }

    /* 9.1 Meerdere categorieen */

    public function test_a_room_can_carry_several_categories(): void
    {
        $this->assertSame(['opname', 'productie'], $this->room->typeValues());
        $this->assertStringContainsString(__('host.types.productie'), $this->room->typeLabel());

        // Zoeken op een tweede categorie vindt de ruimte ook.
        $this->get('/studios?types[]=productie')->assertOk()->assertSee($this->room->title);
        $this->get('/studios?types[]=master')->assertOk()->assertDontSee($this->room->title);
    }

    /* 11. Zakelijk of particulier op de advertentie */

    public function test_the_studio_page_shows_the_host_type(): void
    {
        $this->get('/studios/' . $this->room->slug)
            ->assertOk()
            ->assertSee(__('studio.owner_ondernemer'));
    }

    /* 16. Consumentenprijs inclusief servicekosten */

    public function test_advertised_prices_include_the_service_fee(): void
    {
        // 50 euro plus 9% servicekosten plus 21% btw daarover is 55,45, afgerond 55.
        $this->assertSame(5545, Room::allInCents(5000));
        $this->assertSame(55, Room::allInEuros(5000));
        $this->assertSame(55, $this->room->displayHourlyEuros());

        // Onder 50 cent naar beneden, anders naar boven.
        $this->assertSame(28, Room::allInEuros(2500));
        $this->assertSame(44, Room::allInEuros(4000));

        $this->get('/studios')->assertOk()->assertSee('&euro;55', escape: false);
    }

    /* 18. De volgende dag bij naam in plaats van +1 */

    public function test_opening_hours_name_the_next_day(): void
    {
        $this->assertSame('06:00 – 02:00 (' . Hours::weekdayShort(2) . ')', Hours::openingRange(1, 6, 26));
        $this->assertStringNotContainsString('+1', Hours::openingRange(1, 6, 26));
        $this->assertStringNotContainsString('+1', Hours::range(20, 26));
    }

    public function test_a_booking_period_names_both_days(): void
    {
        $monday = Carbon::parse('2026-09-21');

        $this->assertSame('15:00 – 20:00', Hours::bookingRange($monday, 15, 20));
        $this->assertSame(
            $monday->translatedFormat('l') . ' 21:00 – ' . $monday->copy()->addDay()->translatedFormat('l') . ' 02:00',
            Hours::bookingRange($monday, 21, 26)
        );
    }

    /* 5. Wizard stuurt eerst naar de bedrijfsgegevens */

    public function test_a_host_without_business_details_is_guided_to_that_step_first(): void
    {
        $fresh = User::factory()->create(['role' => 'verhuurder']);

        $this->actingAs($fresh)->get(route('host.studios.create'))
            ->assertRedirect(route('host.profile.edit'))
            ->assertSessionHas('status', __('host.wizard.profile_required'));

        $this->actingAs($fresh)->post('/dashboard/verhuurder/studios', [
            'name' => 'Nieuwe Studio',
            'street' => 'Prinsengracht 263',
            'postal_code' => '1016 GV',
            'city' => 'Amsterdam',
        ])->assertRedirect(route('host.profile.edit'));

        $this->assertSame(0, $fresh->studios()->count());
    }

    /* 7. Filteren over meerdere dagen */

    public function test_the_filter_checks_every_day_in_the_range(): void
    {
        // Standaardrooster is in het weekend dicht, dus een reeks over het weekend valt af.
        $friday = today()->next(Carbon::FRIDAY);

        $this->get('/studios?date=' . $friday->toDateString())
            ->assertOk()
            ->assertSee($this->room->title);

        $this->get('/studios?date=' . $friday->toDateString() . '&date_to=' . $friday->copy()->addDays(2)->toDateString())
            ->assertOk()
            ->assertDontSee($this->room->title);
    }

    /* 8. Admin ziet de volledige studiodetails */

    public function test_the_admin_review_page_shows_rates_and_opening_hours(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->room->update(['status' => 'in_review', 'day_rate_cents' => 40000]);

        $this->actingAs($admin)->get(route('admin.queue.show', $this->room))
            ->assertOk()
            ->assertSee(__('host.rooms.fields.day_rate'))
            ->assertSee(__('studio.opening_hours'))
            ->assertSee(__('host.availability.days.1'));
    }
}
