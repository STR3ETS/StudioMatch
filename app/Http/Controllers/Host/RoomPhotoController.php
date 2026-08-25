<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RoomPhotoController extends Controller
{

    public function destroy(Request $request, Room $room, RoomPhoto $photo): RedirectResponse
    {
        abort_unless($room->studio->user_id === $request->user()->id, 403);
        abort_unless($photo->room_id === $room->id, 404);

        if ($room->photos()->count() <= 5) {
            return redirect()->route('host.rooms.edit', $room)->withErrors(['photos' => __('host.rooms.min_photos_delete')]);
        }

        $photo->delete();

        return redirect()->route('host.rooms.edit', $room)->with('status', __('host.rooms.photo_deleted'));
    }

    public function move(Request $request, Room $room, RoomPhoto $photo): RedirectResponse
    {
        abort_unless($room->studio->user_id === $request->user()->id, 403);
        abort_unless($photo->room_id === $room->id, 404);

        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        $neighbor = $room->photos()
            ->where('sort_order', $direction === 'up' ? '<' : '>', $photo->sort_order)
            ->orderBy('sort_order', $direction === 'up' ? 'desc' : 'asc')
            ->first();

        if ($neighbor !== null) {
            [$photoOrder, $neighborOrder] = [$photo->sort_order, $neighbor->sort_order];
            $photo->update(['sort_order' => $neighborOrder]);
            $neighbor->update(['sort_order' => $photoOrder]);
        }

        return redirect()->route('host.rooms.edit', $room)->with('status', __('host.rooms.photo_moved'));
    }
}
