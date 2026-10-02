<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CajaSession;
use App\Models\Client;
use App\Models\CreditPayment;
use App\Models\RepairCreditPayment;
use App\Models\RepairOrder;
use App\Models\Sale;
use App\Services\AccountingService;
use App\Services\BranchContextService;
use App\Services\CreditService;
use App\Services\RepairPaymentService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CreditController extends Controller
{
    public function __construct(
        private CreditService $credit,
        private AccountingService $accountingService,
        private BranchContextService $branches,
    ) {}

    public function index(Request $request)
    {
        $type = in_array($request->query('type'), ['sales', 'repairs'], true) ? $request->query('type') : 'all';

        $allClients = $this->credit->clientsWithDebt($request->query('search'))
            ->filter(fn (array $row) => $row['balance'] > 0);

        $counts = [
            'all' => $allClients->count(),
            'sales' => $allClients->filter(fn (array $row) => $row['sales_balance'] > 0.00001)->count(),
            'repairs' => $allClients->filter(fn (array $row) => $row['repairs_balance'] > 0.00001)->count(),
        ];

        $clientsWithDebt = match ($type) {
            'sales' => $allClients->filter(fn (array $row) => $row['sales_balance'] > 0.00001),
            'repairs' => $allClients->filter(fn (array $row) => $row['repairs_balance'] > 0.00001),
            default => $allClients,
        };

        $portfolio = $this->credit->portfolioSummary();

        // En la vista "Reparaciones" se listan las órdenes a crédito pendientes de cada cliente.
        $repairCredits = collect();
        if ($type === 'repairs') {
            $clientModels = Client::query()->whereKey($clientsWithDebt->pluck('id'))->get()->keyBy('id');
            $repairCredits = $clientsWithDebt->flatMap(fn (array $row) => $this->credit->repairCredits($clientModels[$row['id']])
                ->map(fn (array $credit) => $credit + ['client' => $clientModels[$row['id']]]))->values();
        }

        return view('creditos.index', compact('clientsWithDebt', 'portfolio', 'type', 'counts', 'repairCredits'));
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

        $repairCredits = RepairOrder::where('client_id', $clientId)->where('payment_type', 'credit')
            ->whereIn('payment_status', ['pending', 'partial'])->with('creditPayments')->latest()->get();
        $repairPayments = RepairCreditPayment::where('client_id', $clientId)
            ->whereHas('repairOrder', fn ($query) => $query->where('payment_type', 'credit'))
            ->with('repairOrder')
            ->latest('payment_date')->get();

        $totalDebt = $creditSales->sum('total') + $repairCredits->sum('total');
        $totalPaid = $payments->sum('amount') + $repairPayments->sum('amount') + $repairCredits->sum('advance_payment');
        $balance = $creditSummary['balance'];
        $moraBreakdown = $this->credit->moraBreakdown($client);
        $totalMora = $this->credit->moraForClient($client);

        return view('creditos.show', compact(
            'client', 'creditSales', 'payments', 'totalDebt', 'totalPaid', 'balance', 'creditSummary',
            'moraBreakdown', 'totalMora', 'repairCredits', 'repairPayments'
        ));
    }

    public function create($clientId)
    {
        $client = Client::findOrFail($clientId);
        $creditSummary = $this->credit->clientCreditSummary($client);
        $balance = $creditSummary['balance'];
        $repairRows = $this->credit->repairCredits($client);
        $totalDebt = (float) Sale::where('client_id', $clientId)
            ->where('payment_type', 'credit')->where('status', 'pending')->sum('total')
            + (float) $repairRows->sum(fn (array $row) => (float) $row['order']->total);
        $totalPaid = max(0, round($totalDebt - $balance, 2));
        $applyTo = (string) request('apply_to', 'auto');

        $userBranchId = request()->user()?->branch_id;
        $branches = Branch::query()
            ->where('is_active', true)
            ->when($userBranchId, fn ($query) => $query->whereKey($userBranchId))
            ->orderBy('name')
            ->get();

        return view('creditos.create', compact('client', 'balance', 'totalDebt', 'totalPaid', 'creditSummary', 'branches', 'repairRows', 'applyTo'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_type' => 'required|in:cash,transfer,check,other',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string',
            'request_token' => 'required|uuid',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'apply_to' => ['nullable', 'regex:/^(auto|repairs|repair:\d+)$/'],
        ]);

        $existing = CreditPayment::query()
            ->where('request_token', $validated['request_token'])
            ->where('user_id', $request->user()->id)
            ->first()
            ?? RepairCreditPayment::query()
                ->where('request_token', $validated['request_token'])
                ->where('user_id', $request->user()->id)
                ->first();
        if ($existing) {
            return redirect()->route('creditos.show', $validated['client_id'])
                ->with('success', 'El abono ya había sido registrado; no se duplicó.');
        }

        try {
            [$payment, $repairPayments, $applied] = DB::transaction(function () use ($validated, $request) {
                $client = Client::query()->lockForUpdate()->findOrFail($validated['client_id']);
                $balance = $this->credit->pendingDebt($client);
                $amount = round((float) $validated['amount'], 2);
                $cashSession = $validated['payment_type'] === 'cash'
                    ? CajaSession::currentForUser($request->user()->id)
                    : null;
                if ($validated['payment_type'] === 'cash' && ! $cashSession) {
                    throw new \RuntimeException('Debes abrir una caja antes de recibir un abono en efectivo.');
                }
                if ($cashSession?->branch_id && isset($validated['branch_id'])
                    && (int) $validated['branch_id'] !== (int) $cashSession->branch_id) {
                    throw new \RuntimeException('La sucursal seleccionada no coincide con la caja abierta.');
                }
                if ($request->user()?->branch_id && isset($validated['branch_id'])
                    && (int) $validated['branch_id'] !== (int) $request->user()->branch_id) {
                    throw new \RuntimeException('Tu usuario no puede recibir abonos en otra sucursal.');
                }
                $requestedBranchId = isset($validated['branch_id'])
                    ? (int) $validated['branch_id']
                    : $request->user()?->branch_id;
                $branch = $this->branches->resolve(
                    $cashSession?->branch?->warehouse_id,
                    $cashSession,
                    $requestedBranchId,
                );
                if (! $branch && Branch::query()->where('is_active', true)->count() > 1) {
                    throw new \RuntimeException('Selecciona la sucursal donde se recibe este abono.');
                }

                if ($amount > $balance + 0.00001) {
                    throw new \RuntimeException(
                        'El abono no puede superar el saldo pendiente de '.number_format($balance, 2).'.'
                    );
                }

                // A qué se aplica el abono: facturas, reparaciones o una reparación concreta.
                $target = $validated['apply_to'] ?? 'auto';
                $repairRows = $this->credit->repairCredits($client);
                $repairsBalance = round((float) $repairRows->sum('balance'), 2);
                $salesBalance = round($balance - $repairsBalance, 2);

                if (str_starts_with($target, 'repair')) {
                    if (str_starts_with($target, 'repair:')) {
                        $repairId = (int) substr($target, 7);
                        $repairRows = $repairRows->filter(fn (array $row) => $row['order']->id === $repairId)->values();
                        if ($repairRows->isEmpty()) {
                            throw new \RuntimeException('La reparación elegida ya no tiene saldo a crédito pendiente.');
                        }
                    }
                    $available = round((float) $repairRows->sum('balance'), 2);
                    if ($amount > $available + 0.00001) {
                        throw new \RuntimeException('El abono supera el saldo de las reparaciones elegidas ('.number_format($available, 2).').');
                    }
                    $salesPart = 0.0;
                } else {
                    // Automático: primero las facturas y lo que sobre, a las reparaciones.
                    $salesPart = round(min($amount, $salesBalance), 2);
                }
                $repairPart = round($amount - $salesPart, 2);

                $payment = null;
                if ($salesPart > 0.00001) {
                    $payment = CreditPayment::create([
                        'request_token' => $validated['request_token'],
                        'client_id' => $validated['client_id'],
                        'amount' => $salesPart,
                        'payment_type' => $validated['payment_type'],
                        'reference_number' => $validated['reference_number'] ?? null,
                        'notes' => $validated['notes'] ?? null,
                        'payment_date' => now(),
                        'user_id' => $request->user()->id,
                        'branch_id' => $branch?->id,
                        'caja_session_id' => $cashSession?->id,
                    ]);

                    $this->accountingService->recordCreditPayment($payment);
                }

                $repairPayments = [];
                $remaining = $repairPart;
                foreach ($repairRows as $row) {
                    if ($remaining <= 0.00001) {
                        break;
                    }
                    $order = RepairOrder::query()->lockForUpdate()->findOrFail($row['order']->id);
                    $part = round(min($remaining, $order->balance()), 2);
                    if ($part <= 0.00001) {
                        continue;
                    }
                    $repairPayments[] = app(RepairPaymentService::class)->pay(
                        $order,
                        $part,
                        $validated['payment_type'],
                        $validated['reference_number'] ?? null,
                        $validated['notes'] ?? null,
                        $request->user(),
                        $validated['request_token'],
                    );
                    $remaining = round($remaining - $part, 2);
                }

                return [$payment, $repairPayments, ['sales' => $salesPart, 'repairs' => $repairPart]];
            });
        } catch (UniqueConstraintViolationException $e) {
            if (CreditPayment::query()->where('request_token', $validated['request_token'])->exists()) {
                return redirect()->route('creditos.show', $validated['client_id'])
                    ->with('success', 'El abono ya había sido registrado; no se duplicó.');
            }
            throw $e;
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        // Si se solicita imprimir inmediatamente, redirigimos al recibo del abono
        if ($request->boolean('print')) {
            if ($payment) {
                return redirect()->route('creditos.invoice', ['paymentId' => $payment->id]);
            }
            if ($repairPayments !== []) {
                return redirect()->route('creditos.repair-receipt', ['paymentId' => $repairPayments[0]->id]);
            }
        }

        $message = 'Abono registrado correctamente';
        if ($applied['sales'] > 0.00001 && $applied['repairs'] > 0.00001) {
            $message .= '. Aplicado: facturas $ '.number_format($applied['sales'], 2).' y reparaciones $ '.number_format($applied['repairs'], 2).'.';
        } elseif ($applied['repairs'] > 0.00001) {
            $message .= ' a reparaciones a crédito.';
        }

        return redirect()->route('creditos.show', $validated['client_id'])
            ->with('success', $message);
    }

    /**
     * Búsqueda rápida de clientes y su resumen de crédito (JSON).
     */
    public function search(Request $request)
    {
        $q = $request->query('q');

        $clients = Client::query()
            ->when($q, fn ($qb) => $qb->where(function ($searchQ) use ($q) {
                $searchQ->where('name', 'like', "%{$q}%")
                    ->orWhere('business_name', 'like', "%{$q}%")
                    ->orWhere('cedula', 'like', "%{$q}%")
                    ->orWhere('ruc', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            }))
            ->limit(12)
            ->get();

        $results = $clients->map(fn (Client $c) => [
            'id' => $c->id,
            'name' => $c->name,
            'legal_name' => $c->legal_name,
            'client_type' => $c->client_type,
            'document_label' => $c->document_label,
            'document_number' => $c->document_number,
            'phone' => $c->phone,
            'email' => $c->email,
            'credit_summary' => $this->credit->clientCreditSummary($c),
            'pending_repairs' => $this->credit->repairCredits($c)->map(fn (array $row) => [
                'id' => $row['order']->id,
                'order_number' => $row['order']->order_number,
                'device' => trim($row['order']->device_brand.' '.$row['order']->device_model),
                'due_date' => $row['order']->due_date?->toDateString(),
                'total' => (float) $row['order']->total,
                'balance' => (float) $row['balance'],
            ])->values(),
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
     * Recibo imprimible de un abono a una reparación a crédito.
     */
    public function repairReceipt($paymentId)
    {
        $payment = RepairCreditPayment::with('repairOrder.client', 'user')->findOrFail($paymentId);
        $order = $payment->repairOrder;
        abort_unless($order && $order->payment_type === 'credit', 404);

        return view('creditos.repair-receipt', [
            'payment' => $payment,
            'order' => $order,
            'client' => $order->client,
            'balance' => $order->balance(),
        ]);
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

        $repairRows = $this->credit->repairCredits($client);
        $repairPayments = RepairCreditPayment::where('client_id', $clientId)
            ->whereHas('repairOrder', fn ($query) => $query->where('payment_type', 'credit'))
            ->with('repairOrder')
            ->latest('payment_date')
            ->get();

        return view('creditos.statement', compact('client', 'creditSummary', 'pendingSales', 'payments', 'repairRows', 'repairPayments'));
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
            $sale->balance = $this->credit->outstandingBalanceForSale($sale);

            return $sale;
        })->filter(fn ($sale) => $sale->balance > 0)->values();

        $overdueRepairs = $this->credit->overdueRepairCredits();

        return view('creditos.overdue', compact('overdueCredits', 'overdueRepairs'));
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

        $repairCreditsIssued = RepairOrder::where('payment_type', 'credit')
            ->whereBetween('received_date', [$startDate, $endDate])
            ->with('client')
            ->latest('received_date')
            ->get();

        $repairPaymentsReceived = RepairCreditPayment::whereBetween('payment_date', [$startDate, $endDate])
            ->whereHas('repairOrder', fn ($query) => $query->where('payment_type', 'credit'))
            ->with('repairOrder.client', 'user')
            ->latest('payment_date')
            ->get();

        $portfolio = $this->credit->portfolioSummary();
        $aging = $this->credit->agingReport();

        $totalCredits = $creditsSold->sum('total') + $repairCreditsIssued->sum('total');
        $totalPayments = $paymentsReceived->sum('amount') + $repairPaymentsReceived->sum('amount');

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
            'repairCreditsIssued',
            'repairPaymentsReceived',
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

        $csv = "Cliente,Tipo Cliente,Documento,Telefono,Limite Credito,Dias Plazo,Saldo Pendiente,Saldo Reparaciones,Disponible,Vencido,Limite Excedido\n";

        foreach ($clients as $client) {
            $summary = $this->credit->clientCreditSummary($client);
            $overdue = Sale::where('client_id', $client->id)
                ->where('payment_type', 'credit')
                ->where('status', 'pending')
                ->where('due_date', '<', now())
                ->sum('total');
            $overdue += (float) $this->credit->repairCredits($client)
                ->filter(fn (array $row) => $row['order']->due_date?->copy()->startOfDay()->isBefore(now()->startOfDay()))
                ->sum('balance');

            $csv .= sprintf(
                "\"%s\",%s,%s,%s,%.2f,%d,%.2f,%.2f,%s,%.2f,%s\n",
                str_replace('"', '""', $client->legal_name),
                $client->isCompany() ? 'Empresa' : 'Persona Natural',
                $client->document_number ?? '',
                $client->phone ?? '',
                $summary['credit_limit'],
                $summary['credit_days'],
                $summary['balance'],
                $summary['repairs_balance'],
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
