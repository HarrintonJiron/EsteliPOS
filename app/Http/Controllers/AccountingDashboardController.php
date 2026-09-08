<?php

namespace App\Http\Controllers;

use App\Services\AccountingDashboardService;
use Illuminate\Http\Request;

class AccountingDashboardController extends Controller
{
    public function __invoke(Request $request, AccountingDashboardService $dashboard)
    {
        $request->validate(['month' => ['nullable', 'date_format:Y-m']]);

        return view('contabilidad.dashboard', $dashboard->build($request->query('month')));
    }
}
