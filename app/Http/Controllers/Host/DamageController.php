<?php

namespace App\Http\Controllers\Host;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\DamageReported;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class DamageController extends Controller
{
    /**
     * Schade melden (scope §2.8): recente sessies om te melden plus de historie.
     */
    public function index(Request $request): View
    {
        $bookings = Booking::whereIn('room_id', $request->user()->rooms()->select('rooms.id'))
            ->with(['room.studio', 'user'])
            ->get();

        return view('host.damage.index', [
            'eligible' => $bookings->filter(fn (Booking $booking) => $booking->canReportDamage())
                ->sortByDesc(fn (Booking $booking) => $booking->startsAt())->values(),
            'reported' => $bookings->whereNotNull('damage_reported_at')
                ->sortByDesc('damage_reported_at')->values(),
        ]);
    }

    /**
     * Meld schade met bewijs → alertmail naar de admin, die bemiddelt en de
     * gegevens deelt. De afhandeling zelf gebeurt buiten het platform.
     */
    public function store(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->room->studio->user_id === $request->user()->id, 403);
        abort_unless($booking->canReportDamage(), 404);

        $validated = $request->validate([
            'damage_reason' => ['required', 'string', 'min:10', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $photos = collect($validated['photos'] ?? [])
            ->map(fn ($photo) => $photo->store('damages/' . $booking->id, 'public'))
            ->all();

        $booking->update([
            'damage_reported_at' => now(),
            'damage_reason' => $validated['damage_reason'],
            'damage_photos' => $photos !== [] ? $photos : null,
        ]);

        Notification::send(User::where('role', UserRole::Admin)->get(), new DamageReported($booking));

        return redirect()->route('host.damage.index')->with('status', __('host.damage.reported'));
    }
}
