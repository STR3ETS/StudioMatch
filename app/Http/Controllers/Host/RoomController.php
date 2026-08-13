<?php

namespace App\Http\Controllers\Host;

use App\Enums\RoomStatus;
use App\Enums\RoomType;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Studio;
use App\Support\ImageProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoomController extends Controller
{

    public function create(Request $request, Studio $studio): View
    {
        $this->authorizeStudio($request, $studio);

        return view('host.rooms.form', ['room' => new Room, 'studio' => $studio]);
    }

    public function store(Request $request, Studio $studio): RedirectResponse
    {
        $this->authorizeStudio($request, $studio);

        [$validated, $photos] = $this->validateRoom($request, isCreate: true);

        $validated['status'] = RoomStatus::InReview;

        $room = $studio->rooms()->create($validated);

        $room->seedDefaultHours();

        $this->storePhotos($room, $photos);

        return redirect()->route('host.rooms.edit', $room)->with('status', __('host.rooms.created_in_review'));
    }

    public function edit(Request $request, Room $room): View
    {
        $this->authorizeRoom($request, $room);

        return view('host.rooms.form', ['room' => $room->load('photos'), 'studio' => $room->studio]);
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        $this->authorizeRoom($request, $room);

        [$validated, $photos] = $this->validateRoom($request, isCreate: false);

        if ($room->status === RoomStatus::Afgekeurd) {
            $validated['status'] = RoomStatus::InReview;
            $validated['rejection_reason'] = null;
        }

        $room->update($validated);

        $this->storePhotos($room, $photos);

        return redirect()->route('host.rooms.edit', $room)->with('status', __('host.rooms.saved'));
    }

    public function destroy(Request $request, Room $room): RedirectResponse
    {
        $this->authorizeRoom($request, $room);

        $studio = $room->studio;

        $room->photos->each->delete();
        $room->delete();

        return redirect()->route('host.studios.show', $studio)->with('status', __('host.rooms.deleted'));
    }

    private function validateRoom(Request $request, bool $isCreate): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'type' => ['required', Rule::enum(RoomType::class)],
            'hourly_rate' => ['required', 'numeric', 'min:1', 'max:1000'],
            'min_hours' => ['required', 'integer', 'min:1', 'max:8'],
            'capacity' => ['required', 'integer', 'min:1', 'max:50'],
            'engineer_included' => ['nullable', 'boolean'],
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

        $validated['hourly_rate_cents'] = (int) round($validated['hourly_rate'] * 100);
        unset($validated['hourly_rate']);

        $validated['engineer_included'] = $request->boolean('engineer_included');

        return [$validated, $photos];
    }

    private function storePhotos(Room $room, array $photos): void
    {
        $sort = ($room->photos()->max('sort_order') ?? -1) + 1;

        foreach ($photos as $photo) {

            $stored = ImageProcessor::store($photo->get(), 'rooms/' . $room->id)
                ?? ['path' => $photo->store('rooms/' . $room->id, 'public'), 'thumb_path' => null];

            $room->photos()->create([...$stored, 'sort_order' => $sort++]);
        }
    }

    private function authorizeRoom(Request $request, Room $room): void
    {
        abort_unless($room->studio->user_id === $request->user()->id, 403);
    }

    private function authorizeStudio(Request $request, Studio $studio): void
    {
        abort_unless($studio->user_id === $request->user()->id, 403);
    }
}
