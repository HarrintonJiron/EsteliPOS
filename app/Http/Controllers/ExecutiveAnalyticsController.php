<?php

namespace App\Http\Controllers;

use App\Services\ExecutiveAnalyticsService;
use Illuminate\View\View;

class ExecutiveAnalyticsController extends Controller
{
    public function __invoke(ExecutiveAnalyticsService $analytics): View
    {
        return view('analitica.index', $analytics->dashboard());
    }
}
