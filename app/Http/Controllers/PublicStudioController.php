<?php

namespace App\Http\Controllers;

use App\Enums\RoomType;
use App\Models\Room;
use App\Support\Geocoder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PublicStudioController extends Controller
{

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
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'integer', 'between:1,100'],
            'sort' => ['nullable', 'in:relevance,distance,price_asc,price_desc'],
        ]);

        $types = $filters['types'] ?? [];
        if (! empty($filters['type'])) {
            $types[] = $filters['type'] === 'recording' ? RoomType::Opname->value : $filters['type'];
            $types = array_intersect($types, array_column(RoomType::cases(), 'value'));
        }

        $query = Room::query()
            ->publiclyVisible()
            ->with(['studio', 'photos', 'hours', 'exceptions']);

        $center = isset($filters['lat'], $filters['lng'])
            ? [(float) $filters['lat'], (float) $filters['lng']]
            : null;

        if ($center === null && ! empty($filters['location'])) {
            $place = Cache::remember(
                'geocode-place:' . mb_strtolower(trim($filters['location'])),
                now()->addDay(),
                fn () => Geocoder::place($filters['location']) ?? [],
            );

            if ($place !== []) {
                $center = [$place['lat'], $place['lng']];
            }
        }

        if ($center === null && ! empty($filters['location'])) {
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

        $engineer = $filters['engineer'] ?? [];
        if (count($engineer) === 1) {
            (bool) $engineer[array_key_first($engineer)]
                ? $query->where(fn ($query) => $query->where('engineer_included', true)->orWhereNotNull('engineer_rate_cents'))
                : $query->where('engineer_included', false);
        }

        foreach ($filters['equipment'] ?? [] as $item) {
            $query->whereJsonContains('equipment', $item);
        }

        $daws = $filters['daws'] ?? [];
        if ($daws !== []) {
            $query->where(function ($query) use ($daws) {
                foreach ($daws as $daw) {
                    $query->orWhereJsonContains('daws', $daw);
                }
            });
        }

        foreach ($filters['facilities'] ?? [] as $facility) {
            $query->whereJsonContains('facilities', $facility);
        }

        $sort = $filters['sort'] ?? 'relevance';

        match ($sort) {
            'price_asc' => $query->orderBy('hourly_rate_cents'),
            'price_desc' => $query->orderByDesc('hourly_rate_cents'),
            default => $query->latest(),
        };

        $rooms = $query->get();

        if ($center !== null) {
            [$lat, $lng] = $center;
            $radius = (int) ($filters['radius'] ?? 25);

            $rooms = $rooms
                ->each(function (Room $room) use ($lat, $lng) {
                    $room->distance_km = $room->studio->lat !== null
                        ? $this->distanceKm($lat, $lng, $room->studio->lat, $room->studio->lng)
                        : null;
                })
                ->filter(fn (Room $room) => $room->distance_km !== null && $room->distance_km <= $radius)
                ->values();

            if ($sort === 'distance' || $sort === 'relevance') {
                $rooms = $rooms->sortBy('distance_km')->values();
            }
        }

        if (! empty($filters['date'])) {
            $date = Carbon::parse($filters['date']);
            $start = isset($filters['start']) ? (int) $filters['start'] : null;
            $end = isset($filters['end']) ? (int) $filters['end'] : null;
            if ($start !== null && $end === null) {
                $end = min(24, $start + 2);
            }
            if ($start !== null && $end !== null && $end <= $start) {
                $end = null;
                $start = null;
            }
            $rooms = $rooms->filter(function (Room $room) use ($date, $start, $end) {

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

    public function mapData(Collection $rooms): array
    {
        return $rooms
            ->filter(fn (Room $room) => $room->studio->lat !== null
                || isset(self::CITY_COORDS[mb_strtolower($room->studio->city)]))
            ->map(function (Room $room) {

                [$lat, $lng] = $room->studio->lat !== null
                    ? $this->blurredCoords($room->studio->id, (float) $room->studio->lat, (float) $room->studio->lng)
                    : self::CITY_COORDS[mb_strtolower($room->studio->city)];

                return [
                    'name' => $room->studio->name . ' - ' . $room->title,
                    'city' => $room->studio->city,
                    'price' => (int) round($room->hourlyRateEuros()),
                    'photos' => $room->photos->map->thumbUrl()->all(),
                    'url' => route('studios.show', $room),
                    'lat' => $lat,
                    'lng' => $lng,
                ];
            })
            ->values()
            ->all();
    }

    public function cardData(Room $room): array
    {
        return array_filter([
            'name' => $room->studio->name . ' - ' . $room->title,
            'city' => $room->studio->city,
            'price' => (int) round($room->hourlyRateEuros()),
            'type' => $room->typeLabel(),
            'photos' => $room->photos->map->thumbUrl()->all(),
            'url' => route('studios.show', $room),
            'distance' => isset($room->distance_km) ? number_format($room->distance_km, 1, ',', '.') : null,
        ], fn ($value) => $value !== null);
    }

    /**
     * Shift a studio away from its real address by 60-160 metres in a fixed direction,
     * so a public map pin never points at the front door. The offset is derived from the
     * studio id, so it stays on the same spot between page loads and cannot be averaged away.
     * Stays well inside the 250 m circle drawn on the studio page.
     */
    private function blurredCoords(int $studioId, float $lat, float $lng): array
    {
        $angle = (crc32('studiomatch-map-angle-' . $studioId) % 3600) / 3600 * 2 * M_PI;
        $metres = 60 + crc32('studiomatch-map-offset-' . $studioId) % 100;

        return [
            round($lat + ($metres * cos($angle)) / 111320, 4),
            round($lng + ($metres * sin($angle)) / (111320 * cos(deg2rad($lat))), 4),
        ];
    }

    private function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
