<?php

namespace App\Http\Controllers\Host;

use App\Enums\ExceptionType;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AvailabilityController extends Controller
{
    /**
     * Kies een ruimte om de beschikbaarheid van te beheren.
     */
    public function index(Request $request): View
    {
        return view('host.availability.index', [
            'hasStudios' => $request->user()->studios()->exists(),
            'rooms' => $request->user()->rooms()->with(['hours', 'studio'])->get(),
        ]);
    }

    /**
     * Weekschema, uitzonderingen en vakantiemodus van één ruimte (scope §2.4).
     */
    public function edit(Request $request, Room $room): View
    {
        $this->authorizeRoom($request, $room);

        // Oudere ruimtes zonder schema krijgen hier alsnog de standaardrijen.
        $room->seedDefaultHours();

        return view('host.availability.edit', [
            'room' => $room->load('hours'),
            'exceptions' => $room->exceptions()->whereDate('date', '>=', today())->get(),
        ]);
    }

    /**
     * Sla het wekelijkse schema op.
     */
    public function updateSchedule(Request $request, Room $room): RedirectResponse
    {
        $this->authorizeRoom($request, $room);

        $validated = $request->validate([
            'days' => ['required', 'array', 'size:7'],
            'days.*.is_open' => ['nullable', 'boolean'],
            'days.*.open_hour' => ['required', 'integer', 'between:0,23'],
            'days.*.close_hour' => ['required', 'integer', 'between:1,24', 'gt:days.*.open_hour'],
        ], [
            'days.*.close_hour.gt' => __('host.availability.close_after_open'),
        ]);

        foreach ($validated['days'] as $weekday => $day) {
            $room->hours()->where('weekday', $weekday)->update([
                'is_open' => (bool) ($day['is_open'] ?? false),
                'open_hour' => $day['open_hour'],
                'close_hour' => $day['close_hour'],
            ]);
        }

        return redirect()->route('host.availability.edit', $room)->with('status', __('host.availability.schedule_saved'));
    }

    /**
     * Zet de vakantiemodus aan of uit (ruimte tijdelijk onzichtbaar).
     */
    public function updateVacation(Request $request, Room $room): RedirectResponse
    {
        $this->authorizeRoom($request, $room);

        $validated = $request->validate([
            'on_vacation' => ['nullable', 'boolean'],
            'vacation_until' => ['nullable', 'date', 'after_or_equal:today'],
        ], [
            'vacation_until.after_or_equal' => __('host.availability.date_in_past'),
        ]);

        $on = $request->boolean('on_vacation');

        $room->update([
            'on_vacation' => $on,
            'vacation_until' => $on ? ($validated['vacation_until'] ?? null) : null,
        ]);

        return redirect()->route('host.availability.edit', $room)->with('status', __('host.availability.vacation_saved'));
    }

    /**
     * Voeg een uitzondering (extra open/dicht) of blokkade toe.
     */
    public function storeException(Request $request, Room $room): RedirectResponse
    {
        $this->authorizeRoom($request, $room);

        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'type' => ['required', Rule::enum(ExceptionType::class)],
            'start_hour' => ['nullable', 'integer', 'between:0,23', 'required_unless:type,closed'],
            'end_hour' => ['nullable', 'integer', 'between:1,24', 'required_unless:type,closed', 'gt:start_hour'],
            'label' => ['nullable', 'string', 'max:100'],
        ], [
            'date.after_or_equal' => __('host.availability.date_in_past'),
            'end_hour.gt' => __('host.availability.end_after_start'),
            'start_hour.required_unless' => __('host.availability.hours_required'),
            'end_hour.required_unless' => __('host.availability.hours_required'),
        ]);

        if ($validated['type'] === ExceptionType::Closed->value) {
            $validated['start_hour'] = null;
            $validated['end_hour'] = null;
        }

        $room->exceptions()->create($validated);

        return redirect()->route('host.availability.edit', $room)->with('status', __('host.availability.exception_saved'));
    }

    /**
     * Verwijder een uitzondering of blokkade.
     */
    public function destroyException(Request $request, Room $room, RoomException $exception): RedirectResponse
    {
        $this->authorizeRoom($request, $room);
        abort_unless($exception->room_id === $room->id, 404);

        $exception->delete();

        return redirect()->route('host.availability.edit', $room)->with('status', __('host.availability.exception_deleted'));
    }

    private function authorizeRoom(Request $request, Room $room): void
    {
        abort_unless($room->studio->user_id === $request->user()->id, 403);
    }
}
