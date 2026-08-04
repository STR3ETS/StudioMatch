<?php

namespace App\Http\Controllers;

use App\Enums\RoomType;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PublicStudioController extends Controller
{
    /**
     * Coördinaten per stad voor de kaart, totdat echte geocoding er is (BESLISSING 14).
     */
    private const CITY_COORDS = [
        'amsterdam' => [52.3676, 4.9041],
        'rotterdam' => [51.9244, 4.4777],
        'utrecht' => [52.0907, 5.1214],
        'den haag' => [52.0705, 4.3007],
        'groningen' => [53.2194, 6.5665],
        'tilburg' => [51.5606, 5.0919],
        'eindhoven' => [51.4416, 5.4697],
        'haarlem' => [52.3874, 4.6462],
        'nijmegen' => [51.8126, 5.8372],
        'breda' => [51.5719, 4.7683],
        'arnhem' => [51.9851, 5.8987],
        'maastricht' => [50.8514, 5.6910],
        'almere' => [52.3508, 5.2647],
        'leiden' => [52.1601, 4.4970],
        'zwolle' => [52.5168, 6.0830],
    ];

    /**
     * Zoekresultaten: alle live ruimtes, gefilterd conform scope §2.3.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'location' => ['nullable', 'string', 'max:100'],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0'],
            'types' => ['nullable', 'array'],
            'types.*' => [Rule::enum(RoomType::class)],
            'type' => ['nullable', 'string'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'engineer' => ['nullable', 'array'],
            'engineer.*' => ['in:0,1'],
            'equipment' => ['nullable', 'array'],
            'equipment.*' => [Rule::in(config('studio.equipment'))],
            'daws' => ['nullable', 'array'],
            'daws.*' => [Rule::in(config('studio.daws'))],
            'facilities' => ['nullable', 'array'],
            'facilities.*' => [Rule::in(config('studio.facilities'))],
            'date' => ['nullable', 'date'],
            'start' => ['nullable', 'integer', 'between:0,23'],
            'end' => ['nullable', 'integer', 'between:1,24'],
            'sort' => ['nullable', 'in:relevance,price_asc,price_desc'],
        ]);

        // De homepage-zoekbalk stuurt één type (o.a. oude waarden recording/mix/master).
        $types = $filters['types'] ?? [];
        if (! empty($filters['type'])) {
            $types[] = match ($filters['type']) {
                'recording' => RoomType::Opname->value,
                'mix', 'master' => RoomType::MixMaster->value,
                default => $filters['type'],
            };
            $types = array_intersect($types, array_column(RoomType::cases(), 'value'));
        }

        $query = Room::query()
            ->publiclyVisible()
            ->with(['studio', 'photos', 'hours', 'exceptions']);

        if (! empty($filters['location'])) {
            $location = $filters['location'];
            $query->whereHas('studio', function ($query) use ($location) {
                $query->where('city', 'like', "%{$location}%")
                    ->orWhere('street', 'like', "%{$location}%")
                    ->orWhere('name', 'like', "%{$location}%");
            });
        }

        if (! empty($filters['price_min'])) {
            $query->where('hourly_rate_cents', '>=', (int) round($filters['price_min'] * 100));
        }
        if (! empty($filters['price_max'])) {
            $query->where('hourly_rate_cents', '<=', (int) round($filters['price_max'] * 100));
        }
        if ($types !== []) {
            $query->whereIn('type', $types);
        }
        if (! empty($filters['capacity'])) {
            $query->where('capacity', '>=', (int) $filters['capacity']);
        }

        // Engineer: alleen filteren als er precies één optie is aangevinkt.
        $engineer = $filters['engineer'] ?? [];
        if (count($engineer) === 1) {
            $query->where('engineer_included', (bool) $engineer[array_key_first($engineer)]);
        }

        foreach ($filters['equipment'] ?? [] as $item) {
            $query->whereJsonContains('equipment', $item);
        }
        foreach ($filters['daws'] ?? [] as $daw) {
            $query->whereJsonContains('daws', $daw);
        }
        foreach ($filters['facilities'] ?? [] as $facility) {
            $query->whereJsonContains('facilities', $facility);
        }

        match ($filters['sort'] ?? 'relevance') {
            'price_asc' => $query->orderBy('hourly_rate_cents'),
            'price_desc' => $query->orderByDesc('hourly_rate_cents'),
            default => $query->latest(),
        };

        $rooms = $query->get();

        // Datum/tijd-filter op basis van weekschema, uitzonderingen en blokkades (§2.4).
        if (! empty($filters['date'])) {
            $date = Carbon::parse($filters['date']);
            $start = isset($filters['start']) ? (int) $filters['start'] : null;
            $end = isset($filters['end']) ? (int) $filters['end'] : null;
            if ($start !== null && $end !== null && $end <= $start) {
                $end = null;
                $start = null;
            }
            $rooms = $rooms->filter(function (Room $room) use ($date, $start, $end) {
                // Met een concreet tijdvak tellen bestaande boekingen ook mee.
                if ($start !== null && $end !== null) {
                    return $room->isBookableFor($date, $start, $end);
                }

                return $room->isAvailableOn($date);
            })->values();
        }

        return view('studios', [
            'rooms' => $rooms,
            'cards' => $rooms->map(fn (Room $room) => $this->cardData($room)),
            'mapStudios' => $this->mapData($rooms),
        ]);
    }

    /**
     * Publieke detailpagina van een ruimte (server-side gerenderd, scope §2.1 SEO).
     */
    public function show(Room $room): View
    {
        abort_unless($room->isPubliclyVisible(), 404);

        $room->load(['studio', 'photos', 'hours', 'exceptions']);

        return view('studio-show', [
            'room' => $room,
            'freeHours' => $room->freeHoursByDate(),
            'mapStudios' => $this->mapData(collect([$room])),
        ]);
    }

    /**
     * Kaartgegevens per ruimte, met een pin op stadsniveau.
     *
     * @param  Collection<int, Room>  $rooms
     * @return array<int, array<string, mixed>>
     */
    public function mapData(Collection $rooms): array
    {
        return $rooms
            ->filter(fn (Room $room) => $room->studio->lat !== null
                || isset(self::CITY_COORDS[mb_strtolower($room->studio->city)]))
            ->map(function (Room $room) {
                // Exacte pin via geocoding; anders een pin op stadsniveau.
                [$lat, $lng] = $room->studio->lat !== null
                    ? [$room->studio->lat, $room->studio->lng]
                    : self::CITY_COORDS[mb_strtolower($room->studio->city)];

                return [
                    'name' => $room->studio->name . ' - ' . $room->title,
                    'city' => $room->studio->city,
                    'price' => (int) round($room->hourlyRateEuros()),
                    'photos' => $room->photos->map->url()->all(),
                    'url' => route('studios.show', $room),
                    'lat' => $lat,
                    'lng' => $lng,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * De gegevens die de studio-card-component verwacht.
     *
     * @return array<string, mixed>
     */
    public function cardData(Room $room): array
    {
        return [
            'name' => $room->studio->name . ' - ' . $room->title,
            'city' => $room->studio->city,
            'price' => (int) round($room->hourlyRateEuros()),
            'type' => $room->typeLabel(),
            'photos' => $room->photos->map->url()->all(),
            'url' => route('studios.show', $room),
        ];
    }
}
