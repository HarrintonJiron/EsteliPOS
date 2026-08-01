<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashFlowController extends Controller
{
    public function __construct(private ReportService $reportService)
    {
    }

    public function index(Request $request)
    {
        $dateFrom = $request->query('date_from') ?: now()->startOfMonth()->toDateString();
        $dateTo = $request->query('date_to') ?: now()->toDateString();

        $report = $this->reportService->cashFlow($dateFrom, $dateTo);

        return view('contabilidad.flujo-caja.index', $report + compact('dateFrom', 'dateTo'));
    }

    public function export(Request $request): StreamedResponse
    {
        $dateFrom = $request->query('date_from') ?: now()->startOfMonth()->toDateString();
        $dateTo = $request->query('date_to') ?: now()->toDateString();

        $report = $this->reportService->cashFlow($dateFrom, $dateTo);

        $filename = 'flujo_caja_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($report) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($output, ['Saldo Inicial', number_format($report['openingBalance'], 2)]);
            fputcsv($output, []);
            fputcsv($output, ['Fecha', 'Concepto', 'Referencia', 'Categoría', 'Monto']);

            foreach ($report['movements'] as $movement) {
                fputcsv($output, [
                    $movement['date'],
                    $movement['concept'],
                    $movement['reference'],
                    $movement['category'],
                    number_format($movement['amount'], 2),
                ]);
            }

            fputcsv($output, []);
            foreach ($report['byCategory'] as $category => $amount) {
                fputcsv($output, ['', '', '', $category, number_format($amount, 2)]);
            }

            fputcsv($output, []);
            fputcsv($output, ['', '', '', 'Movimiento Neto', number_format($report['netMovement'], 2)]);
            fputcsv($output, ['', '', '', 'Saldo Final', number_format($report['closingBalance'], 2)]);

            fclose($output);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
