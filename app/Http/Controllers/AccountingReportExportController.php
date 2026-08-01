<?php

namespace App\Http\Controllers;

use App\Services\AccountingReportExportService;
use Illuminate\Http\Request;

class AccountingReportExportController extends Controller
{
    public function pdf(string $report, Request $request, AccountingReportExportService $exports)
    {
        return $exports->pdf($report, $request);
    }

    public function excel(string $report, Request $request, AccountingReportExportService $exports)
    {
        return $exports->excel($report, $request);
    }
}
