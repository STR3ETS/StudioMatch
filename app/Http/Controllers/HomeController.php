<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\View\View;

class HomeController extends Controller
{

    public function __invoke(PublicStudioController $studios): View
    {
        $rooms = Room::featured();

        return view('welcome', [
            'featured' => $rooms->map(fn (Room $room) => $studios->cardData($room)),
            'mapStudios' => $studios->mapData($rooms),
        ]);
    }
}
