<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    private function studio(array $attributes = []): Studio
    {
        $host = User::factory()->create(['role' => 'verhuurder']);

        return $host->studios()->create(array_merge([
            'name' => 'Redlight Recordings',
            'street' => 'Prinsengracht 263',
            'postal_code' => '1016 GV',
            'city' => 'Amsterdam',
        ], $attributes));
    }

    private function liveRoom(?Studio $studio = null, array $attributes = []): Room
    {
        $room = ($studio ?? $this->studio())->rooms()->create(array_merge([
            'title' => 'Live room A',
            'description' => 'Professionele opnamestudio met fijne akoestiek.',
            'type' => 'opname',
            'hourly_rate_cents' => 4500,
            'min_hours' => 2,
            'capacity' => 6,
            'engineer_included' => true,
            'equipment' => ['mic_condenser', 'monitors'],
            'daws' => ['Logic'],
            'facilities' => ['wifi', 'coffee'],
            'status' => 'live',
        ], $attributes));

        $room->seedDefaultHours();
        $room->photos()->create(['path' => 'rooms/' . $room->id . '/foto.jpg']);

        return $room->fresh();
    }

    public function test_search_page_shows_only_live_rooms(): void
    {
        $studio = $this->studio();
        $this->liveRoom($studio, ['title' => 'Zichtbare ruimte']);
        $this->liveRoom($studio, ['title' => 'In review ruimte', 'status' => 'in_review']);
        $this->liveRoom($studio, ['title' => 'Vakantie ruimte', 'on_vacation' => true]);

        $this->get('/studios')
            ->assertOk()
            ->assertSee('Zichtbare ruimte')
            ->assertDontSee('In review ruimte')
            ->assertDontSee('Vakantie ruimte');
    }

    public function test_expired_vacation_room_is_visible_again(): void
    {
        $this->liveRoom(null, ['on_vacation' => true, 'vacation_until' => today()->subDay()]);

        $this->get('/studios')->assertOk()->assertSee('Live room A');
    }

    public function test_search_filters_on_price_type_and_engineer(): void
    {
        $studio = $this->studio();
        $this->liveRoom($studio, ['title' => 'Goedkope opname', 'hourly_rate_cents' => 3000]);
        $this->liveRoom($studio, ['title' => 'Dure mix', 'type' => 'mix_master', 'hourly_rate_cents' => 9000, 'engineer_included' => false]);

        $this->get('/studios?price_max=50')->assertSee('Goedkope opname')->assertDontSee('Dure mix');
        $this->get('/studios?types[]=mix_master')->assertSee('Dure mix')->assertDontSee('Goedkope opname');
        $this->get('/studios?engineer[]=1')->assertSee('Goedkope opname')->assertDontSee('Dure mix');
    }

    public function test_search_filters_on_location_and_capacity(): void
    {
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response([], 500)]);

        $this->liveRoom($this->studio(['city' => 'Amsterdam']), ['title' => 'Ruimte Amsterdam', 'capacity' => 4]);
        $this->liveRoom($this->studio(['city' => 'Groningen']), ['title' => 'Ruimte Groningen', 'capacity' => 10]);

        $this->get('/studios?location=Groningen')->assertSee('Ruimte Groningen')->assertDontSee('Ruimte Amsterdam');
        $this->get('/studios?capacity=8')->assertSee('Ruimte Groningen')->assertDontSee('Ruimte Amsterdam');
    }

    public function test_city_search_applies_radius_via_geocoding(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'api.pdok.nl/*' => \Illuminate\Support\Facades\Http::response([
                'response' => ['docs' => [['centroide_ll' => 'POINT(4.3007 52.0705)']]],
            ]),
        ]);

        $this->liveRoom($this->studio(['city' => 'Rotterdam', 'lat' => 51.9244, 'lng' => 4.4777]), ['title' => 'Ruimte Rotterdam']);
        $this->liveRoom($this->studio(['city' => 'Groningen', 'lat' => 53.2194, 'lng' => 6.5665]), ['title' => 'Ruimte Groningen']);

        $this->get('/studios?location=Den+Haag&radius=100')
            ->assertSee('Ruimte Rotterdam')
            ->assertDontSee('Ruimte Groningen');

        $this->get('/studios?location=Den+Haag&radius=10')
            ->assertDontSee('Ruimte Rotterdam');
    }

    public function test_daw_filter_uses_or_logic(): void
    {
        $studio = $this->studio();
        $this->liveRoom($studio, ['title' => 'Logic ruimte', 'daws' => ['Logic']]);
        $this->liveRoom($studio, ['title' => 'Ableton ruimte', 'daws' => ['Ableton']]);
        $this->liveRoom($studio, ['title' => 'Cubase ruimte', 'daws' => ['Cubase']]);

        $this->get('/studios?daws[]=Logic&daws[]=Ableton')
            ->assertSee('Logic ruimte')
            ->assertSee('Ableton ruimte')
            ->assertDontSee('Cubase ruimte');
    }

    public function test_search_filters_on_availability_date(): void
    {

        $this->liveRoom();

        $monday = today()->next('monday');
        $sunday = today()->next('sunday');

        $this->get('/studios?date=' . $monday->toDateString())->assertSee('Live room A');
        $this->get('/studios?date=' . $sunday->toDateString())->assertDontSee('Live room A');
        $this->get('/studios?date=' . $monday->toDateString() . '&start=10&end=12')->assertSee('Live room A');
        $this->get('/studios?date=' . $monday->toDateString() . '&start=6&end=8')->assertDontSee('Live room A');
    }

    public function test_gallery_shows_more_photos_indicator(): void
    {
        $room = $this->liveRoom();
        foreach (range(2, 7) as $i) {
            $room->photos()->create(['path' => "rooms/{$room->id}/foto{$i}.jpg", 'sort_order' => $i]);
        }

        $this->get('/studios/' . $room->slug)->assertOk()->assertSee('+2');
    }

    public function test_search_filters_and_sorts_by_distance(): void
    {

        $near = $this->liveRoom(null, ['title' => 'Ruimte Amsterdam-Noord']);
        $near->studio->update(['lat' => 52.40, 'lng' => 4.90]);

        $farStudio = $this->studio(['name' => 'Domklank', 'city' => 'Utrecht', 'street' => 'Oudegracht 12']);
        $farStudio->update(['lat' => 52.0907, 'lng' => 5.1214]);
        $this->liveRoom($farStudio, ['title' => 'Ruimte Utrecht']);

        $this->get('/studios?lat=52.37&lng=4.90&radius=25')
            ->assertOk()
            ->assertSee('Ruimte Amsterdam-Noord')
            ->assertSee('km)')
            ->assertDontSee('Ruimte Utrecht');

        $this->get('/studios?lat=52.37&lng=4.90&radius=60')
            ->assertOk()
            ->assertSee('Ruimte Amsterdam-Noord')
            ->assertSee('Ruimte Utrecht');
    }

    public function test_detail_page_renders_live_room_by_slug(): void
    {
        $room = $this->liveRoom();

        $this->get('/studios/' . $room->slug)
            ->assertOk()
            ->assertSee('Live room A')
            ->assertSee('Redlight Recordings')
            ->assertSee('Professionele opnamestudio')
            ->assertSee('Condensatormicrofoon')
            ->assertSee('Prinsengracht 263');
    }

    public function test_detail_page_is_not_available_for_unpublished_rooms(): void
    {
        $inReview = $this->liveRoom(null, ['status' => 'in_review']);
        $vacation = $this->liveRoom($inReview->studio, ['title' => 'Vakantie', 'on_vacation' => true]);

        $this->get('/studios/' . $inReview->slug)->assertNotFound();
        $this->get('/studios/' . $vacation->slug)->assertNotFound();
    }

    public function test_rooms_get_a_unique_slug(): void
    {
        $studio = $this->studio();
        $roomA = $this->liveRoom($studio);
        $roomB = $this->liveRoom($studio);

        $this->assertNotNull($roomA->slug);
        $this->assertNotSame($roomA->slug, $roomB->slug);
        $this->assertStringContainsString('redlight-recordings-live-room-a', $roomA->slug);
    }

    public function test_homepage_features_live_rooms(): void
    {
        $this->liveRoom(null, ['title' => 'Uitgelichte ruimte']);

        $this->get('/')->assertOk()->assertSee('Uitgelichte ruimte');
    }

    public function test_homepage_search_type_from_search_bar_maps_to_room_types(): void
    {
        $studio = $this->studio();
        $this->liveRoom($studio, ['title' => 'Opname ruimte']);
        $this->liveRoom($studio, ['title' => 'Mix ruimte', 'type' => 'mix_master']);

        $this->get('/studios?type=recording')->assertSee('Opname ruimte')->assertDontSee('Mix ruimte');
        $this->get('/studios?type=mix')->assertSee('Mix ruimte')->assertDontSee('Opname ruimte');
    }
}
