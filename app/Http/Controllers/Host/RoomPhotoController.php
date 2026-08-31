<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomPhoto;
use Illuminate\Http\JsonResponse;
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

    /**
     * Store the order the host dragged the photos into. Expects every photo id of the
     * room exactly once, so a stale page cannot half-renumber the gallery.
     */
    public function reorder(Request $request, Room $room): JsonResponse
    {
        abort_unless($room->studio->user_id === $request->user()->id, 403);

        $order = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['required', 'integer'],
        ])['order'];

        $ids = $room->photos()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $order = array_map('intval', $order);

        if (count($order) !== count($ids) || array_diff($ids, $order) !== [] || count(array_unique($order)) !== count($order)) {
            return response()->json(['ok' => false], 422);
        }

        foreach ($order as $position => $id) {
            $room->photos()->whereKey($id)->update(['sort_order' => $position]);
        }

        return response()->json(['ok' => true]);
    }
}
