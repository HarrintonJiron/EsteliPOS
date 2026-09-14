<?php

namespace App\Http\Controllers;

use App\Services\LedgerService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrialBalanceController extends Controller
{
    public function __construct(private LedgerService $ledgerService)
    {
    }

    public function index(Request $request)
    {
        $accounts = $this->ledgerService->trialBalance($request->date_from, $request->date_to);
        $totalDebe = $accounts->sum('debe');
        $totalHaber = $accounts->sum('haber');

        return view('contabilidad.balance-comprobacion.index', compact('accounts', 'totalDebe', 'totalHaber'));
    }

    public function export(Request $request): StreamedResponse
    {
        $accounts = $this->ledgerService->trialBalance($request->date_from, $request->date_to);

        $filename = 'balance_comprobacion_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($accounts) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($output, ['Código', 'Cuenta', 'Debe', 'Haber', 'Diferencia']);

            foreach ($accounts as $account) {
                fputcsv($output, [
                    $account->code,
                    $account->name,
                    number_format($account->debe, 2),
                    number_format($account->haber, 2),
                    number_format($account->diferencia, 2),
                ]);
            }

            fputcsv($output, ['', 'TOTALES', number_format($accounts->sum('debe'), 2), number_format($accounts->sum('haber'), 2), '']);

            fclose($output);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
