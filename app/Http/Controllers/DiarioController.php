<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DiarioController extends Controller
{
    private function filteredEntries(Request $request)
    {
        $query = JournalEntry::posted()->with(['lines.account', 'user']);

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('account_id') || $request->filled('movement_type')) {
            $query->whereHas('lines', function ($q) use ($request) {
                if ($request->filled('account_id')) {
                    $q->where('account_id', $request->account_id);
                }

                if ($request->movement_type === 'debit') {
                    $q->where('debit', '>', 0);
                } elseif ($request->movement_type === 'credit') {
                    $q->where('credit', '>', 0);
                }
            });
        }

        return $query->orderBy('date')->orderBy('id')->get();
    }

    public function index(Request $request)
    {
        $entries = $this->filteredEntries($request);
        $accounts = Account::orderBy('code')->get();
        $users = User::orderBy('name')->get();

        return view('contabilidad.diario.index', compact('entries', 'accounts', 'users'));
    }

    public function export(Request $request): StreamedResponse
    {
        $entries = $this->filteredEntries($request);

        $filename = 'diario_general_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($entries) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($output, ['Fecha', 'Asiento', 'Concepto', 'Referencia', 'Cuenta', 'Detalle', 'Debe', 'Haber', 'Usuario']);

            foreach ($entries as $entry) {
                foreach ($entry->lines as $line) {
                    fputcsv($output, [
                        $entry->date->format('d/m/Y'),
                        $entry->number,
                        $entry->concept,
                        $entry->reference,
                        $line->account->code . ' - ' . $line->account->name,
                        $line->detail,
                        number_format((float) $line->debit, 2),
                        number_format((float) $line->credit, 2),
                        $entry->user?->name,
                    ]);
                }
            }

            fclose($output);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
