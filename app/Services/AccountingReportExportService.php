<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AccountingReportExportService
{
    public function __construct(private ReportService $reports, private LedgerService $ledger)
    {
    }

    public function payload(string $report, Request $request): array
    {
        $dateFrom = $request->query('date_from') ?: now()->startOfMonth()->toDateString();
        $dateTo = $request->query('date_to') ?: now()->toDateString();
        $asOfDate = $request->query('as_of_date') ?: now()->toDateString();

        return match ($report) {
            'estado-resultados' => $this->incomeStatement($dateFrom, $dateTo),
            'balance-general' => $this->balanceSheet($asOfDate),
            'flujo-caja' => $this->cashFlow($dateFrom, $dateTo),
            'balance-comprobacion' => $this->trialBalance($dateFrom, $dateTo),
            'diario-general' => $this->journal($request, $dateFrom, $dateTo),
            'mayor-general' => $this->generalLedger($request, $dateFrom, $dateTo),
            default => abort(404),
        };
    }

    public function pdf(string $report, Request $request)
    {
        $payload = $this->payload($report, $request);

        return Pdf::loadView('contabilidad.reportes.pdf', $payload)
            ->setPaper('letter', $payload['orientation'] ?? 'portrait')
            ->download($report . '_' . now()->format('Ymd_His') . '.pdf');
    }

    public function excel(string $report, Request $request): BinaryFileResponse
    {
        $payload = $this->payload($report, $request);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($payload['title'], 0, 31));
        $writeRows = function (array $rows, int $startRow) use ($sheet): void {
            foreach ($rows as $rowOffset => $row) {
                foreach (array_values($row) as $columnOffset => $value) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnOffset + 1) . ($startRow + $rowOffset), $value);
                }
            }
        };
        $writeRows([[$payload['title']], [$payload['period']]], 1);
        $writeRows([$payload['headers']], 4);
        $writeRows($payload['rows'], 5);
        $lastColumn = Coordinate::stringFromColumnIndex(count($payload['headers']));
        $lastRow = 4 + count($payload['rows']);
        $sheet->mergeCells("A1:{$lastColumn}1")->mergeCells("A2:{$lastColumn}2");
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle("A4:{$lastColumn}4")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A4:{$lastColumn}4")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E293B');
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        for ($columnIndex = 1; $columnIndex <= count($payload['headers']); $columnIndex++) {
            $column = Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->freezePane('A5');

        $path = tempnam(sys_get_temp_dir(), 'accounting_') . '.xlsx';
        // PhpSpreadsheet 3.x todavía emite avisos internos deprecados en PHP 8.5.
        // No deben contaminar el archivo binario descargado.
        $errorLevel = error_reporting();
        error_reporting($errorLevel & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        try {
            (new Xlsx($spreadsheet))->save($path);
        } finally {
            error_reporting($errorLevel);
        }

        return response()->download($path, $report . '_' . now()->format('Ymd_His') . '.xlsx')->deleteFileAfterSend(true);
    }

    private function base(string $title, string $period, array $headers, array $rows, string $orientation = 'portrait'): array
    {
        return compact('title', 'period', 'headers', 'rows', 'orientation') + [
            'company' => Setting::get('system_name', 'Agroservicio POS'),
            'generatedAt' => now(),
        ];
    }

    private function incomeStatement(string $from, string $to): array
    {
        $r = $this->reports->incomeStatement($from, $to);
        $rows = [];
        foreach ([['Ingresos', 'ingresos', 'totalIngresos'], ['Costos', 'costos', 'totalCostos'], ['Gastos', 'gastos', 'totalGastos'], ['Otros ingresos', 'otrosIngresos', 'totalOtrosIngresos'], ['Otros gastos', 'otrosGastos', 'totalOtrosGastos']] as [$label, $key, $total]) {
            $rows[] = [$label, '', ''];
            foreach ($r[$key] as $account) $rows[] = ['', $account->code . ' - ' . $account->name, $account->amount];
            $rows[] = ['', 'Total ' . $label, $r[$total]];
        }
        $rows[] = ['', 'UTILIDAD NETA', $r['utilidadNeta']];
        return $this->base('Estado de Resultados', "Del {$from} al {$to}", ['Sección', 'Cuenta', 'Monto C$'], $rows);
    }

    private function balanceSheet(string $date): array
    {
        $r = $this->reports->balanceSheet($date); $rows = [];
        foreach ([['Activo', 'activo', 'totalActivo'], ['Pasivo', 'pasivo', 'totalPasivo'], ['Capital', 'capital', 'totalCapitalCuentas']] as [$label, $key, $total]) {
            $rows[] = [$label, '', ''];
            foreach ($r[$key] as $account) $rows[] = ['', $account->code . ' - ' . $account->name, $account->amount];
            $rows[] = ['', 'Total ' . $label, $r[$total]];
        }
        $rows[] = ['', 'Utilidad del ejercicio', $r['utilidadEjercicio']];
        $rows[] = ['', 'Diferencia', $r['diferencia']];
        return $this->base('Balance General', "Al {$date}", ['Sección', 'Cuenta', 'Monto C$'], $rows);
    }

    private function cashFlow(string $from, string $to): array
    {
        $r = $this->reports->cashFlow($from, $to);
        $rows = $r['movements']->map(fn ($m) => [$m['date'], $m['concept'], $m['reference'], $m['category'], $m['amount']])->all();
        $rows[] = ['', '', '', 'Saldo inicial', $r['openingBalance']];
        $rows[] = ['', '', '', 'Movimiento neto', $r['netMovement']];
        $rows[] = ['', '', '', 'Saldo final', $r['closingBalance']];
        return $this->base('Flujo de Caja', "Del {$from} al {$to}", ['Fecha', 'Concepto', 'Referencia', 'Categoría', 'Monto C$'], $rows, 'landscape');
    }

    private function trialBalance(string $from, string $to): array
    {
        $accounts = $this->ledger->trialBalance($from, $to);
        $rows = $accounts->map(fn ($a) => [$a->code, $a->name, $a->debe, $a->haber, $a->diferencia])->all();
        $rows[] = ['', 'TOTALES', $accounts->sum('debe'), $accounts->sum('haber'), $accounts->sum('debe') - $accounts->sum('haber')];
        return $this->base('Balance de Comprobación', "Del {$from} al {$to}", ['Código', 'Cuenta', 'Debe C$', 'Haber C$', 'Saldo C$'], $rows, 'landscape');
    }

    private function journal(Request $request, string $from, string $to): array
    {
        $entries = JournalEntry::posted()->with(['lines.account', 'user'])->whereBetween('date', [$from, $to]);
        if ($request->filled('user_id')) $entries->where('user_id', $request->user_id);
        if ($request->filled('account_id')) $entries->whereHas('lines', fn ($q) => $q->where('account_id', $request->account_id));
        $rows = $entries->orderBy('date')->orderBy('id')->get()->flatMap(fn ($entry) => $entry->lines->map(fn ($line) => [
            $entry->date->format('d/m/Y'), $entry->number, $line->account->code . ' - ' . $line->account->name,
            $line->detail ?: $entry->concept, (float) $line->debit, (float) $line->credit, $entry->user?->name,
        ]))->all();
        return $this->base('Diario General', "Del {$from} al {$to}", ['Fecha', 'Asiento', 'Cuenta', 'Detalle', 'Debe C$', 'Haber C$', 'Usuario'], $rows, 'landscape');
    }

    private function generalLedger(Request $request, string $from, string $to): array
    {
        $account = Account::findOrFail($request->integer('account_id'));
        $opening = $this->ledger->openingBalance($account, $from); $running = $opening;
        $rows = $this->ledger->movementsForAccount($account, $from, $to)->map(function ($line) use ($account, &$running) {
            $running += $account->nature === 'debit' ? (float) $line->debit - (float) $line->credit : (float) $line->credit - (float) $line->debit;
            return [$line->journalEntry->date->format('d/m/Y'), $line->journalEntry->number, $line->detail ?: $line->journalEntry->concept, (float) $line->debit, (float) $line->credit, $running];
        })->all();
        array_unshift($rows, ['', '', 'Saldo inicial', '', '', $opening]);
        return $this->base('Mayor General - ' . $account->code . ' ' . $account->name, "Del {$from} al {$to}", ['Fecha', 'Asiento', 'Detalle', 'Debe C$', 'Haber C$', 'Saldo C$'], $rows, 'landscape');
    }
}
