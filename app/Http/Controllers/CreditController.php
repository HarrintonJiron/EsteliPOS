<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CreditPayment;
use App\Models\Sale;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CreditController extends Controller
{
    public function __construct(private CreditService $credit) {}

    public function index(Request $request)
    {
        $clientsWithDebt = $this->credit->clientsWithDebt($request->query('search'))
            ->filter(fn (array $row) => $row['balance'] > 0);

        $portfolio = $this->credit->portfolioSummary();

        return view('creditos.index', compact('clientsWithDebt', 'portfolio'));
    }

    public function show($clientId)
    {
        $client = Client::findOrFail($clientId);
        $creditSummary = $this->credit->clientCreditSummary($client);

        $creditSales = Sale::where('client_id', $clientId)
            ->where('payment_type', 'credit')
            ->where('status', 'pending')
            ->with('user')
            ->latest()
            ->get();

        $payments = CreditPayment::where('client_id', $clientId)
            ->with('user')
            ->latest()
            ->get();

        $totalDebt = $creditSales->sum('total');
        $totalPaid = $payments->sum('amount');
        $balance = max(0, $totalDebt - $totalPaid);

        return view('creditos.show', compact(
            'client', 'creditSales', 'payments', 'totalDebt', 'totalPaid', 'balance', 'creditSummary'
        ));
    }

    public function create($clientId)
    {
        $client = Client::findOrFail($clientId);
        $creditSummary = $this->credit->clientCreditSummary($client);
        $balance = $creditSummary['balance'];
        $totalDebt = (float) Sale::where('client_id', $clientId)
            ->where('payment_type', 'credit')->where('status', 'pending')->sum('total');
        $totalPaid = (float) CreditPayment::where('client_id', $clientId)->sum('amount');

        return view('creditos.create', compact('client', 'balance', 'totalDebt', 'totalPaid', 'creditSummary'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_type' => 'required|in:cash,transfer,check,other',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        CreditPayment::create([
            'client_id' => $validated['client_id'],
            'amount' => $validated['amount'],
            'payment_type' => $validated['payment_type'],
            'reference_number' => $validated['reference_number'],
            'notes' => $validated['notes'],
            'payment_date' => now(),
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('creditos.show', $validated['client_id'])
            ->with('success', 'Abono registrado correctamente');
    }

    public function overdue()
    {
        $overdueCredits = Sale::where('payment_type', 'credit')
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->with('client', 'user')
            ->latest('due_date')
            ->paginate(20);

        $overdueCredits->getCollection()->transform(function ($sale) {
            $sale->days_overdue = now()->startOfDay()->diffInDays($sale->due_date, false) * -1;
            $sale->balance = max(0, (float) $sale->total);

            return $sale;
        });

        return view('creditos.overdue', compact('overdueCredits'));
    }

    public function report(Request $request)
    {
        $startDate = ($request->date('start_date') ?? now()->subMonth())->copy()->startOfDay();
        $endDate = ($request->date('end_date') ?? now())->copy()->endOfDay();

        $creditsSold = Sale::where('payment_type', 'credit')
            ->whereBetween('date', [$startDate, $endDate])
            ->with('client')
            ->latest()
            ->get();

        $paymentsReceived = CreditPayment::whereBetween('payment_date', [$startDate, $endDate])
            ->with('client', 'user')
            ->latest()
            ->get();

        $portfolio = $this->credit->portfolioSummary();
        $aging = $this->credit->agingReport();

        $totalCredits = $creditsSold->sum('total');
        $totalPayments = $paymentsReceived->sum('amount');

        $clientsOverLimit = Client::where('credit_enabled', true)
            ->where('credit_limit', '>', 0)
            ->get()
            ->filter(fn (Client $c) => $c->isOverCreditLimit())
            ->map(fn (Client $c) => array_merge(['client' => $c], $this->credit->clientCreditSummary($c)));

        $topDebtors = Client::where('credit_enabled', true)
            ->get()
            ->map(fn (Client $c) => ['client' => $c, 'balance' => $this->credit->pendingDebt($c)])
            ->filter(fn ($row) => $row['balance'] > 0)
            ->sortByDesc('balance')
            ->take(10)
            ->values();

        return view('creditos.report', compact(
            'creditsSold',
            'paymentsReceived',
            'totalCredits',
            'totalPayments',
            'portfolio',
            'aging',
            'clientsOverLimit',
            'topDebtors',
            'startDate',
            'endDate'
        ));
    }

    public function export(Request $request): Response
    {
        $startDate = ($request->date('start_date') ?? now()->subMonth())->copy();
        $endDate = ($request->date('end_date') ?? now())->copy();

        $clients = Client::where('credit_enabled', true)->orderBy('name')->get();

        $csv = "Cliente,Telefono,Limite Credito,Dias Plazo,Saldo Pendiente,Disponible,Vencido,Limite Excedido\n";

        foreach ($clients as $client) {
            $summary = $this->credit->clientCreditSummary($client);
            $overdue = Sale::where('client_id', $client->id)
                ->where('payment_type', 'credit')
                ->where('status', 'pending')
                ->where('due_date', '<', now())
                ->sum('total');

            $csv .= sprintf(
                "\"%s\",%s,%.2f,%d,%.2f,%s,%.2f,%s\n",
                str_replace('"', '""', $client->name),
                $client->phone ?? '',
                $summary['credit_limit'],
                $summary['credit_days'],
                $summary['balance'],
                $summary['available_credit'] === null ? 'Ilimitado' : number_format($summary['available_credit'], 2),
                $overdue,
                $summary['over_limit'] ? 'SI' : 'NO'
            );
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="creditos_'.now()->format('Ymd').'.csv"',
        ]);
    }
}
