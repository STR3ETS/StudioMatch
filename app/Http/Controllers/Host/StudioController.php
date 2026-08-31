<?php

namespace App\Http\Controllers\Host;

use App\Enums\RoomStatus;
use App\Http\Controllers\Concerns\HandlesRoomForm;
use App\Http\Controllers\Concerns\VerifiesAddress;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Studio;
use App\Support\Geocoder;
use App\Support\StripeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudioController extends Controller
{
    use HandlesRoomForm, VerifiesAddress;

    public function index(Request $request): View
    {
        return view('host.studios.index', [
            'studios' => $request->user()->studios()
                ->withCount([
                    'rooms',
                    'rooms as rejected_rooms_count' => fn ($query) => $query->where('status', RoomStatus::Afgekeurd),
                ])
                ->latest()
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('host.studios.wizard', ['studio' => new Studio, 'room' => new Room]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateStudio($request);

        $coords = $this->verifiedCoords($validated, __('host.studios.address_invalid'));

        $withRoom = $request->filled('title');

        if ($withRoom) {
            [$roomData, $photos] = $this->validateRoom($request, isCreate: true);
        }

        $studio = $request->user()->studios()->create([...$validated, ...($coords ?? [])]);

        if (! $withRoom) {
            return redirect()->route('host.studios.show', $studio)->with('status', __('host.studios.saved'));
        }

        $room = $studio->rooms()->create([...$roomData, 'status' => RoomStatus::InReview]);
        $room->seedDefaultHours();
        $this->storePhotos($room, $photos);
        $this->notifySubmitted($request, $room);

        if (StripeService::enabled() && ! $request->user()->hostProfile?->stripe_payouts_enabled) {
            return redirect()->route('host.stripe.show')->with('status', __('host.wizard.done_stripe_next'));
        }

        return redirect()->route('dashboard.host')->with('status', __('host.wizard.done'));
    }

    public function show(Request $request, Studio $studio): View
    {
        $this->authorizeStudio($request, $studio);

        return view('host.studios.show', [
            'studio' => $studio->load('rooms.photos'),
        ]);
    }

    public function edit(Request $request, Studio $studio): View
    {
        $this->authorizeStudio($request, $studio);

        return view('host.studios.form', ['studio' => $studio]);
    }

    public function update(Request $request, Studio $studio): RedirectResponse
    {
        $this->authorizeStudio($request, $studio);

        $validated = $this->validateStudio($request);

        $addressChanged = $validated['street'] !== $studio->street
            || $validated['postal_code'] !== $studio->postal_code
            || $validated['city'] !== $studio->city;

        if ($addressChanged || $studio->lat === null) {
            $coords = $this->verifiedCoords($validated, __('host.studios.address_invalid'));
            $validated['lat'] = $coords['lat'] ?? null;
            $validated['lng'] = $coords['lng'] ?? null;
        }

        $studio->update($validated);

        return redirect()->route('host.studios.show', $studio)->with('status', __('host.studios.saved'));
    }

    public function destroy(Request $request, Studio $studio): RedirectResponse
    {
        $this->authorizeStudio($request, $studio);

        $studio->rooms()->with('photos')->get()->each(fn ($room) => $room->photos->each->delete());
        $studio->delete();

        return redirect()->route('host.studios.index')->with('status', __('host.studios.deleted'));
    }

    private function validateStudio(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'street' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:10'],
            'city' => ['required', 'string', 'max:100'],
        ]);
    }

    public function checkAddress(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'street' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:10'],
            'city' => ['required', 'string', 'max:100'],
        ]);

        return response()->json([
            'found' => Geocoder::verify($validated['street'], $validated['postal_code'], $validated['city']) !== false,
        ]);
    }

    private function authorizeStudio(Request $request, Studio $studio): void
    {
        abort_unless($studio->user_id === $request->user()->id, 403);
    }
}
