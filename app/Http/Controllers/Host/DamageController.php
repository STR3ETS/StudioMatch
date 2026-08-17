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

        return redirect()->route('host.bookings.index')->with('status', __('host.damage.reported'));
    }
}
