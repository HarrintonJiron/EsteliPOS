<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\LedgerService;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    public function __construct(private LedgerService $ledgerService)
    {
    }

    public function index(Request $request)
    {
        $accounts = Account::postable()->orderBy('code')->get();
        $account = null;
        $movements = collect();
        $openingBalance = 0;
        $closingBalance = 0;

        if ($request->filled('account_id')) {
            $account = Account::findOrFail($request->account_id);
            $dateFrom = $request->date_from;
            $dateTo = $request->date_to;

            $openingBalance = $this->ledgerService->openingBalance($account, $dateFrom);
            $movements = $this->ledgerService->movementsForAccount($account, $dateFrom, $dateTo);

            $running = $openingBalance;
            $movements = $movements->map(function ($line) use (&$running, $account) {
                $signed = $account->nature === 'debit'
                    ? (float) $line->debit - (float) $line->credit
                    : (float) $line->credit - (float) $line->debit;
                $running += $signed;
                $line->running_balance = $running;

                return $line;
            });

            $closingBalance = $running;
        }

        return view('contabilidad.mayor.index', compact('accounts', 'account', 'movements', 'openingBalance', 'closingBalance'));
    }
}
