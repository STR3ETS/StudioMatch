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

        $photos = $room->photos()->orderBy('sort_order')->orderBy('id')->get()->values();
        $index = $photos->search(fn ($item) => $item->id === $photo->id);
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index !== false && $target >= 0 && $target < $photos->count()) {
            $items = $photos->all();
            [$items[$index], $items[$target]] = [$items[$target], $items[$index]];

            foreach ($items as $order => $item) {
                $item->update(['sort_order' => $order]);
            }
        }

        return redirect()->route('host.rooms.edit', $room)->with('status', __('host.rooms.photo_moved'));
    }
}
