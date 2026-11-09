<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ReviewRoom;
use App\Enums\RoomStatus;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Support\AdminDesk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QueueController extends Controller
{
    public function index(): View
    {
        return view('admin.queue.index', ['rooms' => AdminDesk::queue()]);
    }

    public function show(Room $room, ReviewRoom $review): View
    {
        $room->load(['studio.user.hostProfile', 'photos', 'hours']);

        return view('admin.queue.show', [
            'room' => $room,
            'stripeBlocked' => $review->stripeBlocked($room),
        ]);
    }

    public function approve(Room $room, ReviewRoom $review): RedirectResponse
    {
        abort_unless($room->status === RoomStatus::InReview, 404);

        if ($review->approve($room) === ReviewRoom::STRIPE_REQUIRED) {
            return redirect()->route('admin.queue.show', $room)
                ->withErrors(['stripe' => __('admin.queue.stripe_required', ['host' => $room->studio->user->name])]);
        }

        return redirect()->route('admin.queue.index')->with('status', __('admin.queue.approved', ['room' => $room->title]));
    }

    public function reject(Request $request, Room $room, ReviewRoom $review): RedirectResponse
    {
        abort_unless($room->status === RoomStatus::InReview, 404);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $review->reject($room, $validated['rejection_reason']);

        return redirect()->route('admin.queue.index')->with('status', __('admin.queue.rejected', ['room' => $room->title]));
    }
}
