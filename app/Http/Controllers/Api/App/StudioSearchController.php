<?php

namespace App\Http\Controllers\Api\App;

use App\Enums\RoomType;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Support\AppPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Studio's zoeken in de app: op plaats of naam en soort ruimte. De uitgebreide filters (datum,
 * apparatuur, kaart) en de studiopagina zelf blijven op de website; de app opent die als serverpagina.
 */
class StudioSearchController extends Controller
{
    public const LIMIT = 50;

    public function __invoke(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::enum(RoomType::class)],
        ]);

        $query = Room::query()
            ->publiclyVisible()
            ->with(['studio', 'photos'])
            ->latest();

        if (filled($filters['q'] ?? null)) {
            $term = '%' . trim($filters['q']) . '%';
            $query->where(function ($query) use ($term) {
                $query->where('title', 'like', $term)
                    ->orWhereHas('studio', fn ($query) => $query->where('city', 'like', $term)
                        ->orWhere('name', 'like', $term)
                        ->orWhere('street', 'like', $term));
            });
        }

        if (filled($filters['type'] ?? null)) {
            $type = $filters['type'];
            $query->where(fn ($query) => $query->whereJsonContains('types', $type)->orWhere('type', $type));
        }

        $rooms = $query->limit(self::LIMIT)->get();

        return response()->json([
            'types' => collect(RoomType::cases())->map(fn (RoomType $type) => [
                'value' => $type->value,
                'label' => __('host.types.' . $type->value),
            ])->values(),
            'studios' => $rooms->map(fn (Room $room) => AppPayload::studio($room))->values(),
        ]);
    }
}
