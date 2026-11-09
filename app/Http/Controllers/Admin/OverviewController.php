<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminDesk;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function __invoke(): View
    {
        // Dezelfde cijfers als het Overzicht in de app (AdminDesk).
        return view('admin.overview', AdminDesk::overview());
    }
}
