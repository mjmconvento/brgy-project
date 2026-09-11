<?php

namespace App\Http\Controllers;

use App\Queries\DashboardMetrics;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Landing page: headline figures and charts over the whole data set.
     */
    public function index(DashboardMetrics $metrics): View
    {
        return view('dashboard', [
            'metrics' => $metrics->toArray(),
        ]);
    }
}
