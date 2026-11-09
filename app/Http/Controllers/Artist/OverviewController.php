<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Support\ArtistBookings;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('dashboard.artist', ArtistBookings::split($request->user()));
    }
}
