<?php

namespace App\Http\Controllers\Host;

use App\Enums\BookingStatus;
use App\Enums\RoomStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OverviewController extends Controller
{

    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $checklist = [
            ['key' => 'account', 'done' => true, 'url' => null],
            ['key' => 'profile', 'done' => $user->hostProfile !== null, 'url' => route('host.profile.edit')],
            ['key' => 'studio', 'done' => $user->studios()->exists(), 'url' => route('host.studios.index')],
            ['key' => 'room', 'done' => $user->rooms()->exists(), 'url' => route('host.studios.index')],
            ['key' => 'stripe', 'done' => (bool) $user->hostProfile?->stripe_payouts_enabled, 'url' => route('host.stripe.show')],
        ];

        $rooms = $user->rooms()->with('studio')->get();

        $pendingRequests = Booking::whereIn('room_id', $rooms->pluck('id'))
            ->where('status', BookingStatus::PendingConfirmation)
            ->count();

        return view('host.overview', [
            'pendingRequests' => $pendingRequests,
            'profile' => $user->hostProfile,
            'checklist' => $checklist,
            'checklistDone' => collect($checklist)->where('done', true)->count(),
            'studioCount' => $user->studios()->count(),
            'roomCount' => $rooms->count(),
            'rejectedRooms' => $rooms->where('status', RoomStatus::Afgekeurd),
            'inReviewRooms' => $rooms->where('status', RoomStatus::InReview),
        ]);
    }
}
