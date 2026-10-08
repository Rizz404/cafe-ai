<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentCafe;
use App\Http\Controllers\Controller;
use App\Modules\Operations\Queries\DashboardMetrics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ResolvesCurrentCafe;

    public function index(Request $request, DashboardMetrics $metrics): View
    {
        $cafe = $this->currentCafe($request);

        return view('admin.dashboard', [
            'cafe' => $cafe,
            'stats' => $metrics->counts($cafe),
            'recentReservations' => $metrics->recentReservations($cafe),
            'openHandovers' => $metrics->latestOpenHandovers($cafe),
        ]);
    }
}
