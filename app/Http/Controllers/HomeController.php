<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Homepage: uitgelichte (live) studio's en de kaart op echte data.
     */
    public function __invoke(PublicStudioController $studios): View
    {
        $rooms = Room::query()
            ->publiclyVisible()
            ->with(['studio', 'photos'])
            ->latest()
            ->take(8)
            ->get();

        return view('welcome', [
            'featured' => $rooms->map(fn (Room $room) => $studios->cardData($room)),
            'mapStudios' => $studios->mapData($rooms),
        ]);
    }
}
