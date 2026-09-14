<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BalanceSheetController extends Controller
{
    public function __construct(private ReportService $reportService)
    {
    }

    public function index(Request $request)
    {
        $asOfDate = $request->query('as_of_date') ?: now()->toDateString();

        $report = $this->reportService->balanceSheet($asOfDate);

        return view('contabilidad.balance-general.index', $report);
    }

    public function export(Request $request): StreamedResponse
    {
        $asOfDate = $request->query('as_of_date') ?: now()->toDateString();

        $report = $this->reportService->balanceSheet($asOfDate);

        $filename = 'balance_general_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($report) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($output, ['Balance General al ' . $report['asOfDate']]);
            fputcsv($output, []);

            $section = function (string $title, $accounts, float $total) use ($output) {
                fputcsv($output, [$title]);
                foreach ($accounts as $account) {
                    fputcsv($output, [$account->code, $account->name, number_format($account->amount, 2)]);
                }
                fputcsv($output, ['', 'Total ' . $title, number_format($total, 2)]);
                fputcsv($output, []);
            };

            $section('Activo', $report['activo'], $report['totalActivo']);
            $section('Pasivo', $report['pasivo'], $report['totalPasivo']);
            $section('Capital', $report['capital'], $report['totalCapitalCuentas']);
            fputcsv($output, ['', 'Utilidad del Ejercicio', number_format($report['utilidadEjercicio'], 2)]);
            fputcsv($output, ['', 'Total Capital', number_format($report['totalCapital'], 2)]);
            fputcsv($output, []);
            fputcsv($output, ['', 'Diferencia (Activo - Pasivo - Capital)', number_format($report['diferencia'], 2)]);

            fclose($output);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
