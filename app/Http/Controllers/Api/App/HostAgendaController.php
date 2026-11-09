<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Support\AppPayload;
use App\Support\HostAgenda;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Agenda van de verhuurder in de app: vandaag en de zes dagen erna, elke dag ook als er niets staat.
 */
class HostAgendaController extends Controller
{
    public const DAYS = 7;

    public function __invoke(Request $request): JsonResponse
    {
        $from = today();
        $until = today()->addDays(self::DAYS - 1);
        $entries = HostAgenda::days($request->user(), $from, $until);

        $days = [];
        for ($date = $from->copy(); $date->lte($until); $date->addDay()) {
            $key = $date->toDateString();
            $days[] = [
                'date' => $key,
                'label' => $date->translatedFormat('l j F'),
                'today' => $date->isToday(),
                'entries' => ($entries[$key] ?? collect())->map(fn (array $entry) => $entry['kind'] === 'booking'
                    ? ['kind' => 'booking', ...AppPayload::hostBooking($entry['item'])]
                    : AppPayload::agendaException($entry['item']))->values(),
            ];
        }

        return response()->json(['days' => $days]);
    }
}
