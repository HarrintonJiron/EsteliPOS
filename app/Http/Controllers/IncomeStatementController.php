<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IncomeStatementController extends Controller
{
    public function __construct(private ReportService $reportService)
    {
    }

    public function index(Request $request)
    {
        $dateFrom = $request->query('date_from') ?: now()->startOfMonth()->toDateString();
        $dateTo = $request->query('date_to') ?: now()->toDateString();

        $report = $this->reportService->incomeStatement($dateFrom, $dateTo);

        return view('contabilidad.estado-resultados.index', $report + compact('dateFrom', 'dateTo'));
    }

    public function export(Request $request): StreamedResponse
    {
        $dateFrom = $request->query('date_from') ?: now()->startOfMonth()->toDateString();
        $dateTo = $request->query('date_to') ?: now()->toDateString();

        $report = $this->reportService->incomeStatement($dateFrom, $dateTo);

        $filename = 'estado_resultados_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($report) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            $section = function (string $title, $accounts, float $total) use ($output) {
                fputcsv($output, [$title]);
                foreach ($accounts as $account) {
                    fputcsv($output, [$account->code, $account->name, number_format($account->amount, 2)]);
                }
                fputcsv($output, ['', 'Total ' . $title, number_format($total, 2)]);
                fputcsv($output, []);
            };

            $section('Ingresos', $report['ingresos'], $report['totalIngresos']);
            $section('Costos', $report['costos'], $report['totalCostos']);
            fputcsv($output, ['', 'Utilidad Bruta', number_format($report['utilidadBruta'], 2)]);
            fputcsv($output, []);
            $section('Gastos', $report['gastos'], $report['totalGastos']);
            fputcsv($output, ['', 'Utilidad Operativa', number_format($report['utilidadOperativa'], 2)]);
            fputcsv($output, []);
            $section('Otros Ingresos', $report['otrosIngresos'], $report['totalOtrosIngresos']);
            $section('Otros Gastos', $report['otrosGastos'], $report['totalOtrosGastos']);
            fputcsv($output, ['', 'Utilidad Neta', number_format($report['utilidadNeta'], 2)]);

            fclose($output);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
