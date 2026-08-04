<?php

namespace App\Http\Controllers\Host;

use App\Enums\RoomStatus;
use App\Http\Controllers\Controller;
use App\Models\Studio;
use App\Support\Geocoder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudioController extends Controller
{
    /**
     * Alle studio's (locaties) van deze verhuurder.
     */
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
        return view('host.studios.form', ['studio' => new Studio]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateStudio($request);

        $studio = $request->user()->studios()->create([
            ...$validated,
            ...(Geocoder::geocode($validated['street'], $validated['postal_code'], $validated['city']) ?? []),
        ]);

        return redirect()->route('host.studios.show', $studio)->with('status', __('host.studios.saved'));
    }

    /**
     * Studiodetail: gegevens + de ruimtes van deze locatie.
     */
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

        // Bij een adreswijziging (of ontbrekende pin) opnieuw geocoden.
        $addressChanged = $validated['street'] !== $studio->street
            || $validated['postal_code'] !== $studio->postal_code
            || $validated['city'] !== $studio->city;

        if ($addressChanged || $studio->lat === null) {
            $coords = Geocoder::geocode($validated['street'], $validated['postal_code'], $validated['city']);
            $validated['lat'] = $coords['lat'] ?? null;
            $validated['lng'] = $coords['lng'] ?? null;
        }

        $studio->update($validated);

        return redirect()->route('host.studios.show', $studio)->with('status', __('host.studios.saved'));
    }

    public function destroy(Request $request, Studio $studio): RedirectResponse
    {
        $this->authorizeStudio($request, $studio);

        // Fotobestanden van alle onderliggende ruimtes mee verwijderen.
        $studio->rooms()->with('photos')->get()->each(fn ($room) => $room->photos->each->delete());
        $studio->delete();

        return redirect()->route('host.studios.index')->with('status', __('host.studios.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
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

    private function authorizeStudio(Request $request, Studio $studio): void
    {
        abort_unless($studio->user_id === $request->user()->id, 403);
    }
}
