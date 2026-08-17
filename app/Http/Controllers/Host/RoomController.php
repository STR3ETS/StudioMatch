<?php

namespace App\Http\Controllers\Host;

use App\Enums\RoomStatus;
use App\Http\Controllers\Concerns\HandlesRoomForm;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Studio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    use HandlesRoomForm;

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

        $this->notifySubmitted($request, $room);

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

        $resubmitted = $room->status === RoomStatus::Afgekeurd;

        if ($resubmitted) {
            $validated['status'] = RoomStatus::InReview;
            $validated['rejection_reason'] = null;
        }

        $room->update($validated);

        $this->storePhotos($room, $photos);

        if ($resubmitted) {
            $this->notifySubmitted($request, $room);
        }

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

    private function authorizeRoom(Request $request, Room $room): void
    {
        abort_unless($room->studio->user_id === $request->user()->id, 403);
    }

    private function authorizeStudio(Request $request, Studio $studio): void
    {
        abort_unless($studio->user_id === $request->user()->id, 403);
    }
}
