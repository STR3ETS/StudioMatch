<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\RoomType;
use App\Enums\UserRole;
use App\Models\Room;
use App\Models\User;
use App\Notifications\RoomSubmitted;
use App\Notifications\RoomSubmittedAdmin;
use App\Support\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

trait HandlesRoomForm
{

    protected function validateRoom(Request $request, bool $isCreate): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'type' => ['required', Rule::enum(RoomType::class)],
            'types' => ['nullable', 'array'],
            'types.*' => [Rule::enum(RoomType::class)],
            'hourly_rate' => ['required', 'numeric', 'min:1', 'max:1000'],
            'min_hours' => ['required', 'integer', 'min:2', 'max:8'],
            'day_rate' => ['nullable', 'numeric', 'min:1', 'max:10000'],
            'min_days' => ['nullable', 'integer', 'min:1', 'max:7'],
            'day_rates' => ['nullable', 'array'],
            'day_rates.*' => ['nullable', 'numeric', 'min:1', 'max:10000'],
            'engineer_day_rate' => ['nullable', 'numeric', 'min:1', 'max:5000'],
            'capacity' => ['required', 'integer', 'min:1', 'max:50'],
            'engineer_option' => ['required', Rule::in(['none', 'included', 'optional'])],
            'engineer_rate' => ['nullable', 'numeric', 'min:1', 'max:500', 'required_if:engineer_option,optional'],
            'house_rules' => ['nullable', 'string', 'max:2000'],
            'equipment' => ['nullable', 'array'],
            'equipment.*' => [Rule::in(config('studio.equipment'))],
            'equipment_extra' => ['nullable', 'string', 'max:255'],
            'daws' => ['nullable', 'array'],
            'daws.*' => [Rule::in(config('studio.daws'))],
            'facilities' => ['nullable', 'array'],
            'facilities.*' => [Rule::in(config('studio.facilities'))],
            'photos' => $isCreate ? ['required', 'array', 'min:5', 'max:15'] : ['nullable', 'array', 'max:15'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'photos.required' => __('host.rooms.min_photos_upload'),
            'photos.min' => __('host.rooms.min_photos_upload'),
        ]);

        $photos = $validated['photos'] ?? [];
        unset($validated['photos']);

        // De aangevinkte categorieen, met de hoofdcategorie er altijd bij.
        $validated['types'] = array_values(array_unique([$validated['type'], ...($validated['types'] ?? [])]));

        $validated['hourly_rate_cents'] = (int) round($validated['hourly_rate'] * 100);
        unset($validated['hourly_rate']);

        // Zonder dagtarief blijft de ruimte alleen per uur boekbaar.
        $validated['day_rate_cents'] = isset($validated['day_rate']) && $validated['day_rate'] !== null
            ? (int) round($validated['day_rate'] * 100)
            : null;
        $validated['min_days'] = (int) ($validated['min_days'] ?? 1);
        unset($validated['day_rate']);

        // Afwijkende dagtarieven per weekdag, bijvoorbeeld een hoger weekendtarief.
        $dayRates = collect($validated['day_rates'] ?? [])
            ->filter(fn ($rate) => $rate !== null && $rate !== '')
            ->mapWithKeys(fn ($rate, $weekday) => [(int) $weekday => (int) round($rate * 100)])
            ->all();
        unset($validated['day_rates']);

        $option = $validated['engineer_option'];
        $validated['engineer_included'] = $option === 'included';
        $validated['engineer_rate_cents'] = $option === 'optional' ? (int) round($validated['engineer_rate'] * 100) : null;
        $validated['engineer_day_rate_cents'] = $option === 'optional' && ! empty($validated['engineer_day_rate'])
            ? (int) round($validated['engineer_day_rate'] * 100)
            : null;
        unset($validated['engineer_option'], $validated['engineer_rate'], $validated['engineer_day_rate']);

        return [$validated, $photos, $dayRates];
    }

    /**
     * Bewaart de afwijkende dagtarieven op het weekrooster van de ruimte.
     *
     * @param  array<int, int>  $dayRates
     */
    protected function storeDayRates(Room $room, array $dayRates): void
    {
        $room->seedDefaultHours();

        for ($weekday = 1; $weekday <= 7; $weekday++) {
            $room->hours()->where('weekday', $weekday)->update([
                'day_rate_cents' => $dayRates[$weekday] ?? null,
            ]);
        }
    }

    protected function storePhotos(Room $room, array $photos): void
    {
        $sort = ($room->photos()->max('sort_order') ?? -1) + 1;

        foreach ($photos as $photo) {

            $stored = ImageProcessor::store($photo->get(), 'rooms/' . $room->id)
                ?? ['path' => $photo->store('rooms/' . $room->id, 'public'), 'thumb_path' => null];

            $room->photos()->create([...$stored, 'sort_order' => $sort++]);
        }
    }

    protected function notifySubmitted(Request $request, Room $room): void
    {
        $request->user()->notify(new RoomSubmitted($room));
        Notification::send(User::where('role', UserRole::Admin)->get(), new RoomSubmittedAdmin($room));
    }
}
