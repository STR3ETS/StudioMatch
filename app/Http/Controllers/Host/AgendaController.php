<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Support\HostAgenda;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgendaController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('host.agenda', ['days' => HostAgenda::days($request->user(), today(), today()->addDays(28))]);
    }
}
