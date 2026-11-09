<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminDesk;
use Illuminate\View\View;

class RevenueController extends Controller
{
    public function __invoke(): View
    {
        // Dezelfde cijfers als Omzet in de app (AdminDesk).
        return view('admin.revenue', AdminDesk::revenue());
    }
}
