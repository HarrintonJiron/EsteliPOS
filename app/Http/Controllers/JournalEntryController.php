<?php

namespace App\Http\Controllers;

use App\Http\Requests\JournalEntryRequest;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use Illuminate\Http\Request;

class JournalEntryController extends Controller
{
    public function __construct(private AccountingService $accountingService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', JournalEntry::class);
        $query = JournalEntry::with('user')->orderByDesc('date')->orderByDesc('id');

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('concept', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        $entries = $query->paginate(20)->withQueryString();

        return view('contabilidad.asientos.index', compact('entries'));
    }

    public function create()
    {
        $this->authorize('create', JournalEntry::class);
        $accounts = Account::active()->postable()->orderBy('code')->get();
        $costCenters = CostCenter::active()->orderBy('code')->get();

        return view('contabilidad.asientos.create', compact('accounts', 'costCenters'));
    }

    public function store(JournalEntryRequest $request)
    {
        $this->authorize('create', JournalEntry::class);
        try {
            $entry = $this->accountingService->createEntry([
                ...$request->validated(),
                'user_id' => $request->user()?->id,
            ]);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('contabilidad.asientos.show', $entry)->with('success', 'Asiento creado en borrador.');
    }

    public function show(JournalEntry $journalEntry)
    {
        $this->authorize('view', $journalEntry);
        $journalEntry->load('lines.account', 'lines.costCenter', 'user');

        return view('contabilidad.asientos.show', ['entry' => $journalEntry]);
    }

    public function post(JournalEntry $journalEntry)
    {
        $this->authorize('post', $journalEntry);
        try {
            $this->accountingService->post($journalEntry);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Asiento contabilizado correctamente.');
    }

    public function void(Request $request, JournalEntry $journalEntry)
    {
        $this->authorize('void', $journalEntry);
        try {
            $this->accountingService->void($journalEntry, $request->input('reason'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Asiento anulado correctamente.');
    }

    public function destroy(JournalEntry $journalEntry)
    {
        $this->authorize('delete', $journalEntry);
        if ($journalEntry->status !== JournalEntry::STATUS_DRAFT) {
            return back()->with('error', 'Solo se pueden eliminar asientos en borrador.');
        }

        try {
            if (\App\Models\FiscalPeriod::isDateClosed($journalEntry->date->toDateString())) {
                throw new \RuntimeException('No se puede eliminar un asiento de un período cerrado.');
            }
            $journalEntry->lines()->delete();
            $journalEntry->delete();
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('contabilidad.asientos.index')->with('success', 'Asiento eliminado correctamente.');
    }
}
