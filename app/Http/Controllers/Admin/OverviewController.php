<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\RoomStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\Studio;
use App\Models\User;
use Illuminate\View\View;

class OverviewController extends Controller
{

    public function __invoke(): View
    {
        return view('admin.overview', [
            'openDisputes' => Booking::where('status', BookingStatus::Disputed)->count(),
            'openDamages' => Booking::whereNotNull('damage_reported_at')->whereNull('damage_resolved_at')->count(),
            'inReviewCount' => Room::where('status', RoomStatus::InReview)->count(),
            'liveCount' => Room::where('status', RoomStatus::Live)->count(),
            'studioCount' => Studio::count(),
            'artistCount' => User::where('role', UserRole::Artiest)->count(),
            'hostCount' => User::where('role', UserRole::Verhuurder)->count(),
        ]);
    }
}
