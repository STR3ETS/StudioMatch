<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoomStatus;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Notifications\RoomApproved;
use App\Notifications\RoomRejected;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QueueController extends Controller
{
    /**
     * Goedkeuringswachtrij: alle ruimtes die in review staan (scope §2.9 admin).
     */
    public function index(): View
    {
        return view('admin.queue.index', [
            'rooms' => Room::where('status', RoomStatus::InReview)
                ->with(['studio.user', 'photos'])
                ->oldest('updated_at')
                ->get(),
        ]);
    }

    /**
     * Detail van een in te beoordelen ruimte, met goedkeuren/afwijzen.
     */
    public function show(Room $room): View
    {
        return view('admin.queue.show', [
            'room' => $room->load(['studio.user', 'photos', 'hours']),
        ]);
    }

    /**
     * Goedkeuren: in review → live + mail naar de verhuurder.
     */
    public function approve(Room $room): RedirectResponse
    {
        abort_unless($room->status === RoomStatus::InReview, 404);

        $room->update(['status' => RoomStatus::Live, 'rejection_reason' => null]);

        $room->studio->user->notify(new RoomApproved($room));

        return redirect()->route('admin.queue.index')->with('status', __('admin.queue.approved', ['room' => $room->title]));
    }

    /**
     * Afwijzen met reden: in review → afgekeurd + mail naar de verhuurder.
     */
    public function reject(Request $request, Room $room): RedirectResponse
    {
        abort_unless($room->status === RoomStatus::InReview, 404);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $room->update(['status' => RoomStatus::Afgekeurd, 'rejection_reason' => $validated['rejection_reason']]);

        $room->studio->user->notify(new RoomRejected($room));

        return redirect()->route('admin.queue.index')->with('status', __('admin.queue.rejected', ['room' => $room->title]));
    }
}
