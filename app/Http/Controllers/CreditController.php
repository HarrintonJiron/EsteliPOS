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

        $payment = CreditPayment::create([
            'client_id' => $validated['client_id'],
            'amount' => $validated['amount'],
            'payment_type' => $validated['payment_type'],
            'reference_number' => $validated['reference_number'],
            'notes' => $validated['notes'],
            'payment_date' => now(),
            'user_id' => $request->user()->id,
        ]);

        // Si se solicita imprimir inmediatamente, redirigimos a la vista de factura del abono
        if ($request->boolean('print')) {
            return redirect()->route('creditos.invoice', ['paymentId' => $payment->id]);
        }

        return redirect()->route('creditos.show', $validated['client_id'])
            ->with('success', 'Abono registrado correctamente');
    }

    /**
     * Búsqueda rápida de clientes y su resumen de crédito (JSON).
     */
    public function search(Request $request)
    {
        $q = $request->query('q');

        $clients = Client::query()
            ->when($q, fn($qb) => $qb->where('name', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%"))
            ->limit(12)
            ->get();

        $results = $clients->map(fn (Client $c) => [
            'id' => $c->id,
            'name' => $c->name,
            'phone' => $c->phone,
            'email' => $c->email,
            'credit_summary' => $this->credit->clientCreditSummary($c),
            'pending_sales' => Sale::where('client_id', $c->id)
                ->where('payment_type', 'credit')
                ->where('status', 'pending')
                ->with('details.product')
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'invoice_number' => $s->invoice_number,
                    'date' => $s->date?->toDateString(),
                    'due_date' => $s->due_date?->toDateString(),
                    'total' => (float) $s->total,
                    'items' => $s->details->map(fn ($d) => [
                        'product' => $d->product?->name ?? 'N/A',
                        'quantity' => $d->quantity,
                        'price' => (float) $d->price,
                        'subtotal' => (float) $d->subtotal,
                    ]),
                ]),
        ]);

        return response()->json($results);
    }

    /**
     * Vista imprimible para un abono (factura/recibo del pago).
     */
    public function invoice($paymentId)
    {
        $payment = CreditPayment::with('client', 'user', 'sale.details.product')->findOrFail($paymentId);
        $client = $payment->client;
        $pendingSales = Sale::where('client_id', $client->id)
            ->where('payment_type', 'credit')
            ->where('status', 'pending')
            ->with('details.product')
            ->get();

        return view('creditos.invoice', compact('payment', 'client', 'pendingSales'));
    }

    /**
     * Estado de cuenta / recibo térmico 80mm para un cliente con créditos pendientes
     */
    public function statement($clientId)
    {
        $client = Client::findOrFail($clientId);
        $creditSummary = $this->credit->clientCreditSummary($client);

        $pendingSales = Sale::where('client_id', $clientId)
            ->where('payment_type', 'credit')
            ->where('status', 'pending')
            ->with('details.product')
            ->latest()
            ->get();

        $payments = CreditPayment::where('client_id', $clientId)->latest()->get();

        return view('creditos.statement', compact('client', 'creditSummary', 'pendingSales', 'payments'));
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
